<?php
session_start(); // Deve ser a primeira linha

// Inclui o arquivo de configuração
require_once 'config/config.php';

// Check if the form is submitted
if (isset($_POST['submit'])) {
    // Assigning posted values to variables.
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hash da senha usando SHA-256
    $hashed_password = strtoupper(hash('sha256', $password));

    
    try {
        $stmt = $pdo_user->prepare("SELECT * FROM user_tb WHERE Username = :username and PasswordHash = :hashed_password");
        $stmt->execute(['username' => $username, 'hashed_password' => $hashed_password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user'] = $user;

            $stmt = $pdo_game->prepare("SELECT * FROM character_tb  WHERE accountUID = :accountUID ");
            $stmt->execute(['accountUID' => $user['AccountUID']]);
            $_SESSION['characters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);


            header("Location: ucp");
            exit();
        }else{
            $error = 'Incorrect username or password.';
        } 
    } catch (Exception $e) {
        // Armazena a mensagem de erro na sessão
        $_SESSION['error'] = "There was an error signing. Please try again in a few moments.";
        header("Location: login");
        exit();
    }
}

?>
<!DOCTYPE HTML>
<html lang="en">

<head>
    <title>Topaz MIR4</title>

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
    <meta name="author" content="Topaz">
    <meta name="keywords" content="Topaz; warrior; sorcerer; taoist; lancer; arbalist; dragon flame; dragon tornado; ray of light; dragon spear; arrow rain; mir4; mir4 game; the legend of mir; mir4 video; pay to earn; p2e; nft; mir4 2021; mir4 download; mir4 android; mir4 ios; mir4 news; mir4 available; mir4 beta; mir4 code; mir4 bot; mir4 good; mir4 graphics; mir4 guide; mir4 iPhone; mir4 launch; mir4 story; mir4 mobile; mir4 official; mir4 open; mir4 PC; mir4 pre-register; mir4 pre-registration; mir4 preview; mir4 project; mir4 register; mir4 registration; mir4 release; mir4 review; mir4 test; mir4 hack; mir4 walkthrough; mir4 wiki; mira; play mir4; adventure story; open world game; KPRG; MMORPG; rpg games; mobile game; action RPG; cross platfrom game; great mobile game; best mobile game; sandbox adventure RPG; multiple platforms; multiplayer mobile game; Action role-playing game;">
    <meta name="description" content="From my battle, to our war. In the vast world of MIR4, you participate in expeditions, meet diverse characters with unique personalities and abilities, and fight powerful enemies, all on your journey to becoming king. But no king rules forever, so several growth systems will assist you in the war to conquer the world.">

    <!-- Meta for Twitter -->
    <meta name="twitter:title" content="Topaz MIR4">
    <meta name="twitter:image" content="https://mirfiles.com/resources/mir2/users/Jev/Mir%204/1.png">
    <meta name="twitter:description" content="From my battle, to our war. In the vast world of MIR4, you participate in expeditions, meet diverse characters with unique personalities and abilities, and fight powerful enemies, all on your journey to becoming king. But no king rules forever, so several growth systems will assist you in the war to conquer the world.">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="Topaz MIR4">
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
                    <h1><a href="/">From my battle, to our war. MIR4: Topaz</a></h1>
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
                            <li><a href="register" target="_self">Register</a></li>
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
            <h2>Login - Topaz MIR4 Alpha</h2>
            <!-- Formulário corrigido -->
            <form  action="login" method="post">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="new-password" required>
                <?php if(isset($error)): ?>
                    <p style="color:rgb(245, 108, 108); font-size:12px;"><?php echo $error; ?></p>
                    <?php endif; ?>
                <br>
                <br>
                <br>
                <input type="submit" name="submit" value="Sign In">

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

</html>