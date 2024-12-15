<?php
require_once 'config/base.php';

// Load character data
function loadCharacterData() {
    $filePath = 'Database/character_tb.json';
    if (!file_exists($filePath)) return [];
    return json_decode(file_get_contents($filePath), true);
}

// Load stage names
function loadStageNames() {
    $filePath = 'Database/StageNames.json';
    if (!file_exists($filePath)) return [];
    $json = file_get_contents($filePath);
    $stages = json_decode($json, true);
    $lookup = [];
    foreach ($stages as $stage) {
        $lookup[$stage['StageID']] = $stage['StageName'];
    }
    return $lookup;
}

// Load level requirements
function loadLevelData() {
    $filePath = 'Database/LEVEL.json';
    if (!file_exists($filePath)) return [];
    $json = file_get_contents($filePath);
    $levels = json_decode($json, true);
    $lookup = [];
    foreach ($levels as $level) {
        $lookup[$level['ClassId']][$level['Level']] = $level['ReqExp'];
    }
    return $lookup;
}

// Calculate class percentages
function calculateClassPercentages($data) {
    $classCounts = [];
    $totalPlayers = count($data);
    foreach ($data as $char) {
        $class = $char['Class'];
        $classCounts[$class] = ($classCounts[$class] ?? 0) + 1;
    }
    $percentages = [];
    foreach ($classCounts as $class => $count) {
        $percentages[$class] = round(($count / $totalPlayers) * 100, 1);
    }
    return $percentages;
}

// Abbreviate large numbers
function formatLargeNumber($number) {
    if ($number >= 1000000000) return round($number / 1000000000, 2) . 'B';
    if ($number >= 1000000) return round($number / 1000000, 2) . 'M';
    if ($number >= 1000) return round($number / 1000, 2) . 'k';
    return $number;
}

// Convert seconds to hours
function formatPlaytime($seconds) {
    $hours = floor($seconds / 3600);
    return "{$hours}h";
}

// Sort and filter
function sortData(&$data, $sortOption) {
    usort($data, fn($a, $b) => $b[$sortOption] <=> $a[$sortOption]);
}

// Load data
$data = loadCharacterData();
$stageNames = loadStageNames();
$classPercentages = calculateClassPercentages($data);
$sortOption = $_GET['sort'] ?? 'Lev';
$selectedMap = $_GET['map'] ?? null;

// Handle filters
if ($selectedMap) {
    $data = array_filter($data, fn($char) => $char['StageIdx'] == $selectedMap);
}
sortData($data, $sortOption);

// Handle profile selection
$selectedProfile = null;
if (isset($_GET['profile'])) {
    $profileName = urldecode($_GET['profile']);
    foreach ($data as $char) {
        if ($char['CharacterName'] == $profileName) {
            $selectedProfile = $char;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>AVA Mir 4 Rankings</title>
    <style>
        body { background-color: #1a1a1a; 
        color: #fff; 
        font-family: Arial, sans-serif; 
            
        }
        .class-percentage-container { 
            display: flex; justify-content: center; 
            gap: 20px; 
            margin-top: 80px; 
            
        }
        .class-item { 
            text-align: center; 
            font-size: 32px; 
            color: #fff; }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            background-color: #222; }
        th, td { 
            border: 1px 
            solid #444; 
            padding: 10px; 
            text-align: left; 
            font-size: 16px; }
        th { 
            background-color: #333; 
            position: sticky; 
            top: 0; }
        a { 
            color: #ffcc00; 
            text-decoration: none; 
        } 
        a:hover { 
            text-decoration: underline; 
            
        }
        .profile-container { 
            background-color: #333; 
            padding: 20px; 
            border-radius: 10px; 
            margin: 80px auto; 
            width: 50%; 
            text-align: center; 
        }
    </style>
</head>
<body>

<?php if ($selectedProfile): ?>
    <!-- Player Profile -->
    <div class="profile-container">
        <h1><?php echo htmlspecialchars($selectedProfile['CharacterName']); ?>'s Profile</h1>

        <?php
        // Load JSON files
        $equipItems = json_decode(file_get_contents('Database/Characters/equip_item_tb.json'), true);
        $itemData = json_decode(file_get_contents('Database/Characters/item_tb.json'), true);
        $allItems = json_decode(file_get_contents('Database/ITEM.json'), true);
        $stringTemplate = json_decode(file_get_contents('Database/string_template.json'), true);

        // Helper function to find ItemIDX based on ItemUID
        function getItemIDX($itemUID, $itemData) {
            foreach ($itemData as $item) {
                if ($item['ItemUID'] == $itemUID) {
                    return $item['ItemIdx'];
                }
            }
            return null;
        }

        // Helper function to find Icon and Grade based on ItemIDX (ItemId)
        function getItemDetails($itemIDX, $allItems) {
            foreach ($allItems as $item) {
                if ($item['ItemId'] == $itemIDX) {
                    return ['Icon' => $item['Icon'], 'Grade' => $item['Grade'], 'NameSid' => $item['NameSid']];
                }
            }
            return ['Icon' => null, 'Grade' => 1, 'NameSid' => null];
        }

        // Helper function to get item name from string_template.json
        function getItemName($nameSid, $stringTemplate) {
            return $stringTemplate[$nameSid] ?? 'Unknown Item';
        }

        // Build character equipment
        $characterEquip = [];
        foreach ($equipItems as $equip) {
            if ($equip['CharacterUID'] == $selectedProfile['CharacterUID']) {
                $itemUID = $equip['ItemUID'];
                $equipSlot = $equip['EquipSlot'];

                // Step 1: Find ItemIDX from item_tb.json
                $itemIDX = getItemIDX($itemUID, $itemData);

                // Step 2: Find Icon, Grade, and NameSid from ITEM.json
                $itemDetails = $itemIDX ? getItemDetails($itemIDX, $allItems) : null;

                $icon = $itemDetails['Icon'] ?? null;
                $grade = $itemDetails['Grade'] ?? 1; // Default grade
                $name = $itemDetails['NameSid'] ? getItemName($itemDetails['NameSid'], $stringTemplate) : 'Unknown';

                // Step 3: Set icon path or default
                $characterEquip[$equipSlot] = [
                    'icon' => ($icon && file_exists("Database/Icons/Items/{$icon}.png")) 
                                ? "Database/Icons/Items/{$icon}.png" 
                                : "Database/Icons/Items/400000.png",
                    'grade' => $grade,
                    'name' => $name
                ];
            }
        }

        // Define equipment slots with positions
        $equipmentSlots = [
            1  => [55, 20],    // EquipSlot1 - Weapon
            9  => [10, 110],   // EquipSlot9 - Side Weapon
            5  => [10, 210],   // EquipSlot5 - Armour
            6  => [40, 300],   // EquipSlot6 - Bottoms
            7  => [130, 325],  // EquipSlot7 - Gloves
            8  => [225, 325],  // EquipSlot8 - Boots
            2  => [320, 305],  // EquipSlot2 - Necklace
            3  => [350, 220],  // EquipSlot3 - Bracelet
            4  => [340, 130],  // EquipSlot4 - Rings
            10 => [310, 45]    // EquipSlot10 - Earrings
        ];
        ?>

        <!-- Circular Equipment Layout -->
        <div style="position: relative; width: 400px; height: 400px; margin: 0 auto;">
            <!-- Central Character Image -->
            <img src="Database/Characters/<?php echo $selectedProfile['Class']; ?>.webp" 
                 alt="Character Image" 
                 style="width:200px; height:200px; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); border-radius: 50%;">            

            <!-- Equipment Slots -->
            <?php foreach ($equipmentSlots as $slot => [$x, $y]): ?>
                <!-- Slot Background -->
                <img src="Database/Icons/CharacterWindow/MainSlots.png" 
                     alt="Slot Background" 
                     style="position: absolute; width: 82px; height: 82px; top: <?php echo $y; ?>px; left: <?php echo $x; ?>px;">

                <?php if (isset($characterEquip[$slot])): ?>
                    <!-- Grade Background -->
                    <img src="Database/Icons/Items/grade-<?php echo $characterEquip[$slot]['grade']; ?>-modified.png" 
                         alt="Grade Background" 
                         style="position: absolute; width: 82px; height: 82px; top: <?php echo $y; ?>px; left: <?php echo $x; ?>px;">

                    <!-- Item Icon -->
                    <img src="<?php echo $characterEquip[$slot]['icon']; ?>" 
                         alt="Item Icon" 
                         style="position: absolute; width: 60px; height: 60px; top: <?php echo $y + 11; ?>px; left: <?php echo $x + 11; ?>px;"
                         onmouseover="showItemInfo(event, '<?php echo $characterEquip[$slot]['name']; ?>', <?php echo $characterEquip[$slot]['grade']; ?>)"
                         onmouseout="hideItemInfo()">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <a href="?" style="color: #ffcc00; display: block; margin-top: 20px; text-align: center;">&larr; Back to Rankings</a>
    </div>
<?php endif; ?>


<!-- Class Percentages -->
<?php if (!$selectedProfile): ?>
    <div class="class-percentage-container">
        <?php foreach ($classPercentages as $class => $percentage): ?>
            <div class="class-item">
                <img src="static/characters/<?php echo $class; ?>.webp" alt="Class <?php echo $class; ?>" width="80" height="80">
                <div><?php echo $percentage; ?>%</div>
            </div>
        <?php endforeach; ?>
    </div>

<!-- Filters -->
    <div class="filter-container">
        <form method="GET" action="">
            <label>Sort by:</label>
<select name="sort" onchange="this.form.submit()">
    <?php
    $options = [
        'Lev' => 'Level', 
        'Wonbo' => 'Wonbo', 
        'Gold' => 'Gold', 
        'PlaytimeSec' => 'Playtime',
        'CombatPoint' => 'Combat Points', 
        'EnergyPoint' => 'Energy Points',
        'BlackIron' => 'Black Iron', 
        'ActionPoint' => 'Action Points', 
        'PKPoint' => 'PK Points',
        'AncientCoin' => 'Ancient Coin' // New sort option
    ];
    foreach ($options as $key => $label) {
        $selected = $sortOption == $key ? 'selected' : '';
        echo "<option value='$key' $selected>$label</option>";
    }
    ?>
</select>
            <label>Filter by Map:</label>
            <select name="map" onchange="this.form.submit()">
                <option value="">All Maps</option>
                <?php foreach ($stageNames as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $selectedMap == $id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <!-- Rankings Table -->
    <table>
        <tr>
            <th>Name</th>
            <th>Level</th>
            <th>Map</th>
            <th>Wonbo</th>
            <th>Gold</th>
            <th>Playtime</th>
            <th>Combat Points</th>
            <th>Energy Points</th>
            <th>Black Iron</th>
            <th>ActionPoint</th>
            <th>AncientCoin</th>
            <th>PK Points</th>
        </tr>
        <?php foreach ($data as $index => $char): ?>
            <tr>
                <td>
                    <a href="?profile=<?php echo urlencode($char['CharacterName']); ?>">
                        <?php echo htmlspecialchars($char['CharacterName']); ?>
                    </a>
                </td>
                <td><?php echo $char['Lev']; ?></td>
                <td><?php echo $stageNames[$char['StageIdx']] ?? 'Unknown'; ?></td>
                <td><?php echo formatLargeNumber($char['Wonbo']); ?></td>
                <td><?php echo formatLargeNumber($char['Gold']); ?></td>
                <td><?php echo formatPlaytime($char['PlaytimeSec']); ?></td>
                <td><?php echo formatLargeNumber($char['CombatPoint']); ?></td>
                <td><?php echo formatLargeNumber($char['EnergyPoint']); ?></td>
                <td><?php echo formatLargeNumber($char['BlackIron']); ?></td>
                <td><?php echo formatLargeNumber($char['ActionPoint']); ?></td>
                <td><?php echo formatLargeNumber($char['AncientCoin']); ?></td>
                <td><?php echo $char['PKPoint']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
</body>
</html>
