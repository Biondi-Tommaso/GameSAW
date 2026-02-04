<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    $userId = intval($_SESSION['user_id']);

    try {
        $pdo->beginTransaction();

        $pdo->exec("DELETE FROM matches WHERE player1_id = $userId");
        $pdo->exec("DELETE FROM matches WHERE player2_id = $userId");
        $pdo->exec("DELETE FROM users WHERE id = $userId");

        $pdo->commit();

        $_SESSION = array();
        session_destroy();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error deleting account: " . $e->getMessage());
    }
}

header("Location: ../index.php");
exit();