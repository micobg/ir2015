<?php
require_once 'base.php';

$searchValue = urldecode($_GET['search']);

?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>sdf</title>
        <script>
            function showHideContent(docId) {
                var el = document.getElementById('content_' + docId),
                    currentStatus = el.style.display,
                    newStatus = currentStatus === 'none' ? 'block' : 'none';

                el.style.display = newStatus;
            }
        </script>
    </head>
    <body>
        <h1>ir2015 търсачка</h1>
        <form action="index.php" method="get">
            <input type="text" name="search" placeholder="Търсене..." value="<?php echo $searchValue; ?>" />
            <input type="submit" value="Търси" />
        </form>
<?php

if (!empty($searchValue)) {
    $searchEngine = new SearchEngine();
    $searchResult = $searchEngine->search($searchValue);

    if (empty($searchResult)) {
        ?>
        Няма резултати.
        <?php
    } else {
        ?>
        <ul style="list-style: none;">
            <?php
            foreach ($searchResult as $result) {
                $encoding = mb_detect_encoding($result['content']);
                $start = mb_strlen($result['title'], $encoding);
                ?>
                <li>
                    <strong><?php echo $result['title']; ?></strong><br/>
                    <?php echo trim(mb_substr($result['content'], $start, 300, $encoding)); ?>...
                    <p style="text-decoration: underline; color: blue; cursor: pointer;  "
                          onclick="showHideContent(<?php echo $result['id']; ?>)">Покажи/скрии целия текст</p>

                    <div style="background-color: #eee; display: none;" id="content_<?php echo $result['id']; ?>">
                        <?php echo preg_replace('/\W(' . implode(')|(', $searchEngine->getSearchWords()) . ')\W/ui', ' <strong style="color: red; ">$0</strong> ', $result['content']); ?>
                    </div>
                </li>
            <?php
            }
            unset($result);
            ?>
        </ul>
    <?php
    }
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
