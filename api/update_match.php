<?php
session_start();
require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id']) || !isset($data['match_id'])) {
    echo json_encode(["status" => "error", "message" => "Dati mancanti"]);
    exit();
}

$matchId = $data['match_id'];
$boardState = json_encode($data['board']);
$winnerId = $data['winner_id'] ?? null;
$status = $winnerId ? 'finished' : 'active';

try {
    
    $pdo->beginTransaction();

    
    $stmt = $pdo->prepare("SELECT player1_id, player2_id, current_turn FROM matches WHERE id = ?");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch();

    if (!$match) {
        throw new Exception("Partita non trovata");
    }

    
    $nextTurn = ($match['current_turn'] == $match['player1_id']) ? $match['player2_id'] : $match['player1_id'];

    
    $updateStmt = $pdo->prepare("UPDATE matches SET board_state = ?, current_turn = ?, status = ?, winner_id = ? WHERE id = ?");
    $updateStmt->execute([$boardState, $nextTurn, $status, $winnerId, $matchId]);

    
    if ($winnerId !== null) {
        $punteggioPremio = 100;
        $scoreStmt = $pdo->prepare("INSERT INTO scores (user_id, punteggio, data_partita) VALUES (?, ?, NOW())");
        $scoreStmt->execute([$winnerId, $punteggioPremio]);
    }

    $pdo->commit();
    echo json_encode(["status" => "success", "winner" => $winnerId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}