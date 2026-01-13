<?php
session_start();
include("../settings/connect_datebase.php");

header('Content-Type: text/plain; charset=utf-8');

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    echo "empty_fields";
    exit;
}

// Ищем пользователя
$stmt = $mysqli->prepare("SELECT id, password, email FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "user_not_found";
    exit;
}

// Проверяем пароль
if (!password_verify($password, $user['password'])) {
    if ($password !== $user['password']) {
        echo "wrong_password";
        exit;
    }
}


if (empty($user['email'])) {
    echo "no_email";
    exit;
}


$_SESSION['preuser'] = $user['id'];
$_SESSION['user_email'] = $user['email'];


echo "success";
?>