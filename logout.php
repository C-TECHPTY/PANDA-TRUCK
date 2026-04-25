<?php
// logout.php - Cerrar sesión
session_start();
session_destroy();
header('Location: login.php');
exit;
?>