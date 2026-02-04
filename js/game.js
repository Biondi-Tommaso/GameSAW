const ROWS = 6;
const COLS = 7;
let board = Array(ROWS).fill().map(() => Array(COLS).fill(0));
let currentPlayer = 1;
let gameActive = true;

let myRole = 0; 
let isMyTurn = false;
let pollingInterval = null;
let playerNames = {};

const boardElement = document.getElementById('game-board');
const statusElement = document.getElementById('status');

function createBoard() {
    boardElement.innerHTML = '';
    for (let r = 0; r < ROWS; r++) {
        for (let c = 0; c < COLS; c++) {
            const cell = document.createElement('div');
            cell.classList.add('cell');
            cell.dataset.row = r;
            cell.dataset.col = c;
            cell.onclick = () => handleMove(c);
            boardElement.appendChild(cell);
        }
    }
}

function endGame() {
    gameActive = false;
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
    const cells = document.querySelectorAll('.cell');
    cells.forEach(cell => cell.style.cursor = 'default');
}

function handleMove(col) {
    if (!gameActive) return;
    if (GAME_MODE === 'online' && !isMyTurn) return;

    for (let r = ROWS - 1; r >= 0; r--) {
        if (board[r][col] === 0) {
            if (GAME_MODE === 'online') {
                executeOnlineMove(r, col);
            } else {
                makeMove(r, col);
            }
            return;
        }
    }
}

function makeMove(row, col) {
    board[row][col] = currentPlayer;
    updateVisualBoard();
    if (checkWin(row, col)) {
        statusElement.innerText = ` Giocatore ${currentPlayer} ha vinto!`;
        statusElement.style.color = (currentPlayer === 1) ? "#206602" : "#60211a";
        endGame();
        
        if (GAME_MODE === 'bot' && currentPlayer === 1) {
            saveScore(100);
        }
        return;
    }

    // Controlla pareggio
    if (board[0].every(cell => cell !== 0)) {
        statusElement.innerText = "Pareggio!";
        endGame();
        return;
    }

    currentPlayer = (currentPlayer === 1) ? 2 : 1;
    statusElement.innerText = `Turno del Giocatore ${currentPlayer}`;

    if (GAME_MODE === 'bot' && currentPlayer === 2 && gameActive) {
        boardElement.style.pointerEvents = 'none';
        setTimeout(botMove, 600);
    } else {
        boardElement.style.pointerEvents = 'auto';
    }
}

function getLowestEmptyRow(col) {
    for (let r = ROWS - 1; r >= 0; r--) {
        if (board[r][col] === 0) {
            return r;
        }
    }
    return -1;
}

function botMove() {
    if (!gameActive) return;

    let availableCols = [];
    let totalMoves = 0;
    let lastUserMove = { r: -1, c: -1 };

    // 1. Analisi della scacchiera: conta le mosse e trova l'ultima dell'utente
    for (let c = 0; c < COLS; c++) {
        if (board[0][c] === 0) availableCols.push(c);
        for (let r = 0; r < ROWS; r++) {
            if (board[r][c] !== 0) {
                totalMoves++;
                if (board[r][c] === 1) {
                    lastUserMove = { r: r, c: c };
                }
            }
        }
    }

    if (availableCols.length === 0) return;

    // 2. LOGICA PRIMO TURNO (Se c'è solo 1 pedina, quella dell'utente)
    if (totalMoves === 1) {
        // Proviamo a destra o sinistra
        let targets = [lastUserMove.c + 1, lastUserMove.c - 1];
        // Mescoliamo l'ordine per non andare sempre a destra
        targets.sort(() => Math.random() - 0.5); 

        for (let targetCol of targets) {
            if (targetCol >= 0 && targetCol < COLS && board[0][targetCol] === 0) {
                const row = getLowestEmptyRow(targetCol);
                makeMove(row, targetCol);
                boardElement.style.pointerEvents = 'auto';
                return; // ESCI SUBITO: mossa fatta
            }
        }
    }

    // 3. LOGICA DI VITTORIA (Se non è il primo turno o non può mettersi accanto)
    for (let col of availableCols) {
        let row = getLowestEmptyRow(col);
        board[row][col] = 2;
        if (checkWin(row, col)) {
            makeMove(row, col);
            boardElement.style.pointerEvents = 'auto';
            return;
        }
        board[row][col] = 0;
    }

    // 4. LOGICA DI DIFESA
    for (let col of availableCols) {
        let row = getLowestEmptyRow(col);
        board[row][col] = 1;
        if (checkWin(row, col)) {
            board[row][col] = 0;
            makeMove(row, col);
            boardElement.style.pointerEvents = 'auto';
            return;
        }
        board[row][col] = 0;
    }

    // 5. MOSSA CASUALE (Fallback)
    const randomCol = availableCols[Math.floor(Math.random() * availableCols.length)];
    makeMove(getLowestEmptyRow(randomCol), randomCol);
    boardElement.style.pointerEvents = 'auto';
}

function updateVisualBoard() {
    const cells = document.querySelectorAll('.cell');
    board.forEach((row, r) => {
        row.forEach((value, c) => {
            const index = r * COLS + c;
            cells[index].classList.remove('player1', 'player2');
            if (value === 1) cells[index].classList.add('player1');
            if (value === 2) cells[index].classList.add('player2');
        });
    });
}

function checkWin(r, c) {
    const player = board[r][c];
    const directions = [
        [0, 1],   // Orizzontale
        [1, 0],   // Verticale
        [1, 1],   // Diagonale 
        [1, -1]   // Diagonale 
    ];

    for (let [dr, dc] of directions) {
        let count = 1;
        count += countInDirection(r, c, dr, dc, player);
        count += countInDirection(r, c, -dr, -dc, player);
        if (count >= 4) return true;
    }
    return false;
}

function countInDirection(r, c, dr, dc, player) {
    let match = 0;
    let currR = r + dr;
    let currC = c + dc;

    while (
        currR >= 0 && currR < ROWS && 
        currC >= 0 && currC < COLS && 
        board[currR][currC] === player
    ) {
        match++;
        currR += dr;
        currC += dc;
    }
    return match;
}


async function saveScore(score) {
    try {
        // Genera token di sicurezza
        const tokenTime = Date.now();
        const randomPart = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        const win_token = randomPart + '_' + tokenTime;

        const response = await fetch('api/save_score.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                score: score,
                win_token: win_token,
                token_time: tokenTime
            })
        });

        const result = await response.json();
        
        
        return result;
    } catch (error) {
        error_log(' Errore di rete:', error);
        return { success: false, error: 'Errore di connessione' };
    }
}



function initOnlineGame() {
    fetch(`api/get_match_status.php?match_id=${MATCH_ID}`)
        .then(res => res.json())
        .then(data => {
            myRole = (data.player1_id == USER_ID) ? 1 : 2;
            
            playerNames[data.player1_id] = data.player1_name;
            if (data.player2_id) {
                playerNames[data.player2_id] = data.player2_name;
            }
            if (data.status === 'active') {
                syncBoard(data);
            } else {
                statusElement.innerText = "In attesa di un avversario...";
                setTimeout(initOnlineGame, 1000);
            }
        })
        .catch(err => {
            error_log('Errore fetch match status:', err);
            setTimeout(initOnlineGame, 1000);
        });
}

function syncBoard(match) {
    if (match.board_state) {
        board = typeof match.board_state === 'string' ? JSON.parse(match.board_state) : match.board_state;
        updateVisualBoard();
    }
    
    if (match.status === 'finished') {
        gameActive = false;
        const won = match.winner_id == USER_ID;
        statusElement.innerText = won ? " HAI VINTO!" : " HAI PERSO!";
        statusElement.style.color = won ? "#2ecc71" : "#e74c3c";
        endGame();
        


        return;
    }
    
    if (match.current_turn == USER_ID) {
        isMyTurn = true;
        statusElement.innerText = `Tocca a te! (Sei ${myRole === 1 ? 'Rosso' : 'Giallo'})`;
        if (pollingInterval) clearInterval(pollingInterval);
        pollingInterval = null;
    } else {
        isMyTurn = false;
        statusElement.innerText = 'Turno dell\'avversario...';
        
        if (!pollingInterval && gameActive) {
            pollingInterval = setInterval(pollOpponent, 1500);
        }
    }
}

function pollOpponent() {
    if (!gameActive) return;
    fetch(`api/get_match_status.php?match_id=${MATCH_ID}`)
        .then(res => res.json())
        .then(match => syncBoard(match));
}

function executeOnlineMove(r, c) {
    isMyTurn = false;
    board[r][c] = myRole;
    updateVisualBoard();

    const won = checkWin(r, c);
    
    fetch('api/update_match.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            match_id: MATCH_ID,
            board: board,
            winner_id: won ? USER_ID : null
        })
    })
    .then(res => res.json())
    .then(response => {
        if (won) {
            statusElement.innerText = "HAI VINTO!";
            statusElement.style.color = "#2ecc71";
            endGame();
            saveScore(100);
        } else {
            statusElement.innerText = "Attesa mossa avversario...";
            if (pollingInterval) clearInterval(pollingInterval);
            pollingInterval = setInterval(pollOpponent, 1500);
        }
    })
    .catch(err => error_log("Errore mossa:", err));
}


createBoard();

if (GAME_MODE === 'online') {
    initOnlineGame();
} else {
    isMyTurn = true;
    statusElement.innerText = GAME_MODE === 'bot' ? "Sfida il Bot!" : "Modalità Locale";
}