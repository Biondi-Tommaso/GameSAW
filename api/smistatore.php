<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$mode = $_GET['mode'] ?? 'local';
$userId = intval($_SESSION['user_id']);

if ($mode === 'online') {
    try {
        // pulizia match abbandonati
        $pdo->exec("DELETE FROM matches WHERE status = 'waiting' AND last_move_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

        // cerco un match in attesa
        $sql = "SELECT id, player1_id FROM matches WHERE status = 'waiting' AND player1_id != $userId ORDER BY last_move_at ASC LIMIT 1";
        $match = $pdo->query($sql)->fetch();

        if ($match) {
            // mi unisco a un match esistente
            $matchId = $match['id'];
            $player1Id = $match['player1_id'];
        
            $players = [$player1Id, $userId];
            $startingPlayer = $players[array_rand($players)];

            $stmt = $pdo->prepare("UPDATE matches SET player2_id = ?, status = 'active', current_turn = ?, last_move_at = NOW() WHERE id = ?");
            $rowsAffected = $stmt->execute([$userId, $startingPlayer, $matchId]);
            
            if (!$rowsAffected) {
                throw new Exception("Errore nell'aggiornamento del match");
            }
            
            error_log("Joining match $matchId as player2");
            header("Location: ../game.php?mode=online&match_id=$matchId");
        } else {
            // creo un nuovo match con board_state inizializzato
            $emptyBoard = json_encode(array_fill(0, 6, array_fill(0, 7, 0)));
            $stmt = $pdo->prepare("INSERT INTO matches (player1_id, status, current_turn, board_state, last_move_at) VALUES (?, 'waiting', ?, ?, NOW())");
            $stmt->execute([$userId, $userId, $emptyBoard]);
            $matchId = $pdo->lastInsertId();
            
            if (!$matchId) {
                throw new Exception("Errore nella creazione del match");
            }
            
            error_log("Creating new match $matchId as player1");
            header("Location: ../game.php?mode=online&match_id=$matchId");
        }
    } catch (Exception $e) {
        error_log("Errore in smistatore.php: " . $e->getMessage());
        header("Location: ../game.php?mode=local&error=connection");
    }
} else {
    header("Location: ../game.php?mode=$mode");
}
exit();
?>