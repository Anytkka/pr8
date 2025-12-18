<?php
session_start();
include("../settings/connect_datebase.php");

header('Content-Type: text/plain; charset=utf-8');

// Получаем данные
$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

// Проверка входных данных
if (empty($login) || empty($password)) {
    echo 'empty';
    exit;
}

// Ищем пользователя
$query_user = $mysqli->query("SELECT * FROM `users` WHERE `login`='".$mysqli->real_escape_string($login)."'");
$id = -1;
$db_password = '';

if($user_read = $query_user->fetch_row()) {
    $id = $user_read[0];
    $db_password = $user_read[2]; // пароль из базы
}

if ($id == -1) {
    echo 'not_found';
    exit;
}

// Проверяем пароль
if ($password == $db_password) {
    $_SESSION['user'] = $id;
    echo $id; // возвращаем ID пользователя
} else {
    echo 'wrong';
}
?>