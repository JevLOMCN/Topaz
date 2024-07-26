<?php
   
    //Meacher Test Base
    require_once 'config/base.php';

?>



<head>
    <title>Topaz MIR4 Control Panel</title>
</head>

<body>
    <div class="container">

       <div class="container halfsection">
            <!-- <div id="logomir4"></div> -->
            <h2 class="center">
                Welcome, <?= $user['Username']; ?>!
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

                    foreach ($characters as $character) {
                        echo '<div class="card">';
                            echo '<img src="static/characters/' . $character["Class"] . '.webp" alt="' . $character["name"] . '">';
                            echo '<div class="card-content">';
                                echo '<h3 class="chName">' . $character["CharacterName"] . '</h3>';
                                echo '<p>CharacterUID: ' . $character["CharacterUID"] . '</p>';
                                echo '<p>AccountUID: ' . $character["AccountUID"] . '</p>';
                                echo '<p>Class: ' . $current_translations['Classes'][$character["Class"]] . '</p>';
                                echo '<p>Lev: ' . $character["Lev"] . '</p>';
                                echo '<p>Exp: ' . $character["Exp"] . '</p>';
                                echo '<p>CurrentHP: ' . $character["CurrentHP"] . '</p>';
                                echo '<p>CurrentMP: ' . $character["CurrentMP"] . '</p>';
                                echo '<p>StageIdx: ' . $character["StageIdx"] . '</p>';
                                echo '<p>PositionX: ' . $character["PositionX"] . '</p>';
                                echo '<p>PositionY: ' . $character["PositionY"] . '</p>';
                                echo '<p>PositionZ: ' . $character["PositionZ"] . '</p>';
                                echo '<p>Wonbo: ' . $character["Wonbo"] . '</p>';
                                echo '<p>Gold: ' . $character["Gold"] . '</p>';
                                echo '<p>CreateTime: ' . $character["CreateTime"] . '</p>';
                                echo '<p>LoginTime: ' . $character["LoginTime"] . '</p>';
                                echo '<p>LogoutTime: ' . $character["LogoutTime"] . '</p>';
                                echo '<p>PlaytimeSec: ' . $character["PlaytimeSec"] . '</p>';
                                echo '<p>CombatPoint: ' . $character["CombatPoint"] . '</p>';
                                echo '<p>EnergyPoint: ' . $character["EnergyPoint"] . '</p>';
                                echo '<p>BlackIron: ' . $character["BlackIron"] . '</p>';
                                echo '<p>ActionPoint: ' . $character["ActionPoint"] . '</p>';
                                echo '<p>LastActionPointUpdateTime: ' . $character["LastActionPointUpdateTime"] . '</p>';
                                echo '<p>AwakenGrade: ' . $character["AwakenGrade"] . '</p>';
                                echo '<p>InvenExtendCnt: ' . $character["InvenExtendCnt"] . '</p>';
                                echo '<p>PKPoint: ' . $character["PKPoint"] . '</p>';
                                echo '<p>CostumeIdx: ' . $character["CostumeIdx"] . '</p>';
                            echo '</div>';
                            echo '<p>Last Login: ' . $character["LogoutTime"] . '</p>';
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
                        <input type="text" id="username" name="username" value="<?= $user['Username']; ?>"><br>
                    </div>

                    <div class="boxInput">
                        <label for="email">Email:</label><br>
                        <input type="email" id="email" name="email" value="<?= $user['Email']; ?>"><br>
                    </div>

                    <div class="boxInput">
                        <label for="password">Password:</label><br>
                        <input type="password" id="password" name="password"  value=""><br><br>
                    </div>  
                    
                    <div class="boxInput">
                        <br>
                        <input type="submit" value="Update">
                    </div>                       
                    
                </form>
            </div>
        </div>

    </div>
</body>

</html>