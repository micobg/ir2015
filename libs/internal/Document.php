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

    protected $matches;
    protected $uniqueMatchesStatus = array(); // unique of $this->matches as key and used status as value
    protected $termsList;
    protected $invertedIndex;

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

        $this->filterMatches();
        
        // insert the document in db
        $this->insert();

        // init terms list
        $this->termsList = new TermsList();

        // save terms and relations
        $this->manageMatches();
    }
    
    /**
     * Filter all matches
     */
    protected function filterMatches() {
        foreach ($this->matches as $key => $word) {
            if (array_search($word, Term::$stopWords) !== FALSE) {
                unset($this->matches[$key]);
            } else {
                $this->matches[$key] = mb_strtolower($word, $this->encoding);
                $this->uniqueMatchesStatus[$word] = FALSE;
            }
        }
        unset($key);
        unset($word);
    }

        /**
     * Insert document in db
     */
    protected function insert() {
        // get firat line and use it as title
        $this->title = $this->getFileTitle();

        $insertDoc = $this->dbConn->prepare("
            INSERT INTO docs(file_name, title, content, count_of_terms) 
            VALUES (:fileName, :title, :content, :count_of_terms)");
        $result = $insertDoc->execute(array(
            ':fileName' => $this->fileName,
            ':title' => $this->title,
            ':content' => $this->content,
            ':count_of_terms' => count($this->matches)
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
        $this->matches = $this->extractTerms();
    }
    
    /**
     * Iterate over all matches (words) in the document and save terms and their
     * relations.
     */
    protected function manageMatches() {
        $this->invertedIndex = new InvertedIndex();

        foreach ($this->matches as $index => $word) {
            $this->manageWord($word, $index);
        }
        unset($index);
        unset($word);
    }

    /**
     * Save the term and relation.
     * 
     * @param string $word
     * @param int $index
     */
    protected function manageWord($word, $index) {
        // normalize
        $termObj = new Term($word);

        // save the term
        if(!$this->uniqueMatchesStatus[$word]) {
            $termObj->save();
            $this->uniqueMatchesStatus[$word] = TRUE;
        }
        
        // add to term list
        if ($this->termsList->contains($termObj)) {
            // just add new occurrence
            $this->invertedIndex->addOccurrence($this->invertedIndex->getInvertedIndex($termObj, $this), $index);
        } else {
            $this->termsList->push($termObj);

            // save term-doc relation (inverted index) and position of occurrance
            $this->invertedIndex->addRelation($termObj, $this, $index);
        }
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