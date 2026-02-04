<?php
session_start();
require_once 'api/db_connect.php';

// Protezione della pagina: se non loggato, reindirizza
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];
$defaultCount = 5; // numero di avatar

// Gestione messaggi di feedback
$successMsg = isset($_GET['success']) ? "Profilo aggiornato con successo!" : "";
$errorMsg = "";
if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'pass_errata': $errorMsg = "La password attuale non è corretta."; break;
        case 'pass_match': $errorMsg = "Le nuove password non coincidono."; break;
        case 'pass_corta': $errorMsg = "La nuova password deve essere di almeno 8 caratteri."; break;
        case 'tipo_file': $errorMsg = "Tipo file non valido."; break;
        case 'database': $errorMsg = "Errore del database. Riprova più tardi."; break;
        default: $errorMsg = "Si è verificato un errore imprevisto."; break;
    }
}

// Recupero dati utente
$userQuery = $pdo->prepare("SELECT username, nome, cognome, citta, bio, livello, foto_profilo FROM users WHERE id = ?");
$userQuery->execute([$userId]);
$user = $userQuery->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Recupero Badge
$badges = $pdo->prepare("
    SELECT b.nome, b.icona, b.descrizione
    FROM badges b
    JOIN user_badges ub ON b.id = ub.badge_id
    WHERE ub.user_id = ?
");
$badges->execute([$userId]);
$userBadges = $badges->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
    <title>Profilo - <?php echo htmlspecialchars($user['username']); ?></title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>

    <div class="profile-wrapper">
        <header class="profile-header">
            <h1>Il Mio Profilo</h1>
            <div class="header-actions">
                <a href="index.php" class="auth-btn auth-btn-nav">Home</a>
                <a href="api/logout.php" class="auth-btn auth-btn-logout">Logout</a>
            </div>
        </header>

        <?php if($successMsg): ?>
            <div class="alert alert-success">
                <?php echo $successMsg; ?>
            </div>
        <?php endif; ?>

        <?php if($errorMsg): ?>
            <div class="alert alert-error">
                <?php echo $errorMsg; ?>
            </div>
        <?php endif; ?>

        <div class="main-content">
            <aside class="sidebar">
                <div class="profile-pic-container">
                    <?php
                        $fotoPath = "assets/uploads/default1.png";
                        if (!empty($user['foto_profilo'])) {
                            $candidate = "assets/uploads/" . $user['foto_profilo'];
                            if (file_exists($candidate)) { $fotoPath = $candidate; }
                        }
                    ?>
                    <img src="<?php echo $fotoPath . "?t=" . time(); ?>" class="profile-pic" alt="Avatar">
                </div>
                
                <section class="badge-section">
                    <h3>I tuoi Traguardi</h3>
                    <div class="badge-container">
                        <?php if(count($userBadges) > 0): ?>
                            <?php foreach($userBadges as $b): ?>
                                <div class="badge-item" title="<?php echo htmlspecialchars($b['descrizione']); ?>">
                                    <span class="badge-icon">🏆</span>
                                    <span class="badge-name"><?php echo htmlspecialchars($b['nome']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-badges-text">Gioca per sbloccare i tuoi primi badge!</p>
                        <?php endif; ?>
                    </div>
                </section>
            </aside>

            <main class="form-section">
                <form action="api/update_profile.php" method="POST">
                    
                    <div class="section-block">
                        <h2>Informazioni Personali</h2>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled class="input-readonly">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="cognome">Cognome</label>
                                <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($user['cognome'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="citta">Città</label>
                            <input type="text" id="citta" name="citta" value="<?php echo htmlspecialchars($user['citta'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="bio">Biografia</label>
                            <textarea id="bio" name="bio" rows="3"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="section-block">
                        <h2>Esperienza</h2>
                        <div class="level-choices">
                            <?php 
                                $levels = ['beginner' => 'Principiante', 'intermediate' => 'Intermedio', 'expert' => 'Esperto'];
                                $currentLevel = strtolower($user['livello'] ?? 'beginner');
                                foreach($levels as $val => $label): 
                                    $checked = ($currentLevel === $val) ? 'checked' : '';
                            ?>
                                <label class="level-option">
                                    <input type="radio" name="skill_level" value="<?php echo $val; ?>" <?php echo $checked; ?>>
                                    <span class="level-card"><?php echo $label; ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="security-section">
                        <h2>Sicurezza & Password</h2>
                        <p>Lascia vuoto se non vuoi cambiare la password.</p>
                        
                        <div class="form-group">
                            <label for="current_password">Password Attuale</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Inserisci la password attuale">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">Nuova Password</label>
                                <input type="password" id="new_password" name="new_password" placeholder="Minimo 8 caratteri">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Conferma Nuova Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Ripeti nuova password">
                            </div>
                        </div>
                    </div>

                    <div class="section-block">
                        <h2>Scegli il tuo Avatar</h2>
                        <div class="avatar-choices">
                            <?php
                                $currentAvatar = $user['foto_profilo'] ?? '';
                                for ($i = 1; $i <= $defaultCount; $i++) {
                                    $fname = "default{$i}.png";
                                    $checked = ($currentAvatar === $fname) ? 'checked' : '';
                                    echo "<label class='avatar-option' style='cursor:pointer;'>";
                                    echo "<input type='radio' name='avatar' value='{$fname}' {$checked}> ";
                                    echo "<img src='assets/uploads/{$fname}' alt='Avatar {$i}' class='avatar-thumb'>";
                                    echo "</label>";
                                }
                            ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-save">Salva Tutte le Modifiche</button>
                    </div>
                </form>
            </main>
        </div>

        <div class="gdpr-zone">
            <p class="gdpr-text">
                <strong>Privacy & GDPR:</strong> In conformità con il Regolamento UE 2016/679 (GDPR), 
                puoi richiedere la cancellazione permanente del tuo account e dei tuoi dati dai nostri sistemi.
            </p>
            <button id="btn-delete-account" class="btn-delete">Elimina Account Permanentemente</button>
        </div>
    </div>

    <script src="js/profile.js"></script>
</body>
</html>