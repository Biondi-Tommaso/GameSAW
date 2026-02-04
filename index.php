<?php
session_start();
require_once 'api/db_connect.php';

// Logica Remember Me
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $parts = explode(':', $_COOKIE['remember_me']);
    
    if (count($parts) === 2) {
        list($selector, $validator) = $parts;
        $stmt = $pdo->prepare("SELECT id, remember_validator FROM users WHERE remember_selector = ?");
        $stmt->execute([$selector]);
        $user = $stmt->fetch();

        if ($user && hash_equals($user['remember_validator'], hash('sha256', $validator))) { //uso hash_equals per prevenire timing attacks, visto che == è "pigro" e si ferma nel momento in cui trova il risultato uso hash_equale che è progettato per essere costante nel tempo, in questo modo un attaccantte non può verificare la validità del token dal tempo di risposta del server
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
        } else {
            setcookie("remember_me", "", time() - 3600, "/");
        }
    }
}

$isLoggedIn = isset($_SESSION['user_id']);
$targetPage = $isLoggedIn ? "profile.php" : "login.php";
$buttonText = $isLoggedIn ? "Account" : "Login";


// query modificata per la richiesta
// QUERY PER LA CLASSIFICA (potrei salvare direttamente la somma nel database, ma così ho una specie di "log" delle partite)
$stmt = $pdo->query("
    SELECT u.username, SUM(s.punteggio) as totale, COUNT(s.id) as partite_giocate
    FROM users u
    JOIN scores s ON u.id = s.user_id
    GROUP BY u.id
    ORDER BY totale DESC
    LIMIT 10
");
$leaderboard = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="css/index.css">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
    <title>Connect4 Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
</head>
<body>

    <a href="<?php echo $targetPage; ?>" class="auth-btn">
        <?php echo $buttonText; ?>
    </a>

    <div class="slide-container">
        <section class="slide" id="slide1">
            <div class="intro-content">
                <h1>Welcome to Connect 4!</h1>
                <button class="button slide-next-btn">Start</button>
            </div>
        </section>
        
        <section class="slide" id="slide2">
    <h2>Connect 4</h2>
    <p>Select Game Mode</p>
<div class="game-mode-container">
    <a href="mode.html" class="button">
        <span class="slide2">Play now</span>
    </a>
</div>
</section>
        
        <section class="slide" id="slide3">
            <div class="intro-content">
                <h2 class="leaderboard-title"> Leaderboard</h2>
                
                <div class="leaderboard-wrapper">
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th>Pos.</th>
                                <th>Player</th>
                                <th>Score</th>
                                <th>Matches</th>  <!-- modifica richiesta -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($leaderboard) > 0): ?>
                                <?php foreach ($leaderboard as $index => $row): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo number_format($row['totale']); ?></td>
                                        <td><?php echo $row['partite_giocate']; ?></td> <!-- modifica richiesta -->
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3">No scores yet. Be the first!</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <p class="leaderboard-subtitle">Top 10 Players</p>
            </div>
        </section>

        <section class="slide" id="slide4">
    <h2>Chi Siamo</h2>
    <div class="about-content">
        <p>
            <strong>GameSAW</strong> è la destinazione definitiva per gli amanti della strategia. 
            Abbiamo riportato in vita il leggendario <strong>Forza 4</strong> in una veste moderna, 
            fluida e accessibile a tutti.
        </p>
        <p>
            Che tu voglia sfidare la nostra <strong>IA</strong> per allenarti, giocare con un amico 
            in <strong>multiplayer locale</strong> o scalare la classifica sfidando avversari nella 
            <strong>modalità pubblica</strong>, GameSAW ha la sfida che fa per te.
        </p>
        <div class="links-construction">
            <small>
                <a href="under-construction.html">Contatti</a> | 
                <a href="under-construction.html">Dove siamo</a> | 
                <a href="under-construction.html">Termini di servizio</a>
            </small>
        </div>
    </div>
</section>
    </div>
    <div id="down-arrow" class="down-arrow slide-next-btn">&#x2193;</div>
    
    <script src="js/index.js"></script>
</body>
</html>