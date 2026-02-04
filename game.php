<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); 
}


$mode = $_GET['mode'] ?? 'local';
$match_id = isset($_GET['match_id']) && is_numeric($_GET['match_id']) 
            ? (int)$_GET['match_id'] 
            : null;

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forza 4 </title>
    <link rel="stylesheet" href="css/game.css">
    <link rel="icon" href="assets/uploads/favicon.ico" type="image/x-icon" />
</head>
<body>

    <div class="game-container">
        
        <div id="status">Inizia la partita!</div>
        
        <div class="board-wrapper">
            <div id="game-board">
                </div>
        </div>
        
        <div class="button-container">
            <form action="api/leave.php" method="post" id="leave-form">
                <input type="hidden" name="match_id" value="<?php echo htmlspecialchars($match_id); ?>">
                <button type="submit" class="button">Abbandona</button>
            </form>
        </div>

    </div>

    <script>
        const GAME_MODE = "<?php echo htmlspecialchars($mode); ?>";
        const USER_ID = <?php echo json_encode($_SESSION['user_id']); ?>;
        const MATCH_ID = "<?php echo htmlspecialchars($match_id); ?>";
    </script>
    
    <script src="js/game.js"></script>

</body>
</html>