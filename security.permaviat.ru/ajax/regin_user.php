<?php
session_start();
include("../settings/connect_datebase.php");

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    echo -1;
    exit;
}
function CheckPassword($password) {
    $regex = '/^(?=.*[0-9])(?=.*[!@#$%^&*\-_=])(?=.*[A-Z])[0-9a-zA-Z!@#$%^&*\-_=]{8,}$/';
    return preg_match($regex, $password) === 1;
}


if (!CheckPassword($password)) {
    echo -2; 
    exit;
}


$stmt = $mysqli->prepare("SELECT id FROM `users` WHERE `login` = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo -3; 
    $stmt->close();
    exit;
}
$stmt->close();


$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("INSERT INTO `users` (`login`, `password`, `roll`) VALUES (?, ?, 0)");
$stmt->bind_param("ss", $login, $hashed_password);

if ($stmt->execute()) {
    $id = $stmt->insert_id; 
    

    $_SESSION['user'] = $id;
    echo $id;
} else {
    echo -4; 
}

$stmt->close();
?>