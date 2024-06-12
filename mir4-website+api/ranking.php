<?php
header('Content-type: text/html; charset=utf-8');
session_start();
include 'db.php';
$translations = include 'translations.php';

if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $translations)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
$lang = array_key_exists($lang, $translations) ? $lang : 'en';
$current_translations = $translations[$lang];

function getRanking($column, $limit) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT CharacterName, $column FROM character_tb ORDER BY $column DESC LIMIT :limit");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$selectedRanking = isset($_POST['rankingType']) ? $_POST['rankingType'] : 'Lev';
$selectedLimit = isset($_POST['rankingLimit']) ? (int)$_POST['rankingLimit'] : 10;
$rankingData = getRanking($selectedRanking, $selectedLimit);
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $current_translations['title']; ?></title>
    
    <link rel="stylesheet" href="static/css/nanumsquare.css"> <!-- Adapte o caminho do arquivo CSS conforme necessário -->
    <link rel="stylesheet" href="static/css/common.css"> <!-- Adapte o caminho do arquivo CSS conforme necessário -->
    <style>
        /* Adicione estilos adicionais aqui, se necessário */
        body {
            font-family: 'NanumSquare', sans-serif; /* Use a mesma fonte da página de registro */
            margin: 0;
            padding: 0;
            background-image: url('static/image/mir4_background.png'); /* Use a mesma imagem de fundo da página de registro */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            overflow-x: hidden; /* Impede a rolagem horizontal */
        }

        .container {
            max-width: 400px; /* Adapte a largura conforme necessário */
            margin: 50px auto;
            background-color: rgba(255, 255, 255, 0.8);
            padding: 32px;
            border-radius: 19px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            max-width: 100%;
            height: auto;
        }

        .language-selector {
            text-align: center;
            margin-bottom: 20px;
        }

        .language-selector a {
            margin: 0 5px;
            color: #007bff; /* Cor do link */
            text-decoration: none;
        }

        .language-selector a:hover {
            text-decoration: underline;
        }

        form {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            font-size: 16px;
        }

        select, button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }

        button {
            background-color: #007bff; /* Cor de fundo do botão */
            color: #fff; /* Cor do texto do botão */
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3; /* Cor de fundo do botão ao passar o mouse */
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="/static/img/Logo.png" alt="Ava MIR4" class="logo">
    </div>
    <div class="language-selector">
        <a href="?lang=en">English</a> |
        <a href="?lang=es">Español</a> |
        <a href="?lang=pt">Português</a> |
        <a href="?lang=tl">Tagalog</a>
    </div>
    <form action="" method="post">
        <div class="form-group">
            <label for="rankingType"><?php echo $current_translations['choose_ranking']; ?></label>
            <select name="rankingType" id="rankingType">
                <?php foreach ($current_translations['ranking_keys'] as $key => $translation): ?>
                    <option value="<?php echo $key; ?>" <?php echo $selectedRanking === $key ? 'selected' : ''; ?>><?php echo $translation; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="rankingLimit"><?php echo $current_translations['select_quantity']; ?></label>
            <select name="rankingLimit" id="rankingLimit">
                <option value="10" <?php echo $selectedLimit === 10 ? 'selected' : ''; ?>><?php echo $current_translations['ranking_limit_10']; ?></option>
                <option value="50" <?php echo $selectedLimit === 50 ? 'selected' : ''; ?>><?php echo $current_translations['ranking_limit_50']; ?></option>
            </select>
        </div>
        <button type="submit"><?php echo $current_translations['update']; ?></button>
    </form>
    <h1><?php echo $current_translations['ranking_keys'][$selectedRanking] . " - Top {$selectedLimit}"; ?></h1>

    <table>
    <tr><th><?php echo $current_translations['character_name']; ?></th><th><?php echo $current_translations['ranking_keys'][$selectedRanking]; ?></th></tr>

        <?php
        foreach ($rankingData as $char) {
            echo "<tr><td>{$char['CharacterName']}</td><td>{$char[$selectedRanking]}</td></tr>";
        }
        ?>
    </table>
</div>
</body>
</html>
