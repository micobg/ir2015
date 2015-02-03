<?php

/**
 * Document to be analyzed
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */
class Document {
    
    protected $dbConn;

    protected $id;
    protected $fileName;
    protected $title;
    protected $content;
    protected $encoding;

    protected $termsList;

    public function __construct($filename) {
        $this->dbConn = dbConn::getInstance();
        $this->fileName = $filename;
        
        // get files content
        $this->getFileContent();

        // insert the document in db
        $this->insert();

        // init terms list
        $this->termsList = new TermsList();

        // save terms and relations
        $this->manageMatches();
    }
    
    /**
     * Insert document in db
     */
    protected function insert() {
        $this->title = strtok($this->content, "\n");
        
        $insertDoc = $this->dbConn->prepare(""
            . "INSERT INTO docs(file_name, title) "
            . "VALUES ('" . $this->fileName . "', '" . $this->title . "')");
        $result = $insertDoc->execute();

        if ($result) {
            $this->id = $this->dbConn->lastInsertId();
        } else {
            throw new Exception('Error on insert document in database.', 500);
        }
    }

    /**
     * Get file content
     * 
     * @throws Exception on error or empty file
     */
    protected function getFileContent() {
        $this->content = file_get_contents($this->fileName);
        if (!$this->content) {
            throw new Exception('The file does not exist or it is empty.', 404);
        }
    }
    
    /**
     * Iterate over all matches (words) in the document and save terms and their
     * relations.
     */
    protected function manageMatches() {
        $invertedIndex = new InvertedIndex();
        
        $matches = $this->extractTerms();
//        
//        $termsToBeInserted = array();
//        foreach ($matches as $index => $word) {
//            // normalize
//            $word = mb_strtolower($word, $this->encoding);
//            
//            // skip stop words
//            $termObj = new Term($word);
//            
//            if ($termObj->isStopWord()) {
//                continue;
//            }
//            
//        }
        
        
        
        foreach ($matches as $index => $word) {
            // normalize
            $word = mb_strtolower($word, $this->encoding);

            // skip stop words
            $termObj = new Term($word);
            
            if ($termObj->isStopWord()) {
                continue;
            }
            
            // save the term
            $termObj->save();
            $this->termsList->insert($termObj);
            
            // save term-doc relation (inverted index) and position of occurrance
            $invertedIndex->addRelation($termObj, $this, $index);
            
            unset($termObj);
        }
        unset($index);
        unset($word);
    }

    /**
     * Find all potential terms from the document
     */
    protected function extractTerms() {        
        $this->encoding = mb_detect_encoding($this->content);

        // get all words in the file
        $matches = array();
        preg_match_all('/\w+/iu', $this->content, $matches);
        
        return $matches[0];
    }
    
    /**
     * Term's id getter
     * 
     * @return string
     */
    public function getId() {
        return $this->id;
    }
    
}