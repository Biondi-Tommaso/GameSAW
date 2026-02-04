<?php
require_once __DIR__ . '/env_loader.php';

// Carica il file .env (se non è già stato caricato)
if (!getenv('SENDGRID_API_KEY')) {
    loadEnv(__DIR__ . '/../../.env');
}

function sendVerificationEmail($to_email, $username, $verification_link) {
    $api_key = getenv('SENDGRID_API_KEY');
    
    // Debug: verifica se la chiave esiste
    if (empty($api_key)) {
        error_log("ERRORE: SENDGRID_API_KEY non trovata nel file .env");
        return false;
    }
    
    $data = [
        'personalizations' => [[
            'to' => [['email' => $to_email]],
            'subject' => 'Verifica il tuo account - Connect4 Hub'
        ]],
        'from' => ['email' => 'barbarascoh@gmail.com', 'name' => 'Connect4 Hub'],
        'content' => [[
            'type' => 'text/html',
            'value' => "
                <h2>Ciao $username!</h2>
                <p>Grazie per esserti registrato a Connect4 Hub.</p>
                <p>Clicca sul link qui sotto per verificare la tua email:</p>
                <a href='$verification_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Verifica Email</a>
                <p>Oppure copia questo link nel browser: $verification_link</p>
                <p>Il link scadrà tra 24 ore.</p>
            "
        ]]
    ];
    
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);                        // imposto il metodo POST (di default curl usa GET)
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // definisco il corpo della richiesta
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);           // catturo la risposta come string
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    

    curl_close($ch);
    
    return $httpCode == 202; // SendGrid ritorna 202 per successo
}

function send_password_reset_email($to_email, $username, $reset_link) {
    $api_key = getenv('SENDGRID_API_KEY');
    
    if (empty($api_key)) {
        error_log("ERRORE: SENDGRID_API_KEY non trovata nel file .env");
        return false;
    }
    
    $data = [
        'personalizations' => [[
            'to' => [['email' => $to_email]],
            'subject' => 'Reset della password - Connect4 Hub'
        ]],
        'from' => ['email' => 'barbarascoh@gmail.com', 'name' => 'Connect4 Hub'],
        'content' => [[
            'type' => 'text/html',
            'value' => "
                <h2>Ciao $username!</h2>
                <p>Hai richiesto il reset della tua password.</p>
                <p>Clicca sul link qui sotto per reimpostare la password:</p>
                <a href='$reset_link' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Reset Password</a>
                <p>Oppure copia questo link nel browser: $reset_link</p>
                <p>Il link scadrà tra 1 ora.</p>
                <p>Se non hai richiesto il reset, ignora questa email.</p>
            "
        ]]
    ];
    
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    
    curl_close($ch);
    
    return $httpCode == 202;
}