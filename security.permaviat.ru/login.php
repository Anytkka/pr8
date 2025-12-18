<?php
session_start();
include("./settings/connect_datebase.php");

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user'])) {
    // Проверяем роль
    $user_query = $mysqli->query("SELECT roll FROM users WHERE id = ".intval($_SESSION['user']));
    if ($user_read = $user_query->fetch_row()) {
        if($user_read[0] == 0) header("Location: user.php");
        else if($user_read[0] == 1) header("Location: admin.php");
    }
}
?>
<html>
<head> 
    <meta charset="utf-8">
    <title>Авторизация</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="top-menu">
        <a href="#"><img src="img/logo1.png"/></a>
        <div class="name">
            <a href="index.php">
                <div class="subname">БЗОПАСНОСТЬ ВЕБ-ПРИЛОЖЕНИЙ</div>
                Пермский авиационный техникум им. А. Д. Швецова
            </a>
        </div>
    </div>
    <div class="space"> </div>
    <div class="main">
        <div class="content">
            <div class="login">
                <div class="name">Авторизация</div>
                
                <div class="sub-name">Логин:</div>
                <input name="_login" type="text" placeholder="" onkeypress="return PressToEnter(event)"/>
                
                <div class="sub-name">Пароль:</div>
                <input name="_password" type="password" placeholder="" onkeypress="return PressToEnter(event)"/>
                
                <a href="regin.php">Регистрация</a>
                <br><a href="recovery.php">Забыли пароль?</a>
                <input type="button" class="button" value="Войти" onclick="LogIn()"/>
                <img src="img/loading.gif" class="loading" style="display: none;"/>
                
                <!-- Сообщение об ошибке -->
                <div id="error_message" style="color: red; margin-top: 10px;"></div>
            </div>
            
            <div class="footer">
                © КГАПОУ "Авиатехникум", 2020
                <a href="#">Конфиденциальность</a>
                <a href="#">Условия</a>
            </div>
        </div>
    </div>
    
    <script>
    function LogIn() {
        var loading = document.getElementsByClassName("loading")[0];
        var button = document.getElementsByClassName("button")[0];
        var errorDiv = document.getElementById("error_message");
        
        var _login = document.getElementsByName("_login")[0].value.trim();
        var _password = document.getElementsByName("_password")[0].value;
        
        if (_login === "") {
            errorDiv.innerHTML = "Введите логин";
            return;
        }
        
        if (_password === "") {
            errorDiv.innerHTML = "Введите пароль";
            return;
        }
        
        errorDiv.innerHTML = "";
        loading.style.display = "block";
        button.disabled = true;
        
        $.ajax({
            url: "ajax/login_user.php",
            type: "POST",
            data: {
                login: _login,
                password: _password
            },
            success: function(response) {
                console.log("Ответ сервера:", response);
                
                if(response.trim() === "success") {
                    // Успешная авторизация, переходим на страницу отправки кода
                    window.location.href = "mail.php";
                } else if(response.trim() === "wrong_password") {
                    errorDiv.innerHTML = "Неверный пароль";
                } else if(response.trim() === "user_not_found") {
                    errorDiv.innerHTML = "Пользователь не найден";
                } else if(response.trim() === "no_email") {
                    errorDiv.innerHTML = "У пользователя не указан email. Обратитесь к администратору.";
                } else {
                    errorDiv.innerHTML = "Ошибка: " + response;
                }
            },
            error: function(xhr, status, error) {
                errorDiv.innerHTML = "Ошибка соединения с сервером";
                console.error("AJAX ошибка:", error);
            },
            complete: function() {
                loading.style.display = "none";
                button.disabled = false;
            }
        });
    }
    
    function PressToEnter(e) {
        if (e.keyCode == 13) {
            LogIn();
        }
    }
    </script>
</body>
</html>