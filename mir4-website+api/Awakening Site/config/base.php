<?php
session_start();

$allowedPages = ['/', '/install', '/tools', '/rankings', '/m4', '/community', '/faq', '/index2'];

if ($_SERVER['REQUEST_URI'] === '/login') {

    if (isset($_POST['submit'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
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
            $_SESSION['error'] = "There was an error signing-in. Please try again in a few moments.";
            header("Location: login");
            exit();
        }
    }


// Register...
} elseif ($_SERVER['REQUEST_URI'] === '/register') {

    if (isset($_POST['h-captcha-response']) && !empty($_POST['h-captcha-response'])) {
        $secret = 'ES_896af29a65274b64a6ba1227289b6cf7';
        $verifyResponse = file_get_contents('https://hcaptcha.com/siteverify?secret=' . $secret . '&response=' . $_POST['h-captcha-response'] . '&remoteip=' . $_SERVER['REMOTE_ADDR']);
        $responseData = json_decode($verifyResponse);
        if ($responseData->success) {
            header("Location: ucp");
            exit();
        } else {
            $_SESSION['captchaError'] = 'Captcha check failed, please try again.'; //Needs translation adding
        }
    }


} elseif (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $user = $_SESSION['user'];
    $characters = $_SESSION['characters'];

} elseif (in_array($_SERVER['REQUEST_URI'], $allowedPages)) {
    // Page doesn't require login; allow access
} else {
    //Re-direct to login if accessing restricted page and not logged in
    header('Location: login');
    exit;
}

















//Include Translation Strings File
$translations = include 'config/translations.php';

//If Language is Set, Get Translated String
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $translations)) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Determine the Language based on Session or Browser Language
$lang = $_SESSION['lang'] ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
$lang = array_key_exists($lang, $translations) ? $lang : 'en';
$current_translations = $translations[$lang];

?>














<!DOCTYPE HTML>
<html lang="<?php echo $lang; ?>">



<!-- Header Start -->
<head>














<!-- Set the Page Title Name -->
<title>
    <?php
    if ($_SERVER['REQUEST_URI'] === '/') {
        echo $current_translations['title-home'];
    } elseif ($_SERVER['REQUEST_URI'] === '/install') {
        echo $current_translations['title-support'];
    } elseif ($_SERVER['REQUEST_URI'] === '/rankings') {
        echo $current_translations['title-avarank'];
    } elseif ($_SERVER['REQUEST_URI'] === '/m4') {
        echo $current_translations['title-globalrank'];
    } elseif ($_SERVER['REQUEST_URI'] === '/tools') {
        echo $current_translations['title-tools'];
    } elseif ($_SERVER['REQUEST_URI'] === '/ucp') {
        echo $current_translations['title-usercp'];
    } else {
        echo $current_translations['title']; // Default title
    }
    ?>
</title>

<!-- Set the Favicon -->
<link rel="shortcut icon" href="static/image/favicon.ico">

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
        j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
        f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', 'GTM-MHQG7GC');
</script>

<!-- Meta for Web Browsers -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<!-- Meta for Web Crawlers (Bing/Google) -->
<meta name="robots" content="index,follow">
<meta name="author" content="Topaz">
<meta name="keywords" content="Topaz; warrior; sorcerer; taoist; lancer; arbalist; dragon flame; dragon tornado; ray of light; dragon spear; arrow rain; mir4; mir4 game; the legend of mir; mir4 video; pay to earn; p2e; nft; mir4 2021; mir4 download; mir4 android; mir4 ios; mir4 news; mir4 available; mir4 beta; mir4 code; mir4 bot; mir4 good; mir4 graphics; mir4 guide; mir4 iPhone; mir4 launch; mir4 story; mir4 mobile; mir4 official; mir4 open; mir4 PC; mir4 pre-register; mir4 pre-registration; mir4 preview; mir4 project; mir4 register; mir4 registration; mir4 release; mir4 review; mir4 test; mir4 hack; mir4 walkthrough; mir4 wiki; mira; play mir4; adventure story; open world game; KPRG; MMORPG; rpg games; mobile game; action RPG; cross platfrom game; great mobile game; best mobile game; sandbox adventure RPG; multiple platforms; multiplayer mobile game; Action role-playing game;">
<meta name="description" content="From my battle, to our war. In the vast world of MIR4, you participate in expeditions, meet diverse characters with unique personalities and abilities, and fight powerful enemies, all on your journey to becoming king. But no king rules forever, so several growth systems will assist you in the war to conquer the world."> 
<!-- Meta for Twitter -->
<meta name="twitter:title" content="Topaz MIR4">
<meta name="twitter:image" content="https://mirfiles.com/resources/mir2/users/Jev/Mir%204/1.png">
<meta name="twitter:description" content="From my battle, to our war. In the vast world of MIR4, you participate in expeditions, meet diverse characters with unique personalities and abilities, and fight powerful enemies, all on your journey to becoming king. But no king rules forever, so several growth systems will assist you in the war to conquer the world.">
<meta name="twitter:card" content="summary_large_image">
<!-- Meta for Open Graph -->
<meta property="og:type" content="website">
<meta property="og:locale" content="en_US">
<meta property="og:title" content="Topaz MIR4">
<meta property="og:image" content="https://mirfiles.com/resources/mir2/users/Jev/Mir%204/1.png">
<meta property="og:description" content="From my battle, to our war. MIR4 is the successor of the traditional Legend of Mir series. In this vast world, you participate in expeditions, meet diverse characters with unique personalities and abilities, and fight powerful enemies together, all on your journey to becoming king. But no king rules forever, so several growth systems will assist you in the war to conquer the world.">

<!-- Add Style Sheets -->
<link rel="stylesheet" type="text/css" href="static/css/nanumsquare.css" media="all">

<link rel="stylesheet" href="static/css/common.css" media="all">
<link rel="stylesheet" href="config/style.css"> 
<link rel="stylesheet" href="static/css/azuremdiaplayer.min.css">

<!-- Javascripts -->
<script src="static/js/sdk.js"></script>
<script src="static/js/platform.js" async="" defer=""></script>
<script src="static/js/appleid.auth.js"></script>
<script src="static/js/kakao.min.js"></script>
<script src="https://js.hcaptcha.com/1/api.js" async defer></script>

<!-- Meta Pixel Code -->
<script>
    ! function(f, b, e, v, n, t, s) {
      if (f.fbq) return;
      n = f.fbq = function() {
        n.callMethod ?
          n.callMethod.apply(n, arguments) : n.queue.push(arguments)
      };
      if (!f._fbq) f._fbq = n;
      n.push = n;
      n.loaded = !0;
      n.version = '2.0';
      n.queue = [];
      t = b.createElement(e);
      t.async = !0;
      t.src = v;
      s = b.getElementsByTagName(e)[0];
      s.parentNode.insertBefore(t, s)
    }(window, document, 'script',
      'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '589294719372445');
    fbq('track', 'PageView');
  </script>
  <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=589294719372445&ev=PageView&noscript=1" /></noscript>
  <!-- End Meta Pixel Code -->

  <meta name="facebook-domain-verification" content="u2u323gq34r1wg5idnjnsjkwys3t6o" />

</head>





<body class="fp-viewing-story" style="overflow: hidden; height: 100%;" cz-shortcut-listen="true">

<div id="skipnavigation">
    <a href="#container">Skip</a>
</div>



<div id="viewport" data-nosnippet="">
    <div class="head">
        <div class="in">
            <h1><a href="/">From my battle, to our war. MIR4: Topaz</a></h1>
            <div class="rightSide">
                <div class="navList">



                <ul class="clear gnb">

                    <!-- Home -->
                    <li class="<?php echo ($_SERVER['REQUEST_URI'] === '/') ? 'active' : ''; ?>" data-menuanchor="story">
                        <p><a href="/"><span><?php echo $current_translations['title-home']; ?></span></a></p>
                    </li>

                    <!-- Community -->
                    <li class="new <?php echo ($_SERVER['REQUEST_URI'] === '/community') ? 'active' : ''; ?>" data-menuanchor="part2">
                        <p><a href="community" target="_self"><span><?php echo $current_translations['title-community']; ?></span></a></p>
                        
                        <ul class="subGnb">
                            <li><a href="community" target="_self"><?php echo $current_translations['title-community']; ?></a></li>
                            <li><a href="https://discord.gg/KCnHvwJJWN" target="_blank"><?php echo $current_translations['title-news']; ?></a></li>
                        </ul>

                    </li>

                    <!-- Support -->
                    <li class="<?php echo ($_SERVER['REQUEST_URI'] === '/install' || $_SERVER['REQUEST_URI'] === '/faq') ? 'active' : ''; ?>" data-menuanchor="part2">
                        <p><a href="install"><span><?php echo $current_translations['title-support']; ?></span></a></p>
                        
                        <ul class="subGnb">
                            <!-- <li><a href="comingsoon" target="_blank">FAQ</a></li> -->
                            <li><a href="install" target="_self"><?php echo $current_translations['title-install']; ?></a></li>
                            <li><a href="faq" target="_self">FAQ</a></li>
                        </ul>
                    </li>

                    <!-- Rankings -->
                    <li class="new <?php echo ($_SERVER['REQUEST_URI'] === '/rankings' || $_SERVER['REQUEST_URI'] === '/m4') ? 'active' : ''; ?>" data-menuanchor="part2">
                        <p><a href="rankings"><span><?php echo $current_translations['title-ranking']; ?><span></a></p>
                        
                        <ul class="subGnb">
                            <li class="new"><a href="rankings" target="_self"><?php echo $current_translations['title-avarank']; ?></a></li>
                            <li><a href="m4" target="_self"><?php echo $current_translations['title-globalrank']; ?></a></li>
                        </ul>
                    </li>


                    <!-- Tools -->
                    <li class="new <?php echo ($_SERVER['REQUEST_URI'] === '/tools') ? 'active' : ''; ?>" data-menuanchor="part2">
                        <p><a href="tools"><span><?php echo $current_translations['title-tools']; ?><span></a></p>
                        
                        <ul class="subGnb">
                            <!-- <li><a href="comingsoon" target="_blank">FAQ</a></li> -->
                            <li><a href="tools" target="_self"><?php echo $current_translations['title-tools']; ?></a></li>
                            <li><a href="https://www.lomcn.net/wiki/index.php/MIR4" target="_blank">WIKI</a></li>
                            <li><a href="https://wemade.thelegendofmir.uk/" target="_blank"><?php echo $current_translations['switchsite']; ?></a></li>
                        </ul>
                    </li>    
                    
                    <!-- Account -->
                    <li class="<?php echo ($_SERVER['REQUEST_URI'] === '/ucp' || $_SERVER['REQUEST_URI'] === '/login' || $_SERVER['REQUEST_URI'] === '/register') ? 'active' : ''; ?>" data-menuanchor="part2">
                        <p><a href="ucp"><span><?php echo $current_translations['account']; ?><span></a></p>
                        
                        <ul class="subGnb">
                            <?php
                            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                                echo '<li><a href="ucp" target="_self">' . $current_translations['myaccount'] . '</a></li>'; 
                                echo '<li><a href="logout" target="_self">' . $current_translations['logout'] . '</a></li>'; 
                            } else {
                                echo '<li><a href="login" target="_self">' . $current_translations['login'] . '</a></li>';
                                echo '<li><a href="register" target="_self">' . $current_translations['register'] . '</a></li>';
                            }
                            ?>
                        </ul>
                    </li>




                </ul>
            </div>
        </div>
    </div>
</div>

<!-- possible missing </div> here need to re-count when not highAF -->

<div class="background-image">
    <img src="static/image/mir4_background.png" alt="background">
</div>

<video autoplay loop muted>
    <source src="static/file/background_growth.mp4" type="video/mp4">
</video>


<!-- the possible missing </div> could also be here instead... need to stroke my plumbus when not highAF -->


</body>


    <!-- Javascripts -->

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