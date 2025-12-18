<?php
session_start();
include("./settings/connect_datebase.php");

// Если пользователь уже авторизован, перенаправляем
if (isset($_SESSION['user'])) {
    header("Location: user.php");
    exit;
}
?>
<html>
<head> 
    <meta charset="utf-8">
    <title>Регистрация</title>
    <script src="https://code.jquery.com/jquery-1.8.3.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="top-menu">
        <a href=#><img src="img/logo1.png"/></a>
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
                <div class="name">Регистрация</div>
                
                <div class="sub-name">Логин:</div>
                <input name="_login" type="text" placeholder="" onkeypress="return PressToEnter(event)"/>
                
                <div class="sub-name">Email:</div>
                <input name="_email" type="email" placeholder="example@mail.com" onkeypress="return PressToEnter(event)"/>
                
                <div class="sub-name">Пароль:</div>
                <input name="_password" type="password" placeholder="" onkeypress="return PressToEnter(event)"/>
                
                <div class="sub-name">Повторите пароль:</div>
                <input name="_passwordCopy" type="password" placeholder="" onkeypress="return PressToEnter(event)"/>
                
                <a href="login.php">Вернуться к авторизации</a>
                <input type="button" class="button" value="Зарегистрироваться" onclick="RegIn()" style="margin-top: 10px;"/>
                <img src="img/loading.gif" class="loading" style="margin-top: 10px; display: none;"/>
            </div>
            
            <div class="footer">
                © КГАПОУ "Авиатехникум", 2020
                <a href=#>Конфиденциальность</a>
                <a href=#>Условия</a>
            </div>
        </div>
    </div>
    
    <script>
    var loading = document.getElementsByClassName("loading")[0];
    var button = document.getElementsByClassName("button")[0];
    
    function CheckPassword(value) {
        let errors = [];
        
        if (value.length < 8) {
            return false;
        }
        if (!/\d/.test(value)) {
            return false;
        }
        if (!/[A-Z]/.test(value)) {
            return false;
        }
        if (!/[!@#$%^&*\-_=]/.test(value)) {
            return false;
        }
        
        return true;
    }
    
    function RegIn() {
        var _login = document.getElementsByName("_login")[0].value.trim();
        var _email = document.getElementsByName("_email")[0].value.trim();
        var _password = document.getElementsByName("_password")[0].value;
        var _passwordCopy = document.getElementsByName("_passwordCopy")[0].value;
        
        if(_login === "") {
            alert("Введите логин.");
            return;
        }
        if(_email === "" || !_email.includes('@')) {
            alert("Введите корректный email.");
            return;
        }
        if(_password === "") {
            alert("Введите пароль");
            return;
        }
        if(!CheckPassword(_password)) {
            alert("Пароль должен содержать:\n- Минимум 8 символов\n- Хотя бы одну цифру\n- Хотя бы одну заглавную букву\n- Хотя бы один специальный символ (!@#$%^&*-_=)");
            return;
        }
        if(_password !== _passwordCopy) {
            alert("Пароли не совпадают");
            return;
        }
        
        loading.style.display = "block";
        button.disabled = true;
        
        var data = new FormData();
        data.append("login", _login);
        data.append("email", _email);
        data.append("password", _password);
        
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "ajax/regin_user.php", true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                var response = xhr.responseText.trim();
                console.log("Ответ: " + response);
                
                if(response === "success") {
                    alert("Регистрация успешна! Теперь войдите в систему.");
                    window.location.href = "login.php";
                } else if(response === "user_exists") {
                    alert("Пользователь с таким логином уже существует.");
                } else if(response === "email_exists") {
                    alert("Пользователь с таким email уже существует.");
                } else if(response === "weak_password") {
                    alert("Пароль слишком слабый.");
                } else {
                    alert("Ошибка регистрации: " + response);
                }
            } else {
                alert("Ошибка сервера");
            }
            
            loading.style.display = "none";
            button.disabled = false;
        };
        
        xhr.send(data);
    }
    
    function PressToEnter(e) {
        if (e.keyCode == 13) {
            RegIn();
        }
    }
    </script>
</body>
</html>