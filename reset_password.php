<?php
session_start();
require_once 'api/db_connect.php';

// Se l'utente è già loggato, reindirizza
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";
$validToken = false;
$user = null;

$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Token mancante o non valido.";
} else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $validToken = true;
    } else {
        $error = "Il link di reset è scaduto o non è valido.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $validToken) {
    $pass = $_POST['password'];
    $pass_confirm = $_POST['password_confirm'];

    if (strlen($pass) < 8) {
        $error = "La password deve contenere almeno 8 caratteri!";
    } elseif ($pass !== $pass_confirm) {
        $error = "Le password non coincidono!";
    } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);

        try {
            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            if ($updateStmt->execute([$hash, $user['id']])) {
                $success = "Password aggiornata con successo!";
                header("refresh:3;url=login.php");
            } else {
                $error = "Errore durante l'aggiornamento della password.";
            }
        } catch (PDOException $e) {
            $error = "Errore del database: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Reset Password - Connect4 Hub</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/reset_password.css">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
</head>
<body>

    <div class="auth-container">
        <h2>Reset Password</h2>
        
        <?php if($error): ?>
            <div class="error-msg alert-box">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success-msg alert-box">
                <?php echo htmlspecialchars($success); ?>
                <br><span class="redirect-subtext">Reindirizzamento al login in corso...</span>
            </div>
        <?php endif; ?>

        <?php if ($validToken && !$success): ?>
            <p class="welcome-text">
                Ciao <strong><?php echo htmlspecialchars($user['username']); ?></strong>, inserisci la tua nuova password.
            </p>

            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="form-group">
                    <label class="form-label">Nuova Password *
                        <input type="password" name="password" class="form-input" required minlength="8">
                    </label>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Conferma Nuova Password *
                        <input type="password" name="password_confirm" class="form-input" required minlength="8">
                    </label>
                </div>

                <div class="password-requirements">
                    <strong>Requisiti password:</strong>
                    <ul>
                        <li>Almeno 8 caratteri</li>
                        <li>Consigliato: usa lettere maiuscole, minuscole, numeri e simboli</li>
                    </ul>
                </div>

                <button type="submit" class="button btn-login">
                    Aggiorna Password
                </button>
            </form>
        <?php elseif (!$validToken && !$success): ?>
            <div class="center-container">
                <a href="forgot_password.php" class="button btn-login inline-btn">
                    Richiedi Nuovo Link
                </a>
            </div>
        <?php endif; ?>

        <a href="index.php" class="return-home">Torna alla Home</a>
    </div>

</body>
</html>