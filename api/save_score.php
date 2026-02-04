<?php
header('Content-Type: application/json');
ini_set('display_errors', 0); 
error_reporting(E_ALL);

session_start();

try {
    require_once 'db_connect.php';

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Utente non autenticato');
    }

    // Trasformiamo in interi per poterli usare nelle stringhe SQL in sicurezza
    $userId = intval($_SESSION['user_id']);
    $punteggio = 100;
    $nuoviBadge = []; 
    
    // Validazione Token (Dati esterni - qui usiamo prepared statement dopo)
    if (!isset($data['win_token']) || empty($data['win_token'])) {
        throw new Exception('Token di sicurezza mancante');
    }
    
    // Anti-Spam
    $sqlLimit = "SELECT MAX(data_partita) FROM scores 
                 WHERE user_id = $userId 
                 AND data_partita > DATE_SUB(NOW(), INTERVAL 5 SECOND)";
    $resLimit = $pdo->query($sqlLimit)->fetchColumn();
    
    if ($resLimit) {
        throw new Exception('Salvataggio troppo frequente.');
    }
    
    $pdo->beginTransaction();

    // Inserimento Punteggio
    $sqlScore = "INSERT INTO scores (user_id, punteggio, data_partita) 
                 VALUES ($userId, $punteggio, NOW())";
    $pdo->exec($sqlScore);

    // Badge 1: Prima Vittoria
    $sqlB1 = "INSERT IGNORE INTO user_badges (user_id, badge_id, ottenuto_il) 
              VALUES ($userId, 1, NOW())";
    if ($pdo->exec($sqlB1) > 0) {
        $nuoviBadge[] = "Prima Vittoria!";
    }

    // Badge2:  Veterano
    $sqlTot = "SELECT SUM(punteggio) FROM scores WHERE user_id = $userId";
    $puntiTotali = (int)$pdo->query($sqlTot)->fetchColumn();

    if ($puntiTotali >= 1000) {
        $sqlB2 = "INSERT IGNORE INTO user_badges (user_id, badge_id, ottenuto_il) 
                  VALUES ($userId, 2, NOW())";
        if ($pdo->exec($sqlB2) > 0) {
            $nuoviBadge[] = "Veterano (1000+ punti)!";
        }
    }

    // Badge3: Campione
    $sqlMaxAltri = "SELECT IFNULL(MAX(tot), 0) FROM (
                        SELECT SUM(punteggio) as tot 
                        FROM scores 
                        WHERE user_id <> $userId 
                        GROUP BY user_id
                    ) as classifica";
    $maxPunteggioAltri = (int)$pdo->query($sqlMaxAltri)->fetchColumn();

    if ($puntiTotali > $maxPunteggioAltri && $puntiTotali > 0) {
        $sqlB3 = "INSERT IGNORE INTO user_badges (user_id, badge_id, ottenuto_il) 
                  VALUES ($userId, 3, NOW())";
        if ($pdo->exec($sqlB3) > 0) {
            $nuoviBadge[] = "Campione della Classifica!";
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'punteggio_totale' => $puntiTotali,
        'badge_news' => !empty($nuoviBadge) ? "Badge Sbloccati: " . implode(", ", $nuoviBadge) : 'Nessun nuovo badge'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>