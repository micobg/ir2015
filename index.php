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
        <p>Няма резултати.</p>
        <?php
    } else {
        ?>
        <ul style="list-style: none;">
            <?php
            foreach ($searchResult as $result) {
                $encoding = mb_detect_encoding($result['content']);
                ?>
                <li style="border-bottom: 1px solid #000; margin-bottom: 15px;">
                    <strong><?php echo $result['title']; ?></strong><br />
                    <em style="color: green;"><?php echo $result['file_name']; ?></em><br/>
                    <?php echo $result['summary']; ?>
                    <p style="text-decoration: underline; color: blue; cursor: pointer;  "
                          onclick="showHideContent(<?php echo $result['id']; ?>)">Покажи/скрии целия текст</p>

                    <div style="padding: 15px; background-color: #eee; display: none;" id="content_<?php echo $result['id']; ?>">
                        <?php echo $result['content']; ?>
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
    ?>
        <p>Можете да търсите сред <strong><?php echo Helper::getCountOfDocuments(); ?></strong> документа с <strong><?php echo Helper::getCountOfTerms(); ?></strong> индексирани думи. :)</p>
    <?php
}
    
?>
        
        
    </body>
</html>
