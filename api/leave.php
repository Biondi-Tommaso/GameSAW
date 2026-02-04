<?php
session_start();
require_once 'db_connect.php';

$match_id = $_POST['match_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;

if (!$match_id || !$user_id) {
    header("Location: ../index.php");
    exit();
}


$stmt = $pdo->prepare("SELECT player1_id, player2_id, status FROM matches WHERE id = ?");
$stmt->execute([$match_id]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    header("Location: ../index.php");
    exit();
}


if ($match['status'] == 'waiting' && $match['player2_id'] == null) {
    
    $stmt = $pdo->prepare("DELETE FROM matches WHERE id = ?");
    $stmt->execute([$match_id]);
} else {
    
    $winner_id = null;
    
    if ($match['player2_id'] != null) {
        
        $winner_id = ($match['player1_id'] == $user_id) ? $match['player2_id'] : $match['player1_id'];
        
        
        $pdo->exec("UPDATE scores SET punteggio = punteggio + 100 WHERE id = " . intval($winner_id));
    }
    
    
    $stmt = $pdo->prepare("UPDATE matches SET status = 'finished', winner_id = ? WHERE id = ?");
    $stmt->execute([$winner_id, $match_id]);
}


if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
} else {
    header("Location: ../login.php");
}
exit();
?>