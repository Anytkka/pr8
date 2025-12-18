<?php
	session_start();
	include("./settings/connect_datebase.php");
	
	if (isset($_SESSION['user'])) {
		if($_SESSION['user'] != -1) {
			
			$user_query = $mysqli->query("SELECT * FROM `users` WHERE `id` = ".$_SESSION['user']);
			while($user_read = $user_query->fetch_row()) {
				if($user_read[3] == 0) header("Location: user.php");
				else if($user_read[3] == 1) header("Location: admin.php");
			}
		}
 	}
?>
<html>
	<head> 
		<meta charset="utf-8">
		<title> Авторизация </title>
		
		<script src="https://code.jquery.com/jquery-1.8.3.js"></script>
		<link rel="stylesheet" href="style.css">
	</head>
	<body>
		<div class="top-menu">
			<a href=#><img src = "img/logo1.png"/></a>
			<div class="name">
				<a href="index.php">
					<div class="subname">БЗОПАСНОСТЬ  ВЕБ-ПРИЛОЖЕНИЙ</div>
					Пермский авиационный техникум им. А. Д. Швецова
				</a>
			</div>
		</div>
		<div class="space"> </div>
		<div class="main">
			<div class="content">
				<div class = "login">
					<div class="name">Авторизация</div>
				
					<div class = "sub-name">Логин:</div>
					<input name="_login" type="text" placeholder="" onkeypress="return PressToEnter(event)"/>
					<div class = "sub-name">Пароль:</div>
					<input name="_password" type="password" placeholder="" onkeypress="return PressToEnter(event)"/>
					
					<a href="regin.php">Регистрация</a>
					<br><a href="recovery.php">Забыли пароль?</a>
					<input type="button" class="button" value="Войти" onclick="LogIn()"/>
					<img src = "img/loading.gif" class="loading"/>
				</div>
				
				<div class="footer">
					© КГАПОУ "Авиатехникум", 2020
					<a href=#>Конфиденциальность</a>
					<a href=#>Условия</a>
				</div>
			</div>
		</div>
		
		<script>
		function LogIn() {
			var loading = document.getElementsByClassName("loading")[0];
			var button = document.getElementsByClassName("button")[0];
			
			var _login = document.getElementsByName("_login")[0].value;
			var _password = document.getElementsByName("_password")[0].value;
			
			if (_login.trim() === "") {
				alert("Введите логин");
				document.getElementsByName("_login")[0].focus();
				return;
			}
			
			if (_password === "") {
				alert("Введите пароль");
				document.getElementsByName("_password")[0].focus();
				return;
			}
			
			loading.style.display = "block";
			button.className = "button_diactive";
			
			var xhr = new XMLHttpRequest();
			var formData = new FormData();
			formData.append("login", _login);
			formData.append("password", _password);
			
			xhr.open("POST", "ajax/login_user.php", true);
			
			xhr.onload = function() {
				if (xhr.status === 200) {
					var response = xhr.responseText.trim();
					console.log("Ответ сервера: " + response);
					
					if(response === "empty") {
						alert("Ошибка: Заполните все поля");
					} else if(response === "not_found") {
						alert("Ошибка: Пользователь не найден\nПроверьте правильность логина");
					} else if(response === "wrong") {
						alert("Ошибка: Неверный пароль\nПроверьте правильность пароля");
					} else if(!isNaN(response) && parseInt(response) > 0) {
						// Успешная авторизация
						localStorage.setItem("token", response);
						alert("Авторизация успешна!");
						location.reload();
					} else {
						alert("Ошибка: Неизвестная ошибка - " + response);
					}
				} else {
					alert("Ошибка: Ошибка сервера " + xhr.status);
				}
				
				loading.style.display = "none";
				button.className = "button";
			};
			
			xhr.onerror = function() {
				alert("Ошибка: Сетевая ошибка");
				loading.style.display = "none";
				button.className = "button";
			};
			
			xhr.send(formData);
		}

		function PressToEnter(e) {
			if (e.keyCode == 13) {
				LogIn();
			}
}
		</script>
	</body>
</html>