<?php
require_once 'config/config.php';
require_once 'config/base.php';

function getRanking($column) {
    global $pdo_game;
    $stmt = $pdo_game->prepare("SELECT CharacterName, Class, PlayTimeSec, CombatPoint, PKPoint, Lev, EnergyPoint FROM character_tb ORDER BY $column DESC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPlayer($searchCriteria) {
    global $pdo_game;
    $stmt = $pdo_game->prepare("SELECT CharacterName, Class, PlayTimeSec, CombatPoint, PKPoint, Lev, EnergyPoint FROM character_tb WHERE CharacterName LIKE :searchCriteria ORDER BY CharacterName DESC");
    $stmt->bindValue(':searchCriteria', '%' . $searchCriteria . '%', PDO::PARAM_STR); 
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getClass($searchCriteria) {
    global $pdo_game;
    $stmt = $pdo_game->prepare("SELECT CharacterName, Class, PlayTimeSec, CombatPoint, PKPoint, Lev, EnergyPoint FROM character_tb WHERE Class = :searchCriteria ORDER BY Lev DESC");
    $stmt->bindValue(':searchCriteria', $searchCriteria, PDO::PARAM_STR);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}





?>


<head>

</head>



<body>
        
        <div class="container">
            <div id="logomir"></div>
            
            <div class="language-selector">
                <a href="?lang=en">English</a> |
                <a href="?lang=es">Español</a> |
                <a href="?lang=pt">Português</a> |
                <a href="?lang=tl">Tagalog</a>
            </div>

            <h1>AVA Mir 4 Rankings</h1>





            <div class="flex-container">
<!--
<div class="container halfsection">

<h1>a spare container...</h1>

</div>
-->

<div class="container halfsection">
<h2> Search by Player Name</h2>
    <form method="GET" action="">
        <input type="text" name="search" id="search" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <input type="submit" value="Search">
    </form>
</div>


</div>


<div class="flex-container">

<div class="container halfsection">
<h2>Search by Rank Type</h2>
<form method="GET" action="">

    <button type="submit" name="rank_choice" value="Lev">
    <img src="static/image/rankpage/1.png" alt="CombatPoints" width="90px" title="Level Ranking">
    </button>

    <button type="submit" name="rank_choice" value="CombatPoint">
        <img src="static/image/rankpage/2.png" alt="CombatPoints" width="90px" title="Combat Point Ranking">
    </button>

    <button type="submit" name="rank_choice" value="EnergyPoint">
    <img src="static/image/rankpage/3.png" alt="EnergyPoint" width="90px" title="Energy Point Ranking">
    </button>

    <button type="submit" name="rank_choice" value="PlayTimeSec">
    <img src="static/image/rankpage/4.png" alt="CombatPoints" width="90px" title="Play Time Ranking">
    </button>

    <button type="submit" name="rank_choice" value="PKPoint">
    <img src="static/image/rankpage/6.png" alt="PKPoints" width="90px" title="PK Point Ranking">
    </button>

    
</form>
</div>


<div class="container halfsection">
<h2> Search by Class Type</h2>
<form method="GET" action="">

<button type="submit" name="class_choice" value="1">
    <img src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_1.png" alt="Warrior" width="90%" title="Warrior Rankings">
</button>

<button type="submit" name="class_choice" value="2">
    <img src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_2.png" alt="Sorcerer" width="90%" title="Sorcerer Rankings">
</button>

<button type="submit" name="class_choice" value="3">
    <img src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_3.png" alt="Warrior" width="90%" title="Taoist Rankings">
</button>

<button type="submit" name="class_choice" value="4">
    <img src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_4.png" alt="Warrior" width="90%" title="Arbalist Rankings">
</button>

<button type="submit" name="class_choice" value="5">
    <img src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_5.png" alt="Warrior" width="90%" title="Lancer Rankings">
</button>
<!--
<button type="submit" name="class_choice" value="6">
    <img src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_6.png" alt="Warrior" width="90%" title="Darkist Rankings">
</button>
-->


</form>

        </div>





</div>








        

        <?php


if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $playerData = getPlayer($search);

        echo '<h1>Search Results:</h1>';
        if (empty($playerData)) {
            echo '<h2>No results found.</h2>';
        } else {
            displayTable($playerData);
        }

    } elseif (isset($_GET['class_choice'])) {
        $searchClass = $_GET['class_choice'];
        $classData = getClass($searchClass);

        echo '<h1>Search Results:</h1>';
        if (empty($classData)) {
            echo '<h2>No results found.</h2>';
        } else {
            displayTable($classData);
        }

    } elseif (isset($_GET['rank_choice'])) {
        $searchRank = $_GET['rank_choice'];
        $rankData = getRanking($searchRank);

        echo '<h1>Search Results:</h1>';
        if (empty($rankData)) {
            echo '<h2>No results found.</h2>';
        } else {
            displayTable($rankData);
        }

    } else {
        echo '<h2>Please enter a player name or select an option above.</h2>';
    }
}

function displayTable($data) {
    echo '<table>';
    echo '<tr><th>Character Name</th><th>Level</th><th>Class</th><th>Play Time</th><th>Combat Points</th><th>PK Points</th><th>Energy</th></tr>';
    foreach ($data as $char) {
        echo '<tr>';
        echo '<td>' . $char['CharacterName'] . '</td>';
        echo '<td>' . $char['Lev'] . '</td>';
        echo '<td>' . $char['Class'] . '</td>';
        echo '<td>' . $char['PlayTimeSec'] . '</td>';
        echo '<td>' . $char['CombatPoint'] . '</td>';
        echo '<td>' . $char['PKPoint'] . '</td>';
        echo '<td>' . $char['EnergyPoint'] . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
?>





        </div>
</body>

</html>