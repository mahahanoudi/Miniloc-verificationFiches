<?php
session_start();
session_unset();
session_destroy();
header("Location: traitement_index.php");
exit;
?>
