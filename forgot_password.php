<?php
session_start();
require_once 'api/db_connect.php';
require_once 'api/send_password_reset_email.php';

// Se l'utente è già loggato, reindirizza alla home
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

// Mostra messaggi dopo redirect
if (isset($_GET['sent']) && $_GET['sent'] == '1') {
    $success = "Se l'email esiste nel nostro database, riceverai un link per il reset della password.";
}
if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'captcha':
            $error = "Verifica captcha fallita. Riprova.";
            break;
        case 'email':
            $error = "Formato email non valido!";
            break;
        case 'unverified':
            $error = "Si è verificato un errore nell'invio dell'email. Riprova più tardi.";
            break;
        case 'send':
            $error = "Si è verificato un errore nell'invio dell'email. Riprova più tardi.";
            break;
        default:
            $error = "Si è verificato un errore. Riprova più tardi.";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Verifica reCAPTCHA
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $recaptchaSecret = getenv('RECAPTCHA_SECRET') ?: getenv('RECAPTCHA_SECRET_KEY') ?: '';
    
    if (empty($recaptchaResponse)) {
        header("Location: forgot_password.php?error=captcha");
        exit();
    }
    
    $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $opts = [
        'http' => [
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'secret'   => $recaptchaSecret,
                'response' => $recaptchaResponse,
                'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ])
        ]
    ];
    $context = stream_context_create($opts);
    $resp = @file_get_contents($verifyUrl, false, $context);
    $respData = json_decode($resp, true);
    
    if (!$respData || !isset($respData['success']) || $respData['success'] !== true) {
        header("Location: forgot_password.php?error=captcha");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: forgot_password.php?error=email");
        exit();
    }
    
    $stmt = $pdo->prepare("SELECT id, username, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        if ($user['email_verified'] == 0) {
            header("Location: forgot_password.php?error=unverified");
            exit();
        }
        
        $reset_token = bin2hex(random_bytes(32));
        $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
        
        if ($stmt->execute([$reset_token, $token_expiry, $user['id']])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $reset_link = $protocol . $host . "/~s5797190/reset_password.php?token=" . $reset_token;
            
            if (send_password_reset_email($email, $user['username'], $reset_link)) {
                header("Location: forgot_password.php?sent=1");
                exit();
            } else {
                header("Location: forgot_password.php?error=send");
                exit();
            }
        }
    } else {
        header("Location: forgot_password.php?sent=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Recupero Password - Connect4 Hub</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/forgot_password.css">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

    <div class="auth-container">
        <h2>Password Dimenticata?</h2>
        
        <div class="info-box">
            Inserisci la tua email e ti invieremo un link per reimpostare la password.
        </div>

        <?php if($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
                <?php if(isset($_GET['debug'])): ?>
                    <br><span class="debug-text">Debug: <?php echo htmlspecialchars($_GET['debug']); ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST" id="resetForm">
            <div class="form-group">
                <label class="form-label">Email
                    <input type="email" name="email" class="form-input" required placeholder="la-tua-email@esempio.it">
                </label>
            </div>

            <?php 
            $recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: '';
            if ($recaptchaSiteKey):
            ?>
                <div class="captcha-container">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>
                </div>
            <?php endif; ?>

            <button type="submit" class="button btn-login" id="submitBtn">
                Invia Link di Reset
            </button>
        </form>

        <div class="auth-footer">
            Ti sei ricordato la password? <a href="login.php" class="auth-link">Accedi</a>
            <br>
            Non hai un account? <a href="register.php" class="auth-link">Registrati</a>
        </div>

        <a href="index.php" class="return-home">Torna alla Home</a>
    </div>

    <script>
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Invio in corso...';
        });
    </script>
</body>
</html>