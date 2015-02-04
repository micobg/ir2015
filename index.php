<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>sdf</title>
    </head>
    <body>

<?php
require_once 'base.php';

try {
    $doc = new Document('text_001_2.txt');
    $doc->setup();
} catch (Exception $ex) {
    echo 'Exception: ' + $ex->getMessage();
}
?>
        
        
    </body>
</html>
