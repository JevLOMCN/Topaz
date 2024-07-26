<?php

require_once 'config/base.php';

?>


<head>

<title><?php echo $current_translations['title-globalrank']; ?></title>




<style>


</style>






</head>



<body>

<div class="container">




<h1>Mir 4 Global Rankings</h1>


<div class="flex-container">

<div class="container halfsection">

<h2> Select a class </h2>



<a href="?ranktype=1000&worldgroupid=&worldid=&classtype=1&searchname=&globalSearch=">
    <input type="image" alt="Warriors" src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_1.png" width="15%">
</a>

<a href="?ranktype=1000&worldgroupid=&worldid=&classtype=2&searchname=&globalSearch=">
    <input type="image" alt="Sorcerers" src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_2.png" width="15%">
</a>

<a href="?ranktype=1000&worldgroupid=&worldid=&classtype=3&searchname=&globalSearch=">
    <input type="image" alt="Taoist" src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_3.png" width="15%">
</a>

<a href="?ranktype=1000&worldgroupid=&worldid=&classtype=4&searchname=&globalSearch=">
    <input type="image" alt="Arbalists" src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_4.png" width="15%">
</a>

<a href="?ranktype=1000&worldgroupid=&worldid=&classtype=5&searchname=&globalSearch=">
    <input type="image" alt="Lancers" src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_5.png" width="15%">
</a>

<a href="?ranktype=1000&worldgroupid=&worldid=&classtype=6&searchname=&globalSearch=">
    <input type="image" alt="Darkists" src="https://mir4-live-hp.wemade.com/mir4-forum/img/desktop/temp/char_6.png" width="15%">
</a>


</div>

<div class="container halfsection">
<form method="GET" action="">
<label for="search">Search by Name:</label>
<input type="text" name="search" id="search">
<input type="submit" value="Search">
</form>
</div>


</div>









<?php



    function getCharacterClass($image_url) {
        $class_map = [
            'char_1.png' => 'Warrior',
            'char_2.png' => 'Sorcerer',
            'char_3.png' => 'Taoist',
            'char_4.png' => 'Arbalist',
            'char_5.png' => 'Lancer',
            'char_6.png' => 'Darkist'
        ];
        $filename = basename($image_url);
        return $class_map[$filename] ?? 'Unknown';
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $base_url = 'https://forum.mir4global.com/rank?';
        $query = '';
        $page = $_GET['page'] ?? 1;
        $num_pages = $_GET['pages'] ?? 1;


        // Collect query parameters
        $params = [
            'ranktype' => $_GET['ranktype'] ?? '',
            'worldgroupid' => '',
            'worldid' => '',
            'classtype' => $_GET['classtype'] ?? '', // Assuming this is always empty
            'searchname' => $_GET['search'] ?? '',
            'globalSearch' => '', // Assuming this is always empty
            'page' => $page,
            // Add other variables as needed
        ];

        if ($params['classtype'] !== '' && $params['ranktype'] == ''){
            $params['ranktype'] = '1000';
        }
        // Check if no options are selected - default query
        if (empty($params['ranktype']) && empty($params['worldgroupid']) && empty($params['worldid']) && empty($params['searchname'])) {
            $params['ranktype'] = '1';
            $params['worldgroupid'] = '2';
            $params['worldid'] = '110';
        }

        if (!empty($params['searchname'])) {
            $params['ranktype'] = '1';
        }

                // Build query string
        foreach ($params as $key => $value) {
            if (!empty($value)) {
                $query .= '&' . $key . '=' . urlencode($value);
            }
        }

        echo '<table>';
        echo '<thead><tr>';

        // Fetch the first page to get the headers
        $params['page'] = 1;
        $query = http_build_query($params);
        $url = $base_url . $query;

        $html = @file_get_contents($url);
        if ($html !== FALSE) {
            // Load the HTML into DOMDocument
            $dom = new DOMDocument;
            @$dom->loadHTML($html);

            // Extract the table headers
            $tables = $dom->getElementsByTagName('table');
            foreach ($tables as $table) {
                if ($table->getAttribute('class') == 'ranking_table') {
                    //$headers = $table->getElementsByTagName('th');   - attempt to get coloumn headers dynamicly - works-ish
                    //foreach ($headers as $header) {
                    //    echo '<th>' . trim($header->nodeValue) . '</th>';
                    //}


                    if ($params['classtype'] !== "" || $params['ranktype'] == "1000"){
                        echo '<th>Ranking</th>'; // Add a new column
                        //echo '<th>Change</th>'; // Add a new column 
                        //echo '<th> </th>'; // Add a new blank column
                        echo '<th>Name</th>'; 
                        echo '<th>Avatar</th>'; 
                        echo '<th>Class</th>'; 
                        echo '<th>Server</th>'; 
                        echo '<th>Clan</th>'; 
                        echo '<th>PowerScore</th>'; 
                    }
                    elseif ($params['ranktype'] == "4"){
                        echo '<th>Ranking</th>'; 
                        echo '<th>Clan Name</th>'; 
                        echo '<th>Image</th>'; 
                        echo '<th>Leader Class</th>'; 
                        echo '<th>Leader Name</th>';
                        echo '<th>Guild Power</th>'; 
                    }
                    else {
                        echo '<th>Ranking</th>'; 
                        echo '<th>Name</th>'; 
                        echo '<th>Avatar</th>'; 
                        echo '<th>Class</th>'; 
                        echo '<th>Clan</th>'; 
                        echo '<th>PowerScore</th>'; 

                    }
                    
                    break;
                }
            }
        }

        echo '</tr></thead>';
        echo '<tbody>';

        for ($i = 1; $i <= $num_pages; $i++) {
            $params['page'] = $i;
            $query = http_build_query($params);
            $url = $base_url . $query;

            $html = @file_get_contents($url);
            if ($html !== FALSE) {
                // Load the HTML into DOMDocument
                $dom = new DOMDocument;
                @$dom->loadHTML($html);

                // Extract the table data
                $tables = $dom->getElementsByTagName('table');
                foreach ($tables as $table) {
                    if ($table->getAttribute('class') == 'ranking_table') {
                        $rows = $table->getElementsByTagName('tr');
                        foreach ($rows as $row) {
                            $row_class = $row->getAttribute('class');
                            if (strpos($row_class, 'list_article') !== false) {
                                echo '<tr>';
                                $cells = $row->getElementsByTagName('td');
                                $cell_data = [];
                                foreach ($cells as $cell) {
                                    if ($cell->getElementsByTagName('span')->length > 0) {
                                        $spans = $cell->getElementsByTagName('span');
                                        foreach ($spans as $span) {
                                            if ($span->getAttribute('class') == 'user_icon') {
                                                $style = $span->getAttribute('style');
                                                preg_match('/url\((.*?)\)/', $style, $matches);
                                                $image_url = $matches[1];
                                                $cell_data[] = '<img src="' . $image_url . '" alt="Character Image" class="user_icon">';
                                                $cell_data[] = getCharacterClass($image_url); // Add character class
                                            } else {
                                                $cell_data[] = trim($span->nodeValue);
                                            }
                                        }
                                    } else {
                                        $cell_data[] = trim($cell->nodeValue);
                                    }
                                }
                                // Display the data in the columns
                                echo '<td align="center">' . $cell_data[0] . '</td>'; // Ranking
                                //echo '<td align="center">' . $cell_data[1] . '</td>'; // Rank change 
                                //echo '<td align="center">' . $cell_data[2] . '</td>'; // Rank change icon 
                                echo '<td align="center">' . $cell_data[3] . '</td>'; 


                                if (!$params['ranktype'] == "4" || $params['ranktype'] == "1" || $params['ranktype'] == "1000" ){
                                    echo '<td align="center">' . $cell_data[4] . '</td>'; 
                                }

                                
                                echo '<td align="center">' . $cell_data[5] . '</td>'; 

                                if ($params['ranktype'] == "4" ){
                                    echo '<td align="center">' . $cell_data[6] . '</td>'; 
                                }

                                if ($cell_data[7] == "-") {
                                    $cell_data[7] = $cell_data[8];
                                    $cell_data[8] = $cell_data[9]; 
                                }

                                echo '<td align="center">' . $cell_data[7] . '</td>'; 
                                echo '<td align="center">' . $cell_data[8] . '</td>'; 
                                
                                if ($params['classtype'] !== "" || $params['ranktype'] == "1000") {
                                    echo '<td align="center">' . $cell_data[9] . '</td>'; 
                                }
                                echo '</tr>';
                            }
                        }
                    }
                }
            }
        }

        echo '</tbody>';
        echo '</table>';




    }
    ?>




















</div>






</body>