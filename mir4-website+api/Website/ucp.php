<?php
    session_start();
    if(isset($_SESSION['username'])) {
        // The session variable exists, you can use it here
        $username = $_SESSION['username'];
        $email = ""; // Substitua pelo email do usuário (que deve vir no session).
    }else{
        header('Location: /');
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

        .loginBox {
            max-width: 100%;
            margin: 90px auto;
            background-color: rgba(255, 255, 255, 0.3);
            padding: 10px 32px;
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


        .center { text-align: center !important; }

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
        
        .logout {
            font-size: 15px;
            margin-top: 5px;
            color: black;
            /* Cor do link é preto */
        }

        .logout:hover {
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


        /* Css para os cards dos personagens */
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .card {
            background-image: linear-gradient(to bottom right, #5E81FF, #405266);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 250px;
            overflow: hidden;
            text-align: center;  
            padding: 15px;    
            border: solid 0.5px #fff;      
        }

        .card img {
            width: 100%;
            height: auto;
        }

        .card-content {
            color: #fff;
        }

        .card-content h3 {
            margin: 0;
            font-size: 1.2em;
            color: #333;
        }

        .card-content p {
            margin: 10px 0;
            font-size: 0.9em;
            font-family: arial;
        }

        .content{ 
            padding: 20px 50px;
            background-image: linear-gradient(to bottom, rgba(255, 255, 255, 0.8), #405266);
            overflow-y: auto; /* Adiciona rolagem ao conteúdo */
        }
        .chName{ 
            background-color: rgb(255, 255, 255, 0.8);
            padding: 5px;
            border-radius: 8px;
        }

        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .tab-link {
            background-color: #ddd;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin: 0 5px;
            transition: background-color 0.3s;
        }

        .tab-link.active, .tab-link:hover {
            background-color: #bbb;
        }

        .tab-content {
            display: none;
            width: 100%;
        }

        .tab-content.active {
            display: block;
        }

        form {
            display: inline-block;
            align-items: center;
        }

        form label {
            width: 300px;
            margin-bottom: 5px;
            text-align: left;
        }

        form input {
            width: 280px;
            margin-bottom: 15px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        form input[type="submit"] {
            width: 150px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            padding: 10px;
        }

        form input[type="submit"]:hover {
            background-color: #45a049;
        }

        body {
            align-items: flex-start; /* Alinha o conteúdo no topo */
            overflow-y: auto;
            max-height: 100px;
        }

        .boxInput{
            display: inline-block;
            max-width: 270px;
            padding: 15px;
            vertical-align: top;
        }

    </style>
</head>


<body class="fp-viewing-story" style="height: 100%;" cz-shortcut-listen="true">
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
                        </li>
                        <li class="new active">
                            <p><a href="ucp"><span>Account<span></a></p>
                            <ul class="subGnb">
                            <li><a href="logout" target="_self">Logout</a></li>
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
        
        <div class="loginBox">
            <!-- <div id="logomir4"></div> -->
            <h2 class="center">
                Welcome, <?= $username; ?>!
            </h2>
        </div>

        <div class="content">
            <div class="tabs">
                <button class="tab-link active" onclick="openTab(event, 'characters')">See your characters</button>
                <button class="tab-link" onclick="openTab(event, 'personal-data')">Personal data</button>
            </div>
            
            <div id="characters" class="tab-content">
                <div class="card-container">
                    <?php
                    // Array de dados fictícios (remover após inserir array com dados vindos do banco)
                    $characters = [
                        [
                            "name" => "Warrior",
                            "class" => "Atirador Fantasma",
                            "level" => 79,
                            "power" => 71.548,
                            "server" => "SA054",
                            "class_img" => "1.webp",
                            "last_login" => "2024-06-25 15:48",
                        ],
                        [
                            "name" => "xDREAMx",
                            "class" => "Mage",
                            "level" => 112,
                            "power" => 145.659,
                            "server" => "EU025",
                            "class_img" => "2.webp",
                            "last_login" => "2024-06-25 15:48",
                        ],
                        [
                            "name" => "KeBab",
                            "class" => "Taoist",
                            "level" => 120,
                            "power" => 197.500,
                            "server" => "NA012",
                            "class_img" => "3.webp",
                            "last_login" => "2024-06-25 15:48",
                        ],
                        [
                            "name" => "ZHYBROO",
                            "class" => "Arbalist",
                            "level" => 55,
                            "power" => 56.847,
                            "server" => "NA012",
                            "class_img" => "4.webp",
                            "last_login" => "2024-06-25 15:48",
                        ],
                        [
                            "name" => "HERNANNES",
                            "class" => "Lancer",
                            "level" => 53,
                            "power" => 33.000,
                            "server" => "NA012",
                            "class_img" => "5.webp",
                            "last_login" => "2024-06-25 15:48",
                        ],
                        [
                            "name" => "Jev",
                            "class" => "Taoist",
                            "level" => 100,
                            "power" => 124.586,
                            "server" => "SA082",
                            "class_img" => "3.webp",
                            "last_login" => "2024-06-25 15:48",
                        ]
                    ];

                    // Loop através dos dados fictícios para criar os cards
                    // Trocar $characters por array com dados da QUERY
                    foreach ($characters as $character) {
                        echo '<div class="card">';
                            echo '<img src="static/characters/' . $character["class_img"] . '" alt="' . $character["name"] . '">';
                            echo '<div class="card-content">';
                                echo '<h3 class="chName">' . $character["name"] . '</h3>';
                                echo '<p>Class: ' . $character["class"] . '</p>';
                                echo '<p>Level: ' . $character["level"] . '</p>';
                                echo '<p>Power: ' . $character["power"] . '</p>';
                                echo '<p>Server: ' . $character["server"] . '</p>';
                            echo '</div>';
                            echo '<p>Last Login: ' . $character["last_login"] . '</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <div id="personal-data" class="tab-content" style="display:none">
                <!-- Conteúdo para a aba "Personal data" -->
                <form action="update_personal_data.php" method="post">
                    <div class="boxInput">
                        <label for="username">Username:</label><br>
                        <input type="text" id="username" name="username" value="<?= $username; ?>"><br>
                    </div>

                    <div class="boxInput">
                        <label for="email">Email:</label><br>
                        <input type="email" id="email" name="email" value="<?= $email; ?>"><br>
                    </div>

                    <div class="boxInput">
                        <label for="password">Password:</label><br>
                        <input type="password" id="password" name="password" value="********"><br><br>
                    </div>  
                    
                    <div class="boxInput">
                        <br>
                        <input type="submit" value="Update">
                    </div>                       
                    
                </form>
            </div>
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
<script src="static/js/open-tab.js"></script>
</html>