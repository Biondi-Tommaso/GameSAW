<?php
require_once 'api/db_connect.php';
require_once 'api/send_email.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($error)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Formato email non valido!";
        } else {
            $stmt = $pdo->prepare("SELECT id, username, email_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                if ($user['email_verified'] == 0) {
                    $error = "Devi prima verificare la tua email. Controlla la tua casella di posta.";
                } else {
                    $reset_token = bin2hex(random_bytes(32));
                    $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    $user_id = (int)$user['id'];
                    
                    $sql = "UPDATE users 
                            SET reset_token = '$reset_token', 
                                reset_token_expiry = '$token_expiry' 
                            WHERE id = $user_id";
                    
                    if ($pdo->query($sql)) {
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                        $host = $_SERVER['HTTP_HOST'];
                        $reset_link = $protocol . $host . "/~s5797190/reset_password.php?token=" . $reset_token;
                        $emailSent = send_password_reset_email($email, $user['username'], $reset_link);
                        
                        if ($emailSent) {
                            $success = "Se l'email esiste nel nostro database, riceverai un link per il reset della password.";
                        } else {
                            $error = "Si è verificato un errore nell'invio dell'email. Riprova più tardi.";
                        }
                    } else {
                        $error = "Si è verificato un errore. Riprova più tardi.";
                    }
                }
            } else {
                $success = "Se l'email esiste nel nostro database, riceverai un link per il reset della password.";
            }
        }
    }
}
?>