<?php
session_start();
include("../settings/connect_datebase.php");

if (isset($_SESSION['user'])) {

    $stmt = $mysqli->prepare("UPDATE users SET session_token = NULL WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user']);
    $stmt->execute();
    $stmt->close();
}

session_destroy();
?>