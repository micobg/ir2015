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
    $dic = new Document('files/text_001_2.txt');
} catch (Exception $ex) {
    echo 'Exception: ' + $ex->getMessage();
}
?>
        
        
    </body>
</html>
