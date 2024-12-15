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

// Load JSON file
$itemData = loadJsonCached('Database/ITEM.json', 'cache_item_data');

if (!$itemData) {
    die("Failed to load JSON file.");
}

// Class, grade, type, tradable, and tier maps
$classMap = [
    0 => "All Classes",
    1 => "Warrior",
    2 => "Sorcerer",
    3 => "Taoist",
    4 => "Arbalist",
    5 => "Lancer",
    6 => "Darkist"
];
$gradeMap = [0 => "All Grades", 1 => "Common", 2 => "Uncommon", 3 => "Rare", 4 => "Epic", 5 => "Legendary"];
$typeMap = [
    0 => "All Types",
    2 => "Weapon",
    20 => "Secondary Weapon",
    3 => "Drapery",
    4 => "Jewelry",
    5 => "Upgrade Material",
    6 => "Sundry",
    7 => "Constitution/Pills",
    8 => "Magic Stone",
    9 => "Crafting",
    10 => "Mystery",
    11 => "Outfit",
    12 => "Skill Tome",
    13 => "Codex",
    14 => "Quest Item",
    17 => "Treasure",
    18 => "Summon Ticket",
    21 => "Mystical Piece"
];
$tradableMap = [0 => "All", 1 => "Tradable"];
$tierMap = [0 => "All", 1 => "Tier 1", 2 => "Tier 2", 3 => "Tier 3", 4 => "Tier 4"];
$languages = ['ENG' => 'English', 'CHT' => 'Traditional Chinese', 'CHS' => 'Simplified Chinese', 'JPN' => 'Japanese', 'THA' => 'Thai', 'IND' => 'Indonesian', 'VIE' => 'Vietnamese', 'GER' => 'German', 'SPA' => 'Spanish', 'POR' => 'Portuguese', 'RUS' => 'Russian', 'TextKr' => 'Korean'];

// Filters
$selectedClass = isset($_GET['class']) ? (int)$_GET['class'] : 0;
$selectedGrade = isset($_GET['grade']) ? (int)$_GET['grade'] : 0;
$selectedType = isset($_GET['type']) ? (int)$_GET['type'] : 0;
$selectedTier = isset($_GET['tier']) ? (int)$_GET['tier'] : 0; // Default to Tier 1
$selectedTradable = isset($_GET['tradable']) ? (int)$_GET['tradable'] : 0;
$itemIdFilter = isset($_GET['itemid_filter']) ? trim($_GET['itemid_filter']) : '';
$nameFilter = isset($_GET['name']) ? trim($_GET['name']) : '';
$selectedLanguage = isset($_GET['lang']) && array_key_exists($_GET['lang'], $languages) ? $_GET['lang'] : 'ENG';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$itemsPerPage = 60;

// Check if file exists, fallback to placeholder
function getIconPath($iconId) {
    $iconPath = "Database/Icons/Items/{$iconId}.png";
    return file_exists($iconPath) ? $iconPath : "Database/Icons/Items/13.png";
}

// Filter items
$filteredItems = array_filter($itemData, function ($item) use ($selectedClass, $selectedGrade, $selectedType, $selectedTier, $selectedTradable, $itemIdFilter, $nameFilter, $selectedLanguage) {
    $classMatch = $selectedClass === 0 || $item['ClassId'] == $selectedClass;
    $gradeMatch = $selectedGrade === 0 || $item['Grade'] == $selectedGrade;
    $typeMatch = $selectedType === 0 || $item['MainType'] == $selectedType;
    $tierMatch = $selectedTier === 0 || ($item['Tier'] >= 1 && $item['Tier'] <= 4 && $item['Tier'] == $selectedTier); // Valid tiers 1-4
    $tradableMatch = $selectedTradable === 0 || $item['TradeType'] == $selectedTradable;
    $itemIdMatch = empty($itemIdFilter) || strpos((string)$item['ItemId'], $itemIdFilter) !== false;
    $itemName = isset($item['translations'][$selectedLanguage]) ? $item['translations'][$selectedLanguage] : "Unknown";
    $nameMatch = empty($nameFilter) || stripos($itemName, $nameFilter) !== false;

    return $classMatch && $gradeMatch && $typeMatch && $tierMatch && $tradableMatch && $itemIdMatch && $nameMatch;
});

// Pagination
$totalItems = count($filteredItems);
$totalPages = ceil($totalItems / $itemsPerPage);
$offset = ($page - 1) * $itemsPerPage;
$itemsOnPage = array_slice($filteredItems, $offset, $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVA Mir 4 Item Database</title>
    <link rel="icon" href="static/image/favicon.ico" type="image/x-icon">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #fff; background: url('static/image/mir4_background.png') no-repeat center center fixed; background-size: cover; }
        h1 { text-align: center; margin: 20px 0; }
        .filter-container form {
            display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 10px; margin: 20px 0; background: rgba(0, 0, 0, 0.6); padding: 10px; border-radius: 8px;
        }
        .item-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
        .item { position: relative; width: 150px; height: 150px; cursor: pointer; overflow: hidden; border-radius: 10px; text-align: center; }
        .item img.backdrop { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .item img.icon { position: relative; width: 80px; height: 80px; margin-top: 20px; }
        .item img.tradeable-icon { position: absolute; top: 5px; left: 5px; width: 20px; height: 20px; z-index: 5; }
        .item p { position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); background-color: rgba(0, 0, 0, 0.6); padding: 5px; border-radius: 5px; font-size: 12px; }
        .pagination { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; margin: 20px; }
        .pagination a { text-decoration: none; color: #fff; background-color: #333; padding: 8px 12px; border-radius: 5px; }
        .pagination a.active, .pagination a:hover { background-color: #555; color: #ffcc00; }
        .nav-button { margin-left: 10px; text-decoration: none; background-color: #333; color: #ffcc00; padding: 8px 12px; border-radius: 5px; font-weight: bold; }
        .button-container { display: flex; justify-content: flex-end; gap: 10px; }
    </style>
</head>
<body>
    <h1>AVA Mir 4 Item Database</h1>

    <!-- Filters -->
    <div class="filter-container">
        <form method="GET">
            <label>Class:</label>
            <select name="class" onchange="this.form.submit()">
                <?php foreach ($classMap as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $selectedClass === $id ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Grade:</label>
            <select name="grade" onchange="this.form.submit()">
                <?php foreach ($gradeMap as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $selectedGrade === $id ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Type:</label>
            <select name="type" onchange="this.form.submit()">
                <?php foreach ($typeMap as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $selectedType === $id ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Tier:</label>
            <select name="tier" onchange="this.form.submit()">
                <?php foreach ($tierMap as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $selectedTier === $id ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Tradable:</label>
            <select name="tradable" onchange="this.form.submit()">
                <?php foreach ($tradableMap as $id => $name): ?>
                    <option value="<?php echo $id; ?>" <?php echo $selectedTradable === $id ? 'selected' : ''; ?>><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="itemid_filter" placeholder="Item ID" value="<?php echo htmlspecialchars($itemIdFilter); ?>">
            <input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($nameFilter); ?>">
            <button type="submit">Search</button>
            <label>Language:</label>
            <select name="lang" onchange="this.form.submit()">
                <?php foreach ($languages as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $selectedLanguage === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <a href="monsters.php" class="nav-button">Monster Database</a> <!-- Button placed here -->
        </form>
    </div>

    <!-- Items -->
    <div class="item-container">
        <?php foreach ($itemsOnPage as $item): ?>
            <?php 
                $backdrop = "Database/Icons/Items/grade-{$item['Grade']}.webp";
                $iconPath = getIconPath($item['Icon']);
                $itemName = $item['translations'][$selectedLanguage] ?? "Unknown";
            ?>
            <div class="item" onclick="copyToClipboard('@cheatmsg makeitem <?php echo $item['ItemId']; ?> 1')">
                <?php if ($item['TradeType'] == 1): ?>
                    <img src="Database/Icons/Items/tradeable.png" alt="Tradable" class="tradeable-icon">
                <?php endif; ?>
                <img src="<?php echo htmlspecialchars($backdrop); ?>" alt="Grade Backdrop" class="backdrop">
                <img src="<?php echo htmlspecialchars($iconPath); ?>" alt="Item Icon" class="icon">
                <p><?php echo htmlspecialchars($itemName); ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?class=<?php echo $selectedClass; ?>&grade=<?php echo $selectedGrade; ?>&type=<?php echo $selectedType; ?>&tier=<?php echo $selectedTier; ?>&tradable=<?php echo $selectedTradable; ?>&lang=<?php echo $selectedLanguage; ?>&itemid_filter=<?php echo urlencode($itemIdFilter); ?>&name=<?php echo urlencode($nameFilter); ?>&page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard!');
            });
        }
    </script>
</body>
</html>
