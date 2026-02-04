<?php
session_start();
require_once 'api/db_connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['email'], $_POST['password'])) {
        $error = "Richiesta non valida.";
        exit();
    }
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);
    $remember = isset($_POST['remember']);

    // Verifica reCAPTCHA
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $recaptchaSecret = getenv('RECAPTCHA_SECRET') ?: getenv('RECAPTCHA_SECRET_KEY') ?: '';
    
    if (empty($recaptchaResponse) || empty($recaptchaSecret)) {
        $error = "Verifica captcha fallita. Riprova.";
    } else {
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $resp = file_get_contents($verifyUrl . '?secret=' . urlencode($recaptchaSecret) . '&response=' . urlencode($recaptchaResponse) . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR'] ?? ''));
        $respData = json_decode($resp, true);
        if (!($respData && isset($respData['success']) && $respData['success'] === true)) {
            $error = "Verifica captcha fallita. Riprova.";
        }
    }
    
    if (empty($error)) {
        $stmt = $pdo->prepare("SELECT id, password_hash, email_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($pass, $user['password_hash'])) {
            if ($user['email_verified'] == 0) {
                $error = "Devi verificare la tua email prima di accedere. Controlla la tua casella di posta.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];

                if ($remember) {
                    $selector = bin2hex(random_bytes(6));
                    $validator = bin2hex(random_bytes(32));
                    $cookie_value = $selector . ':' . $validator;
                    $validator_hash = hash('sha256', $validator);
                    
                    $sql = "UPDATE users SET remember_selector = ?, remember_validator = ? WHERE id = ?";
                    $pdo->prepare($sql)->execute([$selector, $validator_hash, $user['id']]);

                    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                    setcookie("remember_me", $cookie_value, [
                        'expires' => time() + (30 * 24 * 60 * 60),
                        'path' => '/',
                        'secure' => $isSecure,
                        'httponly' => true,
                        'samesite' => 'Lax'
                    ]);
                }

                header("Location: index.php");
                exit();
            }
        } else {
            $error = "Email o Password errati!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Connect4 Hub</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
</head>
<body>

    <div class="auth-container">
        <h2>Welcome Back!</h2>

        <?php if($error): ?>
            <div class="error-msg alert-box">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php $recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: ''; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label class="form-label">Email
                    <input type="email" name="email" class="form-input" required>
                </label>
            </div>
            
            <div class="form-group">
                <label class="form-label">Password
                    <input type="password" name="password" class="form-input" required>
                </label>
            </div>

            <div class="form-options">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember"> Ricordami
                </label>
            </div>

            <div class="form-options">
                 <a href="forgot_password.php" class="auth-link secondary-link">Password dimenticata?</a>
            </div>

            <?php if ($recaptchaSiteKey): ?>
                <div class="captcha-wrapper">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>
                </div>
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <?php endif; ?>

            <button aria-label="login" type="submit" class="button btn-login">
                Login
            </button>
        </form>

        <div class="auth-footer">
            Non hai un account? <a href="register.php" class="auth-link">Registrati ora</a>
        </div>

        <a href="index.php" class="return-home">Torna alla Home</a>
    </div>

</body>
</html>