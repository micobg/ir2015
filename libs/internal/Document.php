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

    /**
     * Init document object
     *
     * @param string $filename
     * @throws Exception
     */
    public function __construct($filename) {
        $this->dbConn = dbConn::getInstance();

        if (empty($filename) || !file_exists(FILES_DIR . $filename)) {
            throw new Exception('Bad request: file does not exist', 400);
        }
        $this->fileName = FILES_DIR . $filename;
    }

    /**
     * Extract all terms and build inverted index
     *
     * @throws Exception
     */
    public function setup() {
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
        // get firat line and use it as title
        $this->title = $this->getFileTitle();

        $insertDoc = $this->dbConn->prepare("
            INSERT INTO docs(file_name, title, content) 
            VALUES (:fileName, :title, :contnet)");
        $result = $insertDoc->execute(array(
            ':fileName' => $this->fileName,
            ':title' => $this->title,
            ':contnet' => $this->content
        ));

        if ($result) {
            $this->id = $this->dbConn->lastInsertId();
        } else {
            throw new Exception('Error on insert document in database.', 500);
        }
    }

    /**
     * Get file title
     * 
     * @throws Exception on error or empty file
     */
    protected function getFileTitle() {
        $title = mb_substr($this->content, 0, 225, $this->encoding);

        // remove spaces and new lines
        return trim(preg_replace("/\n/", '', $title));
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
        $this->encoding = mb_detect_encoding($this->content);
    }
    
    /**
     * Iterate over all matches (words) in the document and save terms and their
     * relations.
     */
    protected function manageMatches() {
        $invertedIndex = new InvertedIndex();

        $matches = $this->extractTerms();
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
            
            // add to term list
            if ($this->termsList->contains($termObj)) {
                // just add new occurrence
                $invertedIndex->addOccurrence($invertedIndex->getInvertedIndex($termObj, $this), $index);
            } else {
                $this->termsList->push($termObj);
    
                // save term-doc relation (inverted index) and position of occurrance
                $invertedIndex->addRelation($termObj, $this, $index);
            }
            
            unset($termObj);
        }
        unset($index);
        unset($word);
    }

    /**
     * Find all potential terms from the document
     */
    protected function extractTerms() {
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