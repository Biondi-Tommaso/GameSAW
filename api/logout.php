<?php
session_start();
require_once 'db_connect.php';

if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $sql = "UPDATE users SET remember_selector = NULL, remember_validator = NULL WHERE id = $userId";
    $pdo->query($sql);
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

if (isset($_COOKIE['remember_me'])) {
    setcookie("remember_me", "", time() - 3600, "/");
}

session_destroy();

header("Location: ../index.php");
exit(); 