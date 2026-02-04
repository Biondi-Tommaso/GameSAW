<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Pagina Non Trovata - Connect4 Hub</title>
    <link rel="stylesheet" href="css/404.css">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1>Oops! Pagina Non Trovata</h1>
        <p class="error-message">
            La pagina che stai cercando non esiste o è stata spostata.
        </p>
        
        <div class="suggestions">
            <h3>Cosa puoi fare:</h3>
            <ul>
                <li>Verifica di aver digitato correttamente l'URL</li>
                <li>Torna alla homepage e naviga da lì</li>
                <li>Usa il menu di navigazione</li>
            </ul>
        </div>

        <div class="button-group">
            <a href="index.php" class="btn btn-primary">
                Torna alla Home
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <span>←</span> Pagina Precedente
            </a>
        </div>

        <div class="illustration">
            <svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
                <circle cx="100" cy="100" r="80" fill="#667eea" opacity="0.1"/>
                <circle cx="100" cy="100" r="60" fill="#764ba2" opacity="0.1"/>
                <text x="100" y="120" font-size="60" text-anchor="middle" fill="#667eea" opacity="0.3">?</text>
            </svg>
        </div>
    </div>
</body>
</html>