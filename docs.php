<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Documents</title>
    </head>
    <body>

<?php
require_once 'base.php';

$error = '';
$getFile = urldecode($_GET['file']);
if (!empty($getFile)) {
    // run for specific file
    try {
        $doc = new Document($getFile);
        $doc->setup();
    } catch (Exception $ex) {
        $error = 'Exception: ' + $ex->getMessage();
    }
    
    if(empty($error)) {
    ?>
        Файлът <strong><?php echo $getFile; ?></strong> е индексиран успешно!
    <?php
    } else {
        echo $error;
    }
    ?>
        <br />
        <a href="<?php echo BASE_URL . 'docs.php'; ?>">Към списъка с файловете за индексиране</a> | 
        <a href="<?php echo BASE_URL; ?>">Към търсачката</a>
    <?php
} else {
    $documentsManager = new DocumentsManager();
    $files = $documentsManager->getUnindexedFiles();

    if (!empty($files)) {
    ?>
    Изберете файл за индексиране:<br />
        <ul>
            <?php 
            foreach ($files as $file) {
            ?>
            <li><a href="<?php echo BASE_URL . 'docs.php?file=' . urlencode($file); ?>" alt="Индексирай"><?php echo $file; ?></a></li>
            <?php
            }
            ?>
        </ul>
    <?php
    } else {
    ?>
    Няма фалйвое за индексиране.
    <?php
    }
    ?>
    <br >
    <a href="<?php echo BASE_URL; ?>">Към търсачката</a>
    <?php
}


?>


    </body>
</html>
