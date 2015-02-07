<?php

/**
 * Manage documents in docs table
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */
class DocumentsManager {
    
    protected $dbConn;

    /**
     * Format results
     *
     * @var type 
     */
    protected $replacePattern = ' <strong style="color: red; ">$0</strong> ';


    public function __construct() {
        $this->dbConn = dbConn::getInstance();
    }
    
    /**
     * Return list of all files from file/ dir that are not indexed
     * 
     * @return array file names of all not indexed files
     */
    public function getNotIndexedFiles() {
        // list all files that are not indexed
        $files = Helper::dirToArray(FILES_DIR);

        // all indexed documents
        $allDocsInDb = __($this->getAllDocuments())->pluck('file_name');

        // remove already indexed files
        foreach ($files as $key => $fileName) {
            if (__($allDocsInDb)->includ(FILES_DIR . $fileName)) {
                unset($files[$key]);
            }
        }
        unset($key);
        unset($fileName);
        
        return $files;
    }
    
    /**
     * Return all indexed documents (from db)
     * 
     * @return array list of all documents
     */
    protected function getAllDocuments() {
        $selectAllDocs = $this->dbConn->prepare("SELECT * FROM docs");
        $selectAllDocs->execute();
        
        return $selectAllDocs->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return all documents by given id
     *
     * @param int $id document id
     *
     * @return array the document
     */
    public function getDocumentsById($id) {
        $selectDoc = $this->dbConn->prepare("SELECT * FROM docs WHERE id = '" . $id . "'");
        $selectDoc->execute();

        return $selectDoc->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Bulid summary of the file
     * 
     * Return substring that include first match of some searched word
     * 
     * @param string $content
     * @param array $searchWords
     * 
     * @return string
     */
    public function getSummary($content, $searchWords) {
        $encoding = mb_detect_encoding($content);
        $matchPattern = $this->getMatchPattern($searchWords);
        
        $firstMatch = array();
        preg_match($matchPattern, $content, $firstMatch, PREG_OFFSET_CAPTURE);
        $startIndex = $firstMatch[0][1] > 100 ? $firstMatch[0][1] - 100 : $firstMatch[0][1];
        $startPosition = mb_strlen(substr($content, 0, $startIndex), $encoding);
        
        $summary = '';
        if ($firstMatch[0][1] > 100) {
            $summary .= '...';
        }
        $summary .= $this->formatConent(trim(mb_substr($content, $startPosition, 300, $encoding)), $searchWords);
        $summary .= '...';
        
        return $summary;
    }
    
    /**
     * Highlight searched words in the content
     * 
     * @param string $content
     * @param array $searchWords
     * 
     * @return string formatted content
     */
    public function formatConent($content, $searchWords) {
        return preg_replace($this->getMatchPattern($searchWords), $this->replacePattern, $content);;
    }
    
    /**
     * Build match pattern by given search words
     * 
     * @param array $searchWords
     * 
     * @return string (regEx)
     */
    protected function getMatchPattern($searchWords) {
        return $matchPattern = '/(\W|\b)((' . implode(')|(', $searchWords) . '))(\W|\b)/ui';
    }
}
