<?php

/**
 * Document to be analyzed
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
        
        // get file content
        $this->getFileContnet();

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
     * Get file comtmet
     * 
     * @throws Exception on error or empty file
     */
    protected function getFileContnet() {
        $this->content = file_get_contents($this->fileName);
        if (!$this->content) {
            throw new Exception('The file does not exist or it is empty.', 404);
        }
    }
    
    /**
     * Iterrate over all matches (words) in the document and save terms and their
     * relations.
     */
    protected function manageMatches() {
        $matches = $this->extractTerms();
        foreach ($matches as $word) {
            // normalize
            $word = mb_strtolower($word, $this->encoding);
            
            // skip stopwords
            if ($this->isStopword($word)) {
                continue;
            }
            
            // save the term
            $termObj = new Term($word);            
            $this->termsList->insert($termObj);
            
            // save term-doc relation (inverted index)
            
            unset($termObj);
        }
        unset($word);
    }

    /**
     * Find all potential terms from the document
     */
    protected function extractTerms() {        
        // get all words in the file
        $matches = array();
        preg_match_all('/\w*/iu', $this->content, $matches);
        
        $this->encoding = mb_detect_encoding($this->content);
        
        return array_unique(array_filter($matches[0]));
    }
    
    /**
     * Is the word a stopword
     * 
     * @param string $word
     */
    protected function isStopword($word) {
        return array_search($word, $this->stopWords);
    }
    
}