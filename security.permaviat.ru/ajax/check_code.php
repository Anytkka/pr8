<?php
session_start();
include("../settings/connect_datebase.php");

header('Content-Type: text/plain; charset=utf-8');

$log_file = dirname(__DIR__) . '/code_check_log.txt';

file_put_contents($log_file, "\n=== " . date('Y-m-d H:i:s') . " ===\n", FILE_APPEND);

if (!isset($_SESSION['preuser'])) {
    file_put_contents($log_file, "ERROR: no_session\n", FILE_APPEND);
    echo "no_session";
    exit;
}

if (!isset($_POST["code"])) {
    file_put_contents($log_file, "ERROR: no_input\n", FILE_APPEND);
    echo "no_input";
    exit;
}

$user_id = $_SESSION['preuser'];
$input_code = trim($_POST['code']);

file_put_contents($log_file, "User ID: $user_id, Input code: $input_code\n", FILE_APPEND);

if (!preg_match('/^\d{6}$/', $input_code)) {
    file_put_contents($log_file, "ERROR: invalid_format\n", FILE_APPEND);
    echo "invalid_format";
    exit;
}

$stmt = $mysqli->prepare("SELECT verification_code, code_expires FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    if ($row = $result->fetch_assoc()) {
        $db_code = $row['verification_code'];
        $expires = $row['code_expires'];
        
        file_put_contents($log_file, "DB Code: " . ($db_code ? $db_code : 'NULL') . ", Expires: " . ($expires ? $expires : 'NULL') . "\n", FILE_APPEND);
        file_put_contents($log_file, "Current time: " . time() . ", Expires timestamp: " . strtotime($expires) . "\n", FILE_APPEND);
        
        if ($expires && strtotime($expires) < time()) {
            $clear_stmt = $mysqli->prepare("UPDATE users SET verification_code = NULL, code_expires = NULL WHERE id = ?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();
            
            unset($_SESSION["code"]);
            unset($_SESSION["code_time"]);
            unset($_SESSION["preuser"]);
            
            file_put_contents($log_file, "ERROR: code_expired\n", FILE_APPEND);
            echo "code_expired";
            exit;
        }
        
        if ($db_code && $db_code == $input_code) {
            $user_stmt = $mysqli->prepare("SELECT id, login, roll FROM users WHERE id = ?");
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result && $user_data = $user_result->fetch_assoc()) {
                $new_session_token = bin2hex(random_bytes(32));
                
                $update_stmt = $mysqli->prepare("UPDATE users SET session_token = ? WHERE id = ?");
                $update_stmt->bind_param("si", $new_session_token, $user_id);
                $update_stmt->execute();
                $update_stmt->close();

                $_SESSION['session_token'] = $new_session_token;
                $_SESSION['user'] = $user_data['id'];
                $_SESSION['user_login'] = $user_data['login'];
                $_SESSION['user_roll'] = $user_data['roll'];
                
                file_put_contents($log_file, "SUCCESS: User authenticated - ID: " . $user_data['id'] . ", Login: " . $user_data['login'] . "\n", FILE_APPEND);
            }
            $user_stmt->close();
            
            $clear_stmt = $mysqli->prepare("UPDATE users SET verification_code = NULL, code_expires = NULL WHERE id = ?");
            $clear_stmt->bind_param("i", $user_id);
            $clear_stmt->execute();
            $clear_stmt->close();
            
            unset($_SESSION["code"]);
            unset($_SESSION["code_time"]);
            unset($_SESSION["preuser"]);
            
            file_put_contents($log_file, "SUCCESS: Code matched from database\n", FILE_APPEND);
            echo "success";
            exit;
        } else {
            file_put_contents($log_file, "DB check failed: DB code = '$db_code', Input = '$input_code'\n", FILE_APPEND);
        }
    } else {
        file_put_contents($log_file, "No row found in database for user_id: $user_id\n", FILE_APPEND);
    }
} else {
    file_put_contents($log_file, "Query failed: " . $mysqli->error . "\n", FILE_APPEND);
}
$stmt->close();

file_put_contents($log_file, "Checking session code. Session contents: " . print_r($_SESSION, true) . "\n", FILE_APPEND);

if (isset($_SESSION["code"])) {
    $session_code = $_SESSION["code"];
    file_put_contents($log_file, "Session code: " . $session_code . "\n", FILE_APPEND);
    
    if (isset($_SESSION["code_time"]) && (time() - $_SESSION["code_time"]) > 600) {
        unset($_SESSION["code"]);
        unset($_SESSION["code_time"]);
        unset($_SESSION["preuser"]);
        
        file_put_contents($log_file, "ERROR: session_code_expired\n", FILE_APPEND);
        echo "code_expired";
        exit;
    }
    
    if ($session_code == $input_code) {
        $user_stmt = $mysqli->prepare("SELECT id, login, roll FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result && $user_data = $user_result->fetch_assoc()) {
            $new_session_token = bin2hex(random_bytes(32));
            
            $update_stmt = $mysqli->prepare("UPDATE users SET session_token = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_session_token, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $_SESSION['session_token'] = $new_session_token;
            $_SESSION['user'] = $user_data['id'];
            $_SESSION['user_login'] = $user_data['login'];
            $_SESSION['user_roll'] = $user_data['roll'];
        }
        $user_stmt->close();
        
        $clear_stmt = $mysqli->prepare("UPDATE users SET verification_code = NULL, code_expires = NULL WHERE id = ?");
        $clear_stmt->bind_param("i", $user_id);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        unset($_SESSION["code"]);
        unset($_SESSION["code_time"]);
        unset($_SESSION["preuser"]);
        
        file_put_contents($log_file, "SUCCESS: Code matched from session\n", FILE_APPEND);
        echo "success";
        exit;
    } else {
        file_put_contents($log_file, "Session check failed: Session code = '$session_code', Input = '$input_code'\n", FILE_APPEND);
    }
} else {
    file_put_contents($log_file, "No code in session\n", FILE_APPEND);
}

file_put_contents($log_file, "ERROR: wrong_code\n", FILE_APPEND);
echo "wrong_code";
?>