<!DOCTYPE html>
<html>
<head> 
    <meta charset="utf-8">
    <title>Отправка кода</title>
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
                <div class="name">Отправка кода подтверждения</div>
                
                <div id="send_section" style="text-align: center; padding: 20px;">
                    <p>Для завершения входа необходимо подтверждение по email.</p>
                    <p>На ваш email будет отправлен 6-значный код.</p>
                    
                    <br>
                    <button onclick="sendCode()" class="button" style="padding: 10px 30px; font-size: 16px;">
                        Отправить код на email
                    </button>
                    
                    <div id="result" style="margin-top: 20px;"></div>
                    <img src="img/loading.gif" id="loading" style="display: none;"/>
                </div>
                
                <div id="code_section" style="text-align: center; display: none;">
                    <p>Введите 6-значный код из письма:</p>
                    
                    <form method="POST" action="ajax/check_code.php" id="code_form">
                        <input type="text" name="code" maxlength="6" placeholder="123456" 
                               style="padding: 10px; font-size: 18px; text-align: center; letter-spacing: 5px; width: 150px;"
                               id="code_input">
                        <br><br>
                        <input type="submit" class="button" value="Проверить код" id="submit_button">
                        <img src="img/loading.gif" id="check_loading" style="display: none;"/>
                    </form>
                    
                    <div id="check_result" style="margin-top: 20px;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function sendCode() {
        var button = document.querySelector('button');
        var result = document.getElementById('result');
        var loading = document.getElementById('loading');
        
        button.disabled = true;
        loading.style.display = 'block';
        result.innerHTML = '';
        
        $.ajax({
            url: 'ajax/send_mail.php',
            type: 'POST',
            success: function(response) {
                loading.style.display = 'none';
                button.disabled = false;
                
                if(response === "success") {
                    result.innerHTML = '<p style="color: green;">✅ Код отправлен на email! Проверьте почту.</p>';
                    document.getElementById('send_section').style.display = 'none';
                    document.getElementById('code_section').style.display = 'block';
                    // Фокус на поле ввода кода
                    document.getElementById('code_input').focus();
                } else if(response.startsWith('test_mode|')) {
                    var parts = response.split('|');
                    var code = parts[1];
                    var email = parts[2];
                    result.innerHTML = '<div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;">' +
                                      '<p><strong>ТЕСТОВЫЙ РЕЖИМ</strong> (email не отправлен)</p>' +
                                      '<p>Email: ' + email + '</p>' +
                                      '<p>Код: <strong style="font-size: 20px; letter-spacing: 5px;">' + code + '</strong></p>' +
                                      '<p><small>На реальном сервере код придет на email</small></p>' +
                                      '</div>' +
                                      '<button onclick="showCodeInput()" class="button">Продолжить с этим кодом</button>';
                } else if(response === "no_email") {
                    result.innerHTML = '<p style="color: red;">❌ У пользователя не указан email</p>';
                } else if(response === "user_not_found") {
                    result.innerHTML = '<p style="color: red;">❌ Пользователь не найден</p>';
                } else if(response === "no_session") {
                    result.innerHTML = '<p style="color: red;">❌ Сессия устарела. Вернитесь к авторизации.</p>' +
                                      '<a href="login.php" class="button">Вернуться</a>';
                } else {
                    result.innerHTML = '<p style="color: red;">❌ Ошибка: ' + response + '</p>';
                }
            },
            error: function() {
                loading.style.display = 'none';
                button.disabled = false;
                result.innerHTML = '<p style="color: red;">❌ Ошибка соединения с сервером</p>';
            }
        });
    }
    
    function showCodeInput() {
        document.getElementById('send_section').style.display = 'none';
        document.getElementById('code_section').style.display = 'block';
        document.getElementById('code_input').focus();
    }
    
    // Обработка формы проверки кода - ТОЛЬКО ОДИН РАЗ!
    $(document).ready(function() {
        $('#code_form').submit(function(e) {
            e.preventDefault();
            
            var form = $(this);
            var button = $('#submit_button');
            var loading = $('#check_loading');
            var result = $('#check_result');
            
            button.prop('disabled', true);
            loading.show();
            result.html('');
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    console.log("Server response:", response);
                    
                    var trimmedResponse = response.trim();
                    
                    if(trimmedResponse === "success") {
                        result.html('<p style="color: green;">Код подтвержден! Перенаправление...</p>');
                        button.prop('disabled', true);
                        
                        // Немедленное перенаправление
                        setTimeout(function() {
                            window.location.href = "user.php";
                        }, 500);
                    } else if(trimmedResponse === "code_expired") {
                        result.html('<p style="color: red;" Код устарел. <button onclick="sendCode()" class="button">Запросите новый</button></p>');
                        button.prop('disabled', false);
                        loading.hide();
                    } else if(trimmedResponse === "wrong_code") {
                        result.html('<p style="color: red;"> Неверный код. Попробуйте снова.</p>');
                        button.prop('disabled', false);
                        loading.hide();
                    } else if(trimmedResponse === "invalid_format") {
                        result.html('<p style="color: red;"> Введите ровно 6 цифр</p>');
                        button.prop('disabled', false);
                        loading.hide();
                    } else if(trimmedResponse === "no_session") {
                        result.html('<p style="color: red;"> Сессия устарела. <a href="login.php" class="button">Вернитесь к авторизации</a></p>');
                        button.prop('disabled', false);
                        loading.hide();
                    } else if(trimmedResponse === "no_input") {
                        result.html('<p style="color: red;"> Введите код</p>');
                        button.prop('disabled', false);
                        loading.hide();
                    } else {
                        result.html('<p style="color: red;"> Ошибка: ' + response + '</p>');
                        button.prop('disabled', false);
                        loading.hide();
                    }
                },
                error: function() {
                    loading.hide();
                    button.prop('disabled', false);
                    result.html('<p style="color: red;"> Ошибка соединения с сервером</p>');
                }
            });
        });
    });
    </script>
</body>
</html>