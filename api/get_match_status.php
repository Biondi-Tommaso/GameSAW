<?php
require_once 'db_connect.php';

// Impedisce al browser di salvare la risposta
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

$matchId = $_GET['match_id'] ?? null;
if (!$matchId) exit(json_encode(["error" => "No ID"]));

$stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
$stmt->execute([$matchId]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($match);