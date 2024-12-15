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
$monsterData = loadJsonCached('Database/MONSTER.json', 'cache_monster_data');

if (!$monsterData) {
    die("Failed to load JSON file.");
}

// Race, grade, and sort maps
$raceMap = [0 => "All Races", 1 => "Race 1", 2 => "Race 2", 3 => "Race 3", 4 => "Race 4", 5 => "Race 5"];
$gradeMap = [0 => "All Grades", 1 => "Grade 1", 2 => "Grade 2", 3 => "Grade 3", 4 => "Grade 4", 5 => "Grade 5", 6 => "Grade 6"];
$sortOptions = [
    'level_asc' => "Level (Low to High)",
    'level_desc' => "Level (High to Low)",
    'combat_asc' => "Combat Power (Low to High)",
    'combat_desc' => "Combat Power (High to Low)"
];
$languages = [
    'ENG' => 'English',
    'CHT' => 'Traditional Chinese',
    'CHS' => 'Simplified Chinese',
    'JPN' => 'Japanese',
    'THA' => 'Thai',
    'IND' => 'Indonesian',
    'VIE' => 'Vietnamese',
    'GER' => 'German',
    'SPA' => 'Spanish',
    'POR' => 'Portuguese',
    'RUS' => 'Russian'
];

// Filters
$selectedRace = isset($_GET['race']) ? (int)$_GET['race'] : 0;
$selectedGrade = isset($_GET['grade']) ? (int)$_GET['grade'] : 0;
$selectedSort = isset($_GET['sort']) ? $_GET['sort'] : 'level_desc';
$monsterIdFilter = isset($_GET['monsterid']) ? trim($_GET['monsterid']) : '';
$nameFilter = isset($_GET['name']) ? trim($_GET['name']) : '';
$selectedLanguage = isset($_GET['lang']) && array_key_exists($_GET['lang'], $languages) ? $_GET['lang'] : 'ENG';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$itemsPerPage = 60;

// Check if file exists, fallback to placeholder
function getImagePath($monsterId) {
    $imagePath = "Database/Icons/Monsters/{$monsterId}.png";
    return file_exists($imagePath) ? $imagePath : "Database/Icons/Monsters/404.png";
}

// Filter monsters
$filteredMonsters = array_filter($monsterData, function ($monster) use ($selectedRace, $selectedGrade, $monsterIdFilter, $nameFilter, $selectedLanguage) {
    $raceMatch = $selectedRace === 0 || $monster['Race'] == $selectedRace;
    $gradeMatch = $selectedGrade === 0 || $monster['MonGrade'] == $selectedGrade;
    $monsterIdMatch = empty($monsterIdFilter) || strpos((string)$monster['MonsterId'], $monsterIdFilter) !== false;
    $monsterName = $monster['translations'][$selectedLanguage] ?? "Unknown";
    $nameMatch = empty($nameFilter) || stripos($monsterName, $nameFilter) !== false;

    return $raceMatch && $gradeMatch && $monsterIdMatch && $nameMatch;
});

// Sort monsters
usort($filteredMonsters, function ($a, $b) use ($selectedSort) {
    switch ($selectedSort) {
        case 'level_asc':
            return $a['Level'] <=> $b['Level'];
        case 'level_desc':
            return $b['Level'] <=> $a['Level'];
        case 'combat_asc':
            return $a['CombatPower'] <=> $b['CombatPower'];
        case 'combat_desc':
            return $b['CombatPower'] <=> $a['CombatPower'];
        default:
            return 0;
    }
});

// Pagination
$totalMonsters = count($filteredMonsters);
$totalPages = ceil($totalMonsters / $itemsPerPage);
$offset = ($page - 1) * $itemsPerPage;
$monstersOnPage = array_slice($filteredMonsters, $offset, $itemsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AVA Mir 4 Monster Database</title>
    <link rel="icon" href="static/image/favicon.ico" type="image/x-icon">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #fff; background: url('static/image/mir4_background.png') no-repeat center center fixed; background-size: cover; }
        h1 { text-align: center; margin: 20px 0; }
        .filter-container form {
            display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 10px; margin: 20px; background: rgba(0, 0, 0, 0.6); padding: 10px; border-radius: 8px;
        }
        .item-database-button {
            text-decoration: none; background-color: #333; color: #ffcc00; padding: 8px 12px; border-radius: 5px; font-weight: bold; transition: background-color 0.3s, color 0.3s;
        }
        .item-database-button:hover { background-color: #555; color: #fff; }
        .monster-container { display: flex; flex-wrap: wrap; justify-content: center; gap: 20px; }
        .monster { position: relative; width: 150px; height: 150px; cursor: pointer; overflow: hidden; border-radius: 10px; text-align: center; }
        .monster img.icon { width: 100%; height: 100%; }
        .monster p { position: absolute; bottom: 0; left: 0; width: 100%; background-color: rgba(0, 0, 0, 0.6); color: #fff; margin: 0; padding: 5px; font-size: 12px; }
        .pagination { display: flex; flex-wrap: wrap; justify-content: center; gap: 5px; margin: 20px; }
        .pagination a { text-decoration: none; color: #fff; background-color: #333; padding: 8px 12px; border-radius: 5px; }
        .pagination a.active, .pagination a:hover { background-color: #555; color: #ffcc00; }
    </style>
</head>
<body>
    <h1>AVA Mir 4 Monster Database</h1>

    <!-- Filters -->
    <div class="filter-container">
        <form method="GET">
            <label>Race:</label>
            <select name="race" onchange="this.form.submit()">
                <?php foreach ($raceMap as $id => $label): ?>
                    <option value="<?php echo $id; ?>" <?php echo $selectedRace === $id ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Grade:</label>
            <select name="grade" onchange="this.form.submit()">
                <?php foreach ($gradeMap as $id => $label): ?>
                    <option value="<?php echo $id; ?>" <?php echo $selectedGrade === $id ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <label>Sort:</label>
            <select name="sort" onchange="this.form.submit()">
                <?php foreach ($sortOptions as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $selectedSort === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="monsterid" placeholder="Monster ID" value="<?php echo htmlspecialchars($monsterIdFilter); ?>">
            <input type="text" name="name" placeholder="Monster Name" value="<?php echo htmlspecialchars($nameFilter); ?>">
            <button type="submit">Search</button>
            <label>Language:</label>
            <select name="lang" onchange="this.form.submit()">
                <?php foreach ($languages as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $selectedLanguage === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
            <a href="database.php" class="item-database-button">Item Database</a>
        </form>
    </div>

    <!-- Monsters -->
    <div class="monster-container">
        <?php foreach ($monstersOnPage as $monster): ?>
            <div class="monster" onclick="copyToClipboard('@cheatmsg gen <?php echo $monster['MonsterId']; ?> 1')">
                <img src="<?php echo htmlspecialchars(getImagePath($monster['MonsterId'])); ?>" alt="Monster Icon" class="icon">
                <p><?php echo htmlspecialchars($monster['translations'][$selectedLanguage] ?? 'Unknown'); ?> - Level: <?php echo $monster['Level']; ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
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
