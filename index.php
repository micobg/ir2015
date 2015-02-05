<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>sdf</title>
    </head>
    <body>
        <h1>ir2015 търсачка</h1>
        <form action="index.php" method="get">
            <input type="text" name="search" placeholder="Търсене..." />
            <input type="submit" value="Търси" />
        </form>
<?php
require_once 'base.php';

$searchValue = urldecode($_GET['search']);
if (!empty($searchValue)) {
    $searchEngine = new SearchEngine();
    $searchEngine->search($searchValue);
} else {
    
}

try {
//    $doc = new Document('text_001_2.txt');
//    $doc->setup();
} catch (Exception $ex) {
    echo 'Exception: ' + $ex->getMessage();
}
?>
        
        
    </body>
</html>
