<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$userId = (int)$_SESSION['user_id']; 

// Recupero dati dal form
$nome    = trim($_POST['nome'] ?? '');
$cognome = trim($_POST['cognome'] ?? '');
$citta   = trim($_POST['citta'] ?? '');
$bio     = trim($_POST['bio'] ?? '');
$livello = $_POST['skill_level'] ?? 'beginner';
$avatar  = $_POST['avatar'] ?? '';

// Dati per il cambio password
$currentPass = $_POST['current_password'] ?? '';
$newPass     = $_POST['new_password'] ?? '';
$confirmPass = $_POST['confirm_password'] ?? '';

try {
    // Aggiornamento dati anagrafici e livello
    $allowedLevels = ['beginner', 'intermediate', 'expert'];
    if (!in_array($livello, $allowedLevels)) {
        $livello = 'beginner';
    }

    $sql = "UPDATE users SET nome = ?, cognome = ?, citta = ?, bio = ?, livello = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nome, $cognome, $citta, $bio, $livello, $userId]);

    //  Aggiornamento Avatar
    $allowedAvatars = [];
    for ($i = 1; $i <= 5; $i++) {
        $allowedAvatars[] = "default{$i}.png";
    }

    if (!empty($avatar) && in_array($avatar, $allowedAvatars)) {
        $stmtAvatar = $pdo->prepare("UPDATE users SET foto_profilo = ? WHERE id = ?");
        $stmtAvatar->execute([$avatar, $userId]);
    }

    // LOGICA CAMBIO PASSWORD
    if (!empty($newPass)) {
        
        // Verifica se le nuove password coincidono
        if ($newPass !== $confirmPass) {
            header("Location: ../profile.php?error=pass_match");
            exit();
        }

        // Verifica lunghezza minima
        if (strlen($newPass) < 8) {
            header("Location: ../profile.php?error=pass_corta");
            exit();
        }

        // Recupero l'hash attuale dal database per il confronto
        $stmtCheck = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmtCheck->execute([$userId]);
        $userRow = $stmtCheck->fetch();

        if ($userRow && password_verify($currentPass, $userRow['password_hash'])) {
            // Se la password attuale è corretta, genero il nuovo hash e aggiorno
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $stmtUpdatePass = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmtUpdatePass->execute([$newHash, $userId]);
        } else {
            // Password attuale errata: fermo l'esecuzione e ritorno errore
            header("Location: ../profile.php?error=pass_errata");
            exit();
        }
    }

    // Se tutto è andato a buon fine
    header("Location: ../profile.php?success=1");
    exit();

} catch (PDOException $e) {
    // Gestione errore database
    header("Location: ../profile.php?error=database");
    exit();
}