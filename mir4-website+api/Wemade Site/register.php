<?php
session_start(); // Deve ser a primeira linha

if (isset($_POST['h-captcha-response']) && !empty($_POST['h-captcha-response'])) {
    $secret = 'ES_896af29a65274b64a6ba1227289b6cf7';
    $verifyResponse = file_get_contents('https://hcaptcha.com/siteverify?secret=' . $secret . '&response=' . $_POST['h-captcha-response'] . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
    $responseData = json_decode($verifyResponse);
    if ($responseData->success) {
        // Seu código de sucesso vai aqui
        // Por exemplo, você pode redirecionar o usuário para uma página de sucesso
    } else {
        $_SESSION['captchaError'] = 'A verificação do robô falhou, por favor tente novamente.';
    }
}
?>
<!DOCTYPE HTML>
<html lang="en">

<head>
    <title>AVA MIR4</title>

    <!-- Google Tag Manager -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-MHQG7GC');
    </script>
    <!-- End Google Tag Manager -->

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=EDGE">
    <meta name="viewport">
    <meta name="robots" content="index,follow">
    <meta name="author" content="AVA">
    <meta name="keywords" content="ava; warrior; sorcerer; taoist; lancer; arbalist; dragon flame; dragon tornado; ray of light; dragon spear; arrow rain; mir4; mir4 game; the legend of mir; mir4 video; pay to earn; p2e; nft; mir4 2021; mir4 download; mir4 android; mir4 ios; mir4 news; mir4 available; mir4 beta; mir4 code; mir4 bot; mir4 good; mir4 graphics; mir4 guide; mir4 iPhone; mir4 launch; mir4 story; mir4 mobile; mir4 official; mir4 open; mir4 PC; mir4 pre-register; mir4 pre-registration; mir4 preview; mir4 project; mir4 register; mir4 registration; mir4 release; mir4 review; mir4 test; mir4 hack; mir4 walkthrough; mir4 wiki; mira; play mir4; adventure story; open world game; KPRG; MMORPG; rpg games; mobile game; action RPG; cross platfrom game; great mobile game; best mobile game; sandbox adventure RPG; multiple platforms; multiplayer mobile game; Action role-playing game;">
    <meta name="description" content="From my battle, to our war. In the vast world of MIR4, you participate in expeditions, meet diverse characters with unique personalities and abilities, and fight powerful enemies, all on your journey to becoming king. But no king rules forever, so several growth systems will assist you in the war to conquer the world.">

    <!-- Meta for Twitter -->
    <meta name="twitter:title" content="AVA MIR4">
    <meta name="twitter:image" content="https://mirfiles.com/resources/mir2/users/Jev/Mir%204/1.png">
    <meta name="twitter:description" content="From my battle, to our war. In the vast world of MIR4, you participate in expeditions, meet diverse characters with unique personalities and abilities, and fight powerful enemies, all on your journey to becoming king. But no king rules forever, so several growth systems will assist you in the war to conquer the world.">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="AVA MIR4">
    <meta property="og:image" content="https://mirfiles.com/resources/mir2/users/Jev/Mir%204/1.png">
    <meta property="og:description" content="From my battle, to our war. MIR4 is the successor of the traditional Legend of Mir series. In this vast world, you participate in expeditions, meet diverse characters with unique personalities and abilities, and fight powerful enemies together, all on your journey to becoming king. But no king rules forever, so several growth systems will assist you in the war to conquer the world.">

    <link rel="shortcut icon" href="static/image/favicon.ico">

    <link rel="stylesheet" type="text/css" href="static/css/nanumsquare.css" media="all"><!-- Revision 1107 -->
    <link rel="stylesheet" href="static/css/common.css" media="all">
    <link rel="stylesheet" href="static/css/azuremdiaplayer.min.css">

    <script src="static/js/sdk.js"></script>
    <script src="static/js/platform.js" async="" defer=""></script>
    <script src="static/js/appleid.auth.js"></script>
    <script src="static/js/kakao.min.js"></script>
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    <style>
        /*body {
            font-family: Arial, sans-serif;
            margin: 0 !important;
            padding: 0 !important;
            background-image: url('static/image/mir4_background.png');
            background-size: 100% 100% !important;
            background-position: center !important;
            background-repeat: no-repeat !important;

        }*/

        .background-image {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .background-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        @media screen and (max-width: 600px) {
            .background-image img {
                display: none;
            }

            body {
                background-color: black;
            }
        }

        /* Adiciona o vídeo como um elemento fixo no topo da página */
        video {
            /* Posiciona o vídeo no topo da página */
            position: fixed;
            /* Define o tamanho do vídeo para cobrir toda a tela */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* Define a transparência do vídeo */
            opacity: 0.5;
            /* Garante que o vídeo preencha toda a tela */
            object-fit: cover;
            z-index: -1;
        }

        .container {
            max-width: 401px;
            max-height: 100%;
            /* Adicione esta linha para definir a altura máxima */
            margin: 90px auto;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 32px;
            border-radius: 19px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }


        #logomir4 {
            background-image: url('static/image/h1_logo_n.png');
            background-repeat: no-repeat;
            background-position: center;
            width: 100%;
            /* Ajuste a largura conforme necessário */
            height: 50px;
            /* Reduzir a altura para que o logo fique mais próximo da borda */
            margin: 0;
            /* Remover margens */
            padding: 0;
            /* Remover preenchimento */
        }


        h2 {
            text-align: center !important;
            margin-bottom: 20px !important;
            font-size: 16px !important;
        }

        label {
            font-weight: bold;
            font-size: 16px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        input[type="text"]::placeholder,
        input[type="email"]::placeholder,
        input[type="password"]::placeholder {
            font-size: 16px;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 15px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .disabled-button {
            background-color: grey;
            cursor: not-allowed;
        }

        .password-strength {
            font-size: 12px;
            margin-top: 0.1vh;
            text-align: right;
        }

        .weak {
            color: red;
        }

        .medium {
            color: orange;
        }

        .strong {
            color: green;
        }

        .backhome {
            font-size: 15px;
            margin-top: 5px;
            color: black;
            /* Cor do link é preto */
        }

        .backhome:hover {
            color: #808080;
            /* Cor do link muda para cinza suave no hover */
        }

        #usernameError,
        #serverError,
        #passwordError,
        #confirmPasswordError,
        #ErrorCaptcha {
            color: red;
            font-weight: bold;
            margin-bottom: 0.1vh;
            text-align: left;
            font-size: 12px;
        }

        #confirmMessage {
            color: green;
            font-weight: bold;
            margin-bottom: -3vh;
            font-size: 12px;
            text-align: left;
        }

        .h-captcha {
            width: 100%;
            box-sizing: border-box;
        }
        #noise {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none; /* Isso permite que os cliques passem pelo canvas para o vídeo abaixo */
    opacity: 0.2; /* Isso torna o ruído semi-transparente */
}

    </style>
</head>

<body class="fp-viewing-story" style="overflow: hidden; height: 100%;" cz-shortcut-listen="true">
    <!-- Skip Nav -->
    <div id="skipnavigation">
        <a href="#container">Skip</a>
    </div>
    <!-- //Skip Nav -->

    <!-- viewport -->
    <div id="viewport" data-nosnippet="">

        <!-- header -->
        <div class="head">
        <div class="in">
                    <!-- h1 -->
                    <h1><a href="/">From my battle, to our war. MIR4: AVA</a></h1>
                    <!-- //h1 -->
                    <!-- rightSide -->
                    <div class="rightSide">
                    <!-- navList -->
                    <div class="navList">
                        <ul class="clear gnb">
                        <li class="new" data-menuanchor="story">
                            <p><a href="/"><span>Home</span></a></p>
                        </li>

                        <li class="new" data-menuanchor="part2">
                            <p><a href="https://discord.gg/KCnHvwJJWN" target="_blank"><span>Community</span></a></p>
                            <ul class="subGnb">
                            <li><a href="https://discord.gg/KCnHvwJJWN" target="_blank">News</a></li>
                            </ul>
                        </li>

                        <li data-menuanchor="part2">
                            <p><a href=""><span>Help Center</span></a></p>
                            <ul class="subGnb">
                            <!-- <li><a href="comingsoon" target="_blank">FAQ</a></li> -->
                            <li><a href="install" target="_blank">How to Install</a></li>
                            </ul>
                        </li>
                        <li class="new" data-menuanchor="part2">
                            <p><a href="rankings"><span>Ranking<span></a></p>
                                      </li>
                          <li class="new" data-menuanchor="part2">
                            <p><a href="tools"><span>Tools<span></a></p>
                            <ul class="subGnb">
                              <!-- <li><a href="comingsoon" target="_blank">FAQ</a></li> -->
                              <li><a href="wiki" target="_blank">WIKI</a></li>
                            </ul>
                          </li>
                        <li class="new active">
                            <p><a href="ucp"><span>Account<span></a></p>
                            <ul class="subGnb">
                            <li><a href="login" target="_self">Login</a></li>
                            </ul>
                        </li>
                        </ul>
                    </div>
                    <!-- //navList -->
                    </div>
                    <!-- //rightSide -->
                </div>   
        </div><!--<canvas id="noise"></canvas>-->
        <div class="background-image">
            <img src="static/image/mir4_background.png" alt="background">
        </div>
        <video autoplay loop muted>
            <source src="static/file/background_growth.mp4" type="video/mp4">
        </video>
        
        <div class="container">
            <div id="logomir4"></div>
            <h2>Register - AVA MIR4 Alpha</h2>
            <!-- Formulário corrigido -->
            <form id="registrationForm" action="formsend" method="post">
                <label for="username">Username (minimum 4 letters):</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <div id="usernameError"></div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" autocomplete="email" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="new-password" required>
                <div id="passwordError"></div>
                <div class="password-strength" id="passwordStrength"></div>
                <label for="confirmPassword">Confirm Password:</label>
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" autocomplete="new-password" required>
                <div id="confirmPasswordError"></div>
                <div id="confirmMessage"></div>
                <br>
                <!--<div id="usernameError"></div>
      <div id="confirmMessage"></div>
      <div id="passwordError"></div>-->
                <div id="ErrorCaptcha">
                    <?php
                    // Exibe a mensagem de erro do captcha, se houver
                    if (isset($_SESSION['captchaError'])) {
                        echo $_SESSION['captchaError'];
                        unset($_SESSION['captchaError']);
                    }
                    ?>
                </div>
                <div id="serverError">
                    <?php
                    // Exibe a mensagem de erro, se houver
                    if (isset($_SESSION['error'])) {
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    }
                    ?>
                </div>
                <br>
                <div class="h-captcha" data-sitekey="ae922e43-5b52-4534-ac51-4d371520090d"></div>
                <br>
                <input type="submit" id="registerButton" value="Register">

                <br><br>
                <div id="backhome"><a class="backhome" href="index">« Back to home</a></div>
            </form>
        </div>
</body>

<script type="text/javascript" src="static/js/jquery-1.11.3.min.js"></script>
<script type="text/javascript" src="static/js/clipboard.min.js"></script>
<script type="text/javascript" src="static/js/jquery.validator.js"></script>
<script type="text/javascript" src="static/js/jquery.fullPage.js"></script>
<script type="text/javascript" src="static/js/slick.min.js"></script>
<script type="text/javascript" src="static/js/common.js"></script>
<script type="text/javascript" src="static/js/common_valid.js"></script>
<script src="static/js/azuremediaplayer.min.js"></script>
<script type="text/javascript" src="static/js/media.js"></script>
<script src="static/js/ui.js"></script>
<script src="static/js/default.js"></script>
<script src="static/js/share-booking.js"></script>
<script src="static/js/md5.min.js"></script>
<script>
    document.getElementById("registrationForm").addEventListener("input", function(event) {
        var username = document.getElementById("username").value;
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirmPassword").value;
        var registerButton = document.getElementById("registerButton");
        var usernameError = document.getElementById("usernameError");
        var passwordError = document.getElementById("passwordError");
        var confirmPasswordError = document.getElementById("confirmPasswordError");
        var confirmMessage = document.getElementById("confirmMessage");

        // Verifica o campo de nome de usuário
        if (username.length < 4) {
            usernameError.textContent = "Username must be at least 4 letters long.";
        } else {
            usernameError.textContent = "";
        }

        // Verifica o campo de senha
        if (password.length === 0) {
            passwordError.textContent = "Password is required.";
        } else {
            passwordError.textContent = "";
        }

        // Verifica o campo de confirmação de senha
        if (password && confirmPassword.length === 0) {
            confirmMessage.textContent = "Confirm password";
            confirmPasswordError.textContent = "";
        } else if (password && confirmPassword && password !== confirmPassword) {
            confirmPasswordError.textContent = "Passwords do not match.";
            confirmMessage.textContent = "";
        } else {
            confirmPasswordError.textContent = "";
            confirmMessage.textContent = "";
        }

        var passwordStrength = document.getElementById("passwordStrength");
        var strength = 0;

        // If the password contains at least 8 characters, the strength is increased
        if (password.length >= 8) {
            strength += 1;
        }
        // If the password contains at least one number, the strength is increased
        if (password.match(/\d+/)) {
            strength += 1;
        }
        // If the password contains at least one lowercase letter and one uppercase letter, the strength is increased
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) {
            strength += 1;
        }
        // If the password contains at least one special character, the strength is increased
        if (password.match(/[!@#$%^&*(),.?":{}|<>]/)) {
            strength += 1;
        }

        // Display password strength
        if (strength < 2) {
            passwordStrength.innerHTML = "Password strength: <span class='weak'>Weak</span>";
        } else if (strength == 2) {
            passwordStrength.innerHTML = "Password strength: <span class='medium'>Medium</span>";
        } else {
            passwordStrength.innerHTML = "Password strength: <span class='strong'>Strong</span>";
        }

        // Enable or disable the submit button based on form validity
        var isValidForm = username && password && confirmPassword && password === confirmPassword;
        registerButton.disabled = !isValidForm;

        // Change the button color based on form validity
        if (isValidForm) {
            registerButton.classList.remove("disabled-button");
        } else {
            registerButton.classList.add("disabled-button");
        }
    });
    document.getElementById("registrationForm").addEventListener("submit", function(event) {
        var captchaResponse = hcaptcha.getResponse();
        if (captchaResponse.length === 0) {
            event.preventDefault();
            var captcha = document.querySelector('.h-captcha');
            captcha.style.border = 'solid 1px red'; // Adiciona a borda vermelha
            var errorField = document.getElementById('ErrorCaptcha');
            errorField.innerHTML = 'Você precisa completar o captcha.';
            var count = 0;
            var interval = setInterval(function() {
                captcha.style.borderColor = count % 2 ? 'red' : '';
                if (++count === 8) {
                    clearInterval(interval);
                }
            }, 500);
        }
    });
</script>

</html>