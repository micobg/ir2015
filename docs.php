<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Documents</title>
    </head>
    <body>

<?php
require_once 'base.php';

/**
 * Return list of all files in given dir (and sub dirs recursively)
 *
 * @param $dir
 * @return array
 */
function dirToArray($dir) {
    $result = array();

    $ls = scandir($dir);
    foreach ($ls as $value)
    {
        if (!in_array($value, array('.', '..')))
        {
            if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
            {
                $result[] = $value . DIRECTORY_SEPARATOR . dirToArray($dir . DIRECTORY_SEPARATOR . $value);
            }
            else
            {
                $result[] = $value;
            }
        }
    }

    return $result;
}



$error = '';
$fileName = htmlspecialchars($_GET['filename']);

if (!empty($fileName)) {
    // run for specific file
    try {
        $doc = new Document('text_001_2.txt');
        $doc->setup();
    } catch (Exception $ex) {
        $error = 'Exception: ' + $ex->getMessage();
    }
} else {
    // list all files that are not indexed
    $allFiles = dirToArray(FILES_DIR);
}


?>


    </body>
</html>
