<?php
// Function to load and cache JSON data
function loadJsonCached($filePath, $cacheKey) {
    if (function_exists('apcu_fetch') && apcu_exists($cacheKey)) {
        return apcu_fetch($cacheKey);
    } else {
        static $cache = [];
        if (!isset($cache[$cacheKey])) {
            $data = json_decode(file_get_contents($filePath), true);
            $cache[$cacheKey] = $data;
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $data, 300);
            }
        }
        return $cache[$cacheKey];
    }
}

// Load achievements data
$achievementData = loadJsonCached('Database/ACHIEVEMENT.json', 'cache_achievement_data');
if (!$achievementData) { die("Failed to load ACHIEVEMENT JSON file."); }

// Load item data
$itemData = loadJsonCached('Database/ITEM.json', 'cache_item_data');
if (!$itemData) { die("Failed to load ITEM JSON file."); }

// Helper function to get item image path based on item ID
function getCorrectItemIconPath($itemId, $itemData) {
    foreach ($itemData as $item) {
        if ($item['ItemId'] == $itemId) {
            $iconId = $item['Icon'] ?? 13;
            $iconPath = "Database/Icons/Items/{$iconId}.png";
            return file_exists($iconPath) ? $iconPath : "Database/Icons/Items/13.png";
        }
    }
    return "Database/Icons/Items/13.png";
}

// Helper function to get item name
function getItemName($itemId, $itemData) {
    foreach ($itemData as $item) {
        if ($item['ItemId'] == $itemId) {
            return $item['translations']['ENG'] ?? "Unknown Item";
        }
    }
    return "Unknown Item";
}

// Filters
$nameFilter = isset($_GET['name']) ? trim($_GET['name']) : '';
$selectedLanguage = isset($_GET['lang']) ? $_GET['lang'] : 'ENG';
$languages = ['ENG' => 'English', 'CHT' => 'Traditional Chinese', 'CHS' => 'Simplified Chinese', 'JPN' => 'Japanese', 'THA' => 'Thai', 'IND' => 'Indonesian', 'VIE' => 'Vietnamese', 'GER' => 'German', 'SPA' => 'Spanish', 'POR' => 'Portuguese', 'RUS' => 'Russian', 'TextKr' => 'Korean'];

// Filter achievements
$filteredAchievements = array_filter($achievementData, function ($achievement) use ($nameFilter, $selectedLanguage) {
    $achievementName = $achievement['translations'][$selectedLanguage] ?? "Unknown";
    return empty($nameFilter) || stripos($achievementName, $nameFilter) !== false;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVA Mir 4 Achievements</title>
    <link rel="icon" href="static/image/favicon.ico" type="image/x-icon">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #fff; background: url('static/image/mir4_background.png') no-repeat center center fixed; background-size: cover; }
        h1 { text-align: center; margin: 20px 0; }
        .header { display: flex; justify-content: center; align-items: center; gap: 15px; margin: 20px; }
        .header a { text-decoration: none; background-color: #333; color: #ffcc00; padding: 8px 12px; border-radius: 5px; font-weight: bold; }
        .header a:hover { background-color: #555; color: #fff; }
        .achievement-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; margin: 20px; }
        .achievement-box { width: 250px; padding: 15px; background: rgba(0, 0, 0, 0.6); border-radius: 10px; text-align: center; }
        .reward-box { display: flex; justify-content: space-around; align-items: center; margin-top: 10px; }
        .reward-box img { width: 30px; height: 30px; }
        .reward-box span { font-size: 12px; color: #fff; }
        form { display: flex; justify-content: center; gap: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>AVA Mir 4 Achievements</h1>

    <!-- Header with navigation buttons -->
    <div class="header">
        <form method="GET">
            <input type="text" name="name" placeholder="Search Achievements" value="<?php echo htmlspecialchars($nameFilter); ?>">
            <label for="lang">Language:</label>
            <select name="lang" id="lang" onchange="this.form.submit()">
                <?php foreach ($languages as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $selectedLanguage === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Search</button>
        </form>
        <a href="database.php">Item Database</a>
        <a href="monsters.php">Monster Database</a>
    </div>

    <!-- Achievements -->
    <div class="achievement-container">
        <?php foreach ($filteredAchievements as $achievement): ?>
            <div class="achievement-box">
                <h3><?php echo htmlspecialchars($achievement['translations'][$selectedLanguage] ?? 'Unknown Achievement'); ?></h3>
                <p>Complete Count: <?php echo $achievement['CompleteCount']; ?></p>

                <div class="reward-box">
                    <!-- EXP -->
                    <?php if (!empty($achievement['RewardAchievementExp'])): ?>
                        <div>
                            <img src="Database/Icons/Items/EXP.png" alt="EXP">
                            <span><?php echo $achievement['RewardAchievementExp']; ?> EXP</span>
                        </div>
                    <?php endif; ?>

                    <!-- Gold -->
                    <?php if (!empty($achievement['RewardGOLD'])): ?>
                        <div>
                            <img src="Database/Icons/Items/2.png" alt="Gold">
                            <span><?php echo $achievement['RewardGOLD']; ?> Copper</span>
                        </div>
                    <?php endif; ?>

                    <!-- RewardItemId01 -->
                    <?php if (!empty($achievement['RewardItemId01']) && $achievement['RewardItemId01'][0] != 0): ?>
                        <?php 
                            $itemId01 = $achievement['RewardItemId01'][0];
                            $itemIconPath01 = getCorrectItemIconPath($itemId01, $itemData);
                            $itemName01 = getItemName($itemId01, $itemData);
                        ?>
                        <div>
                            <img src="<?php echo $itemIconPath01; ?>" alt="Reward 1">
                            <span>x<?php echo $achievement['RewardItemCount01']; ?> - <?php echo $itemName01; ?></span>
                        </div>
                    <?php endif; ?>

                    <!-- RewardItemId02 -->
                    <?php if (!empty($achievement['RewardItemId02']) && $achievement['RewardItemId02'][0] != 0): ?>
                        <?php 
                            $itemId02 = $achievement['RewardItemId02'][0];
                            $itemIconPath02 = getCorrectItemIconPath($itemId02, $itemData);
                            $itemName02 = getItemName($itemId02, $itemData);
                        ?>
                        <div>
                            <img src="<?php echo $itemIconPath02; ?>" alt="Reward 2">
                            <span>x<?php echo $achievement['RewardItemCount02']; ?> - <?php echo $itemName02; ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
