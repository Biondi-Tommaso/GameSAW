<?php
session_start();
require_once 'api/db_connect.php';
require_once 'api/send_email.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $pass = $_POST['password'];
    $pass_confirm = $_POST['password_confirm'];

    // Verifica reCAPTCHA
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    $recaptchaSecret = getenv('RECAPTCHA_SECRET') ?: getenv('RECAPTCHA_SECRET_KEY') ?: '';
    
    if (empty($recaptchaResponse)) {
        $error = "Per favore, completa il captcha.";
    } else {
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query([
                    'secret'   => $recaptchaSecret,
                    'response' => $recaptchaResponse,
                    'remoteip' => $_SERVER['REMOTE_ADDR']
                ])
            ]
        ];
        $context  = stream_context_create($opts);
        $resp = file_get_contents($verifyUrl, false, $context);
        $respData = json_decode($resp, true);
        
        if (!$respData || !isset($respData['success']) || $respData['success'] !== true) {
            $error = "Verifica captcha fallita. Riprova.";
        }
    }

    if (empty($error)) {
        if ($pass !== $pass_confirm) {
            $error = "Le password non coincidono!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Formato email non valido!";
        } else {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->fetch()) {
                $error = "Email o Username già esistenti!";
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $avatar = $_POST['avatar'] ?? 'default1.png';
                $allowedAvatars = [];
                for ($i = 1; $i <= 5; $i++) $allowedAvatars[] = "default{$i}.png";
                if (!in_array($avatar, $allowedAvatars)) $avatar = $allowedAvatars[0];

                $verification_token = bin2hex(random_bytes(32));
                $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, nome, cognome, foto_profilo, email_verified, verification_token, token_expiry) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)");
                
                try {
                    if ($stmt->execute([$username, $email, $hash, $nome, $cognome, $avatar, $verification_token, $token_expiry])) {
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                        $host = $_SERVER['HTTP_HOST'];
                        $verification_link = $protocol . $host . "/~s5797190/verify_email.php?token=" . $verification_token;
                        
                        if (sendVerificationEmail($email, $username, $verification_link)) {
                            $success = "Registrazione completata! Controlla la tua email per verificare l'account.";
                            header("refresh:5;url=login.php");
                        } else {
                            $error = "Account creato, ma errore nell'invio email. Contatta il supporto.";
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Errore database: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Register - Connect4 Hub</title>

    <link rel="stylesheet" href="css/register.css">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

    <div class="auth-container">
        <h2>Join Us!</h2>
        
        <?php if($error): ?>
            <div class="error-msg alert-box">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success-msg alert-box">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Nome <span class="optional-label">(Opz)</span>
                        <input type="text" name="nome" placeholder="Mario" value="<?php echo htmlspecialchars($nome ?? ''); ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label>Cognome <span class="optional-label">(Opz)</span>
                        <input type="text" name="cognome" placeholder="Rossi" value="<?php echo htmlspecialchars($cognome ?? ''); ?>">
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Username *
                    <input type="text" name="username" required placeholder="Es. Mario88" value="<?php echo htmlspecialchars($username ?? ''); ?>">
                </label>
            </div>

            <div class="form-group">
                <label>Email *
                    <input type="email" name="email" required placeholder="email@esempio.it" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </label>
            </div>

            <div class="form-group">
                <label>Password *
                    <input type="password" name="password" required>
                </label>
            </div>

            <div class="form-group">
                <label>Conferma Password *
                    <input type="password" name="password_confirm" required>
                </label>
            </div>

            <div class="form-group">
                <label>Scegli Avatar di default</label>
                <div class="avatar-choices">
                    <?php
                        for ($i = 1; $i <= 5; $i++) {
                            $fname = "default{$i}.png";
                            $path = "assets/uploads/" . $fname;
                            $checked = ($i === 1) ? 'checked' : '';
                            echo '<label class="avatar-option">';
                            echo '<input type="radio" name="avatar" value="' . $fname . '" ' . $checked . '>';
                            echo '<img src="' . $path . '" alt="Avatar ' . $i . '" class="avatar-thumb">';
                            echo '</label>';
                        }
                    ?>
                </div>
            </div>

            <?php 
            $recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY') ?: '';
            if ($recaptchaSiteKey):
            ?>
                <div class="captcha-wrapper">
                    <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($recaptchaSiteKey); ?>"></div>
                </div>
            <?php endif; ?>

            <button aria-label="register" type="submit" class="button-submit">
                Register
            </button>
        </form>

        <div class="auth-footer">
            Hai già un account? <a href="login.php">Accedi qui</a>
            <br><br>
            <a href="index.php" class="back-home">Torna alla Home</a>
        </div>
    </div>

</body>

</html>