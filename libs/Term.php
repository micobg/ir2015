<?php

/**
 * Term (word)
 *
 * @author micobg
 */
class Term {

    protected $dbConn;
    
    protected $term;
    protected $id;
    
    public function __construct($term) {
        $this->term = $term;
        
        $this->dbConn = dbConn::getInstance();
        
        $result = $this->fetch();
        if (!$result) {
            $this->insert();
        }
        
    }
    
    protected function fetch() {
        $searchTerm = $this->dbConn->prepare("SELECT id FROM terms WHERE term = '" . $this->term . "'");
        $result = $searchTerm->execute();
        if (!$result) {
            return FALSE;
        }
        
        $termId = $searchTerm->fetchColumn();
        if (!$termId) {
            return FALSE;
        }
        
        $this->id = $termId;
        
        return TRUE;
    }

    /**
     * Insert the term in db and return the id of the term
     * 
     * @return int termId
     */
    protected function insert() {
        $insertTerm = $this->dbConn->prepare("INSERT INTO terms(term) VALUES ('" . $this->term . "')");
        $result = $insertTerm->execute();

        if ($result) {
            $this->id = $this->dbConn->lastInsertId();
            
            return TRUE;
        } 
        
        return FALSE;
    }
    
    /**
     * Term's getter
     * 
     * @return string
     */
    public function getTerm() {
        return $this->term;
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
