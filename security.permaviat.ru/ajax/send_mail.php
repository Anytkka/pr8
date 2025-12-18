<?php
session_start();
include("../settings/connect_datebase.php");


error_reporting(E_ALL);
ini_set('display_errors', 1);


$log_file = dirname(__DIR__) . '/email_log.txt';
$debug_file = dirname(__DIR__) . '/mail_debug.txt';


if (!isset($_SESSION['preuser'])) {
    echo "no_session";
    exit;
}

$user_id = $_SESSION['preuser'];


$query = $mysqli->query("SELECT email, login FROM users WHERE id = $user_id");
if (!$query || !($user = $query->fetch_assoc())) {
    echo "user_not_found";
    exit;
}

if (empty($user['email'])) {
    echo "no_email";
    exit;
}

$email = trim($user['email']);
$login = $user['login'];


file_put_contents($log_file, 
    date('Y-m-d H:i:s') . " | Sending mail for user_id: $user_id | Login: $login | Email: $email\n", 
    FILE_APPEND
);


$code = sprintf("%06d", rand(100000, 999999));


file_put_contents($log_file, 
    date('Y-m-d H:i:s') . " | Generated code: $code for user_id: $user_id\n", 
    FILE_APPEND
);

$_SESSION["code"] = $code;
$_SESSION["code_time"] = time();

// Сохраняем код в БД
$stmt = $mysqli->prepare("UPDATE users SET verification_code = ?, code_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id = ?");
$stmt->bind_param("si", $code, $user_id);
if ($stmt->execute()) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | Code $code saved to DB for user_id $user_id\n", FILE_APPEND);
    
  
    $check = $mysqli->query("SELECT verification_code FROM users WHERE id = $user_id");
    if ($check && $row = $check->fetch_assoc()) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | Verified: Code in DB is: " . $row['verification_code'] . "\n", FILE_APPEND);
    }
} else {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | ERROR saving code to DB: " . $stmt->error . "\n", FILE_APPEND);
}
$stmt->close();


function sendSMTP($to, $subject, $message, $from_email, $from_name, $log_file) {
  
    $smtp_host = 'smtp.yandex.ru';
    $smtp_port = 587;
    $smtp_user = 'eltyshevaanna2006@yandex.ru';
    $smtp_pass = 'wcvpzyjlenbhtdet';
    
    $timeout = 30;
    $newline = "\r\n";
    
    try {
        
        $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, $timeout);
        
        if (!$socket) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка подключения SMTP: $errstr ($errno)\n", FILE_APPEND);
            return "Ошибка подключения: $errstr ($errno)";
        }
        
        stream_set_timeout($socket, $timeout);
        
       
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка приветствия SMTP: $response\n", FILE_APPEND);
            return "Ошибка приветствия: $response";
        }
        
        
        fputs($socket, "EHLO localhost" . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка EHLO: $response\n", FILE_APPEND);
            return "Ошибка EHLO: $response";
        }
        
        while (substr(fgets($socket, 512), 3, 1) == '-') {}
        
      
        fputs($socket, "STARTTLS" . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '220') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка STARTTLS: $response\n", FILE_APPEND);
            return "Ошибка STARTTLS: $response";
        }
        
    
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка шифрования TLS\n", FILE_APPEND);
            return "Ошибка шифрования TLS";
        }
        
        
        fputs($socket, "EHLO localhost" . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка EHLO после TLS: $response\n", FILE_APPEND);
            return "Ошибка EHLO после TLS: $response";
        }
        
        
        while (substr(fgets($socket, 512), 3, 1) == '-') {}
        
       
        fputs($socket, "AUTH LOGIN" . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка AUTH: $response\n", FILE_APPEND);
            return "Ошибка AUTH: $response";
        }
        
        
        fputs($socket, base64_encode($smtp_user) . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '334') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка логина: $response\n", FILE_APPEND);
            return "Ошибка логина: $response";
        }
        
      
        fputs($socket, base64_encode($smtp_pass) . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '235') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка пароля: $response\n", FILE_APPEND);
            return "Ошибка пароля: $response";
        }
        
     
        fputs($socket, "MAIL FROM: <$from_email>" . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка MAIL FROM: $response\n", FILE_APPEND);
            return "Ошибка MAIL FROM: $response";
        }
        
       
        fputs($socket, "RCPT TO: <$to>" . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка RCPT TO: $response\n", FILE_APPEND);
            return "Ошибка RCPT TO: $response";
        }
        
     
        fputs($socket, "DATA" . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '354') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка DATA: $response\n", FILE_APPEND);
            return "Ошибка DATA: $response";
        }
        
        
        $headers = "From: $from_name <$from_email>" . $newline;
        $headers .= "To: $to" . $newline;
        $headers .= "Reply-To: $from_email" . $newline;
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=" . $newline;
        $headers .= "Content-Type: text/plain; charset=utf-8" . $newline;
        $headers .= "MIME-Version: 1.0" . $newline;
        $headers .= "X-Mailer: PHP/" . phpversion() . $newline;
        
        
        fputs($socket, $headers . $newline . $message . $newline . "." . $newline);
        $response = fgets($socket, 512);
        if (substr($response, 0, 3) != '250') {
            fclose($socket);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " | Ошибка отправки письма: $response\n", FILE_APPEND);
            return "Ошибка отправки письма: $response";
        }
        
        
        fputs($socket, "QUIT" . $newline);
        fclose($socket);
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | Письмо успешно отправлено через SMTP\n", FILE_APPEND);
        return true;
        
    } catch (Exception $e) {
        if (isset($socket) && $socket) {
            fclose($socket);
        }
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | Исключение при отправке SMTP: " . $e->getMessage() . "\n", FILE_APPEND);
        return "Исключение: " . $e->getMessage();
    }
}

$from_email = "eltyshevaanna2006@yandex.ru";
$from_name = "Система безопасности";


$subject = "Код подтверждения входа - Система безопасности";
$message = "Здравствуйте, $login!\r\n\r\n";
$message .= "Ваш код подтверждения для входа: $code\r\n\r\n";
$message .= "Код действителен в течение 10 минут.\r\n\r\n";
$message .= "Если вы не запрашивали этот код, проигнорируйте это письмо.\r\n\r\n";
$message .= "---\r\n";
$message .= "Система безопасности веб-приложений\r\n";
$message .= "Пермский авиационный техникум им. А. Д. Швецова\r\n";

$email_result = sendSMTP($email, $subject, $message, $from_email, $from_name, $log_file);

if ($email_result === true) {
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | УСПЕХ: Код $code отправлен на $email\n", FILE_APPEND);
    echo "success";
} else {
    
    file_put_contents($log_file, date('Y-m-d H:i:s') . " | ОШИБКА SMTP: $email_result\n", FILE_APPEND);
    
    
    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Reply-To: $from_email\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    $mail_result = mail($email, $subject, $message, $headers);
    
    if ($mail_result) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | УСПЕХ через mail(): Код $code отправлен\n", FILE_APPEND);
        echo "success";
    } else {
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | ВСЕ методы отправки не сработали\n", FILE_APPEND);
        
        
        echo "test_mode|$code|$email";
        
       
        $error_info = error_get_last();
        file_put_contents($debug_file, 
            "[" . date('Y-m-d H:i:s') . "] Ошибка отправки:\n" .
            "SMTP ошибка: $email_result\n" .
            "Функция mail(): " . ($mail_result ? 'true' : 'false') . "\n" .
            "Последняя ошибка: " . print_r($error_info, true) . "\n\n",
            FILE_APPEND
        );
    }
}
?>