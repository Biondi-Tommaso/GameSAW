<?php
session_start();
require_once 'api/db_connect.php';

$message = "";
$messageType = ""; // 'success' o 'error'
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = "Token di verifica non valido o mancante.";
    $messageType = "error";
} else {
    try {
        // Cerca l'utente con questo token
        $stmt = $pdo->prepare("SELECT id, username, email_verified, token_expiry FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $message = "Token non valido o già utilizzato.";
            $messageType = "error";
        } elseif ($user['email_verified'] == 1) {
            $message = "Email già verificata! Puoi effettuare il login.";
            $messageType = "success";
        } elseif (strtotime($user['token_expiry']) < time()) {
            $message = "Il token è scaduto. Per favore, richiedi un nuovo link di verifica.";
            $messageType = "error";
        } else {
            // Tutto ok, verifica l'email
            $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE verification_token = ?");
            
            if ($stmt->execute([$token])) {
                $message = " Email verificata con successo! Ora puoi effettuare il login.";
                $messageType = "success";
            } else {
                $message = "Si è verificato un errore durante la verifica. Riprova o contatta il supporto.";
                $messageType = "error";
            }
        }
    } catch (PDOException $e) {
        $message = "Errore del database: " . htmlspecialchars($e->getMessage());
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Verifica Email - Connect4 Hub</title>
    <link rel="stylesheet" href="css/verify_email.css">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
</head>
<body>

    <div class="verify-container">
        <div class="verify-icon <?php echo $messageType; ?>">
            <?php if ($messageType === 'success'): ?>
                ✓
            <?php else: ?>
                ✕
            <?php endif; ?>
        </div>
        
        <h2>Verifica Email</h2>
        
        <div class="message <?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        
        <div class="button-group">
            <?php if ($messageType === 'success'): ?>
                <a href="login.php" class="btn btn-primary">Vai al Login</a>
            <?php else: ?>
                <a href="register.php" class="btn btn-secondary">Torna alla Registrazione</a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">Home</a>
        </div>
    </div>

</body>
</html>