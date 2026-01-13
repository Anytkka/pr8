<?php
session_start();
include("../settings/connect_datebase.php");

header('Content-Type: text/plain; charset=utf-8');

$login = trim($_POST['login'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($email) || empty($password)) {
    echo "empty_fields";
    exit;
}

// Проверка email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "invalid_email";
    exit;
}


function CheckPassword($pass) {
    if (strlen($pass) < 8) return false;
    if (!preg_match('/\d/', $pass)) return false;
    if (!preg_match('/[A-Z]/', $pass)) return false;
    if (!preg_match('/[!@#$%^&*\-_=]/', $pass)) return false;
    return true;
}

if (!CheckPassword($password)) {
    echo "weak_password";
    exit;
}


$check_login = $mysqli->query("SELECT id FROM users WHERE login = '".$mysqli->real_escape_string($login)."'");
if ($check_login->num_rows > 0) {
    echo "user_exists";
    exit;
}

$check_email = $mysqli->query("SELECT id FROM users WHERE email = '".$mysqli->real_escape_string($email)."'");
if ($check_email->num_rows > 0) {
    echo "email_exists";
    exit;
}


$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$mysqli->query("INSERT INTO users (login, email, password, roll, password_changed_at) VALUES (
    '".$mysqli->real_escape_string($login)."',
    '".$mysqli->real_escape_string($email)."',
    '".$mysqli->real_escape_string($hashed_password)."',
    0,
    NOW()
)");

echo "success";
?>