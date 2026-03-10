<?php
session_start();
include("../settings/connect_datebase.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$login = $_POST['login'] ?? '';

if (empty($login)) {
    echo -1;
    exit;
}

// Логирование
$log_file = dirname(__DIR__) . '/recovery_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Начало восстановления для: $login\n", FILE_APPEND);

$login_escaped = $mysqli->real_escape_string($login);

// Ищем пользователя
$query_user = $mysqli->query("
    SELECT * FROM `users` 
    WHERE `login` = '$login_escaped' 
       OR `email` = '$login_escaped'
");

$id = -1;
$user_data = null;

if ($query_user && $query_user->num_rows > 0) {
    $user_data = $query_user->fetch_assoc();
    $id = $user_data['id'];
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Найден пользователь ID: $id\n", FILE_APPEND);
} else {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пользователь не найден\n", FILE_APPEND);
    echo -1;
    exit;
}

function PasswordGeneration() {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    $length = 10;
    $password = "";
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

function sendSMTP($to, $new_password, $login) {
    $log_file = dirname(__DIR__) . '/recovery_log.txt';
    
    // Данные для SMTP Яндекса
    $smtp_host = 'smtp.yandex.ru';
    $smtp_port = 587;
    $smtp_user = 'eltyshevaanna2006@yandex.ru';
    $smtp_pass = 'wcvpzyjlenbhtdet';
    $from_email = 'eltyshevaanna2006@yandex.ru';
    $from_name = 'Система безопасности';
    
    $newline = "\r\n";
    
    try {
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 30);
        
        if (!$socket) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка подключения SMTP: $errstr\n", FILE_APPEND);
            return false;
        }
        
        stream_set_timeout($socket, 30);
        
        
        $response = fgets($socket, 512);
        
        
        fputs($socket, "EHLO localhost" . $newline);
        while ($line = fgets($socket, 512)) {
            if (substr($line, 3, 1) == ' ') break;
        }
        
        
        fputs($socket, "STARTTLS" . $newline);
        $response = fgets($socket, 512);
        
        
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        
        fputs($socket, "EHLO localhost" . $newline);
        while ($line = fgets($socket, 512)) {
            if (substr($line, 3, 1) == ' ') break;
        }
        
        
        fputs($socket, "AUTH LOGIN" . $newline);
        fgets($socket, 512);
        
        fputs($socket, base64_encode($smtp_user) . $newline);
        fgets($socket, 512);
        
        fputs($socket, base64_encode($smtp_pass) . $newline);
        $response = fgets($socket, 512);
        
        
        fputs($socket, "MAIL FROM: <$from_email>" . $newline);
        fgets($socket, 512);
        
        
        fputs($socket, "RCPT TO: <$to>" . $newline);
        fgets($socket, 512);
        
        
        fputs($socket, "DATA" . $newline);
        fgets($socket, 512);
        
        // Формируем письмо
        $subject = "Восстановление пароля";
        $message = "Здравствуйте, $login!\r\n\r\n";
        $message .= "Ваш новый пароль для входа в систему: $new_password\r\n\r\n";
        $message .= "Войти в систему: http://" . $_SERVER['HTTP_HOST'] . "/login.php\r\n\r\n";
        $message .= "После входа рекомендуем сменить пароль в личном кабинете.\r\n\r\n";
        $message .= "Если вы не запрашивали восстановление пароля, проигнорируйте это письмо.\r\n\r\n";
        $message .= "---\r\n";
        $message .= "Система безопасности веб-приложений\r\n";
        $message .= "Пермский авиационный техникум им. А. Д. Швецова\r\n";
        
        $headers = "From: $from_name <$from_email>" . $newline;
        $headers .= "To: $to" . $newline;
        $headers .= "Reply-To: $from_email" . $newline;
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=" . $newline;
        $headers .= "Content-Type: text/plain; charset=utf-8" . $newline;
        $headers .= "MIME-Version: 1.0" . $newline;
        
        // Отправляем
        fputs($socket, $headers . $newline . $message . $newline . "." . $newline);
        $response = fgets($socket, 512);
        
        
        fputs($socket, "QUIT" . $newline);
        fclose($socket);
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | Письмо успешно отправлено на $to\n", FILE_APPEND);
        return true;
        
    } catch (Exception $e) {
        if (isset($socket) && $socket) {
            fclose($socket);
        }
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

if ($id != -1) {
    // Создаем новый пароль
    $new_password = PasswordGeneration();
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Новый пароль: $new_password\n", FILE_APPEND);
    
    // Обновляем пароль в базе
    $update_query = "UPDATE `users` SET `password` = '$hashed_password' WHERE `id` = $id";
    
    if ($mysqli->query($update_query)) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Пароль обновлен в БД\n", FILE_APPEND);
        
        // Определяем email для отправки
        $email_to = $user_data['email'] ?? '';
        
        if (empty($email_to)) {
            // Если email пустой, используем логин, если он похож на email
            if (strpos($user_data['login'], '@') !== false) {
                $email_to = $user_data['login'];
            }
        }
        
        if (!empty($email_to)) {
            // Отправляем через SMTP
            $sent = sendSMTP($email_to, $new_password, $user_data['login']);
            
            if ($sent) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Письмо отправлено на $email_to\n", FILE_APPEND);
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " - Ошибка отправки письма\n", FILE_APPEND);
            }
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " - Нет email для отправки\n", FILE_APPEND);
        }
    }
}

echo $id;
?>