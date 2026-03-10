<?php
session_start();
if (isset($_SESSION['user'])) {
    if($_SESSION['user'] != -1) {
        include("./settings/connect_datebase.php");
        
        $user_query = $mysqli->query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['user']);
        while($user_read = $user_query->fetch_row()) {
            if($user_read[3] == 0) header("Location: user.php");
            else if($user_read[3] == 1) header("Location: admin.php");
        }
    }
}
?>
<!DOCTYPE HTML>
<html>
<head> 
    <script src="https://code.jquery.com/jquery-1.8.3.js"></script>
    <meta charset="utf-8">
    <title>Восстановление пароля</title>
    
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="style.css">
    <style>
        
        .input-error {
            background-color: #ff9797;
            box-shadow: 0 2px 2px 0 rgba(0,0,0,.14), 0 3px 1px -2px rgba(0,0,0,.12), 0 1px 5px 0 rgba(0,0,0,.2);
            margin-bottom: 20px;
            padding: 20px;
            overflow: hidden;
            font-size: 18px;
            color: #000;
            font-weight: bold;
            display: none;
            position: relative;
            border-left: 5px solid #dc3545;
        }
        
        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            cursor: pointer;
            font-size: 20px;
            font-weight: bold;
            color: #333;
            background: none;
            border: none;
            padding: 0 5px;
        }
        
        .close:hover {
            color: #000;
            background-color: rgba(0,0,0,0.1);
        }
        
        .message {
            font-size: 14px;
            font-weight: normal;
            margin-top: 10px;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            display: none;
            border-left: 5px solid #28a745;
        }
        
        .success .name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .loading {
            display: none;
            margin-top: 10px;
            text-align: center;
            color: #666;
        }
        
      
        .top-menu {
            width: 100%;
            height: 100px;
            background-color: #FFFFFF;
            border-bottom: 1px solid #DADCE0;
            position: fixed;
        }
        
        .top-menu .logo-placeholder {
            width: 50px;
            height: 50px;
            margin-left: 100px;
            margin-top: 25px;
            float: left;
            margin-right: 25px;
            background-color: #1A73E8;
            border-radius: 5px;
        }
        
        .top-menu .name {
            padding-top: 20px;
        }
        
        .top-menu .name a {
            font-size: 15.5px;
            color: #5F6368;
            text-decoration: none;
        }
        
        .top-menu .name a .subname {
            font-size: 23px;
            color: #5F6368;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="top-menu">
        <div class="logo-placeholder"></div>
        <div class="name">
            <a href="index.php">
                <div class="subname">Электронная приемная комиссия</div>
                Пермского авиационного техникума им. А. Д. Швецова
            </a>
        </div>
    </div>
    <div class="space"></div>
    <div class="main">
        <div class="content">
            <div class="input-error">
                <span class="close" onclick="DisableError()">✕</span>
                <strong>Ошибка!</strong>
                <div class="message">Указанный вами адрес электронной почты не существует в системе. Проверьте правильность ввода данных.</div>
            </div>
        
            <div class="success" style="display: none;">
                <div class="name">Успешно!</div>
                <div class="description">
                    На указанный вами адрес будет отправлено письмо с новым паролем.
                </div>
            </div>
        
            <div class="login">
                <div class="name">Восстановление пароля</div>
            
                <div class="sub-name">Email (логин):</div>
                <div style="font-size: 12px; margin-bottom: 10px;">На указанную вами почту будет выслан новый пароль для входа в систему.</div>
                <input name="_login" type="text" placeholder="example@mail.ru"/>
                
                <div style="overflow: hidden; margin-top: 20px;">
                    <input type="button" class="button" value="Отправить" onclick="LogIn()" style="float: right; margin: 0;"/>
                    <div class="loading" style="float: right; margin-right: 20px; margin-top: 8px;">Отправка...</div>
                </div>
            </div>
            
            <div class="footer">
                © КГАПОУ "Авиатехникум", 2020
                <a href="#">Конфиденциальность</a>
                <a href="#">Условия</a>
            </div>
        </div>
    </div>
    
    <script>
        var errorWindow = document.getElementsByClassName("input-error")[0];
        var loading = document.getElementsByClassName("loading")[0];
        var button = document.getElementsByClassName("button")[0];
        
        errorWindow.style.display = "none";
    
        function DisableError() {
            errorWindow.style.display = "none";
        }
        
        function EnableError() {
            errorWindow.style.display = "block";
        }
        
        function LogIn() {
            var _login = document.getElementsByName("_login")[0].value;
            
            if(_login.trim() === "") {
                alert("Введите email");
                return;
            }
            
            loading.style.display = "block";
            button.disabled = true;
            button.value = "Отправка...";
            
            var data = new FormData();
            data.append("login", _login);
            
            $.ajax({
                url: 'ajax/recovery.php',
                type: 'POST',
                data: data,
                cache: false,
                dataType: 'html',
                processData: false,
                contentType: false,
                success: function(_data) {
                    if(_data == -1) {
                        EnableError();
                        loading.style.display = "none";
                        button.disabled = false;
                        button.value = "Отправить";
                    } else {
                        document.getElementsByClassName('success')[0].style.display = "block";
                        document.getElementsByClassName('description')[0].innerHTML = "На указанный вами адрес <b>"+_login+"</b> будет отправлено письмо с новым паролем.";
                        
                        document.getElementsByClassName('login')[0].style.display = "none";
                        loading.style.display = "none";
                    }
                },
                error: function() {
                    alert("Системная ошибка. Попробуйте позже.");
                    loading.style.display = "none";
                    button.disabled = false;
                    button.value = "Отправить";
                }
            });
        }
    </script>
</body>
</html>