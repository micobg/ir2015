<?php

/**
 * Holds method that search for results in db
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */
class SearchEngine {
    
    protected $dbConn;
    
    public function __construct() {
        $this->dbConn = dbConn::getInstance();
    }
    
    public function search($searchField) {
        $searchWords = $this->splitSearchWords($searchField);

        $tfIdfs = array();
        foreach ($searchWords as $word) {
            $term = new Term($word);
            $term->init();
            
            $docsIds = $term->getDocuments();
            foreach ($docsIds as $docId) {
                $tfIdfs[$term->getId()][$docId] = $this->calculateTfIdf($term->getId(), $docId);
            }
            unset($docId);
        }
        unset($word);
    }
    
    /**
     * Split search field to array of words
     * 
     * @param string $searchField searched phrase
     * @return array search words
     */
    protected function splitSearchWords($searchField) {
        $matches = array();
        preg_match_all('/\w+/iu', $searchField, $matches);
        
        return $matches[0];
    }
    
    /**
     * Calculate TF-IDF value for given term and doc
     * 
     * @param int $termId
     * @param int $docId
     * 
     * @return int TF-IDF value
     */
    protected function calculateTfIdf($termId, $docId) {
        return $this->calculateTf($termId, $docId) * $this->calculateIdf($termId);
    }
    
    /**
     * Calculate TF value for given term and doc
     * 
     * @param int $termId
     * @param int $docId
     * 
     * @return int TF-IDF value
     */
    protected function calculateTf($termId, $docId) {
        return $this->sumOfOccurrences($termId, $docId) / $this->countOfWordsInDocument($termId, $docId);
    }
    
    /**
     * How many times the term occures in the document
     * 
     * @param int $termId
     * @param int $docId
     * 
     * @return int the value
     */
    protected function sumOfOccurrences($termId, $docId) {
        $searchDocs = $this->dbConn->prepare(""
            . "SELECT COUNT(occurrences.id) "
            . "FROM inverted_index "
            . "JOIN occurrences ON occurrences.inverted_index_id = inverted_index.id "
            . "WHERE inverted_index.term_id = '" . $termId . "' "
                . "AND inverted_index.doc_id = '" . $docId . "'");
        $searchDocs->execute();
        
        return (int)$searchDocs->fetchColumn();
    }

    /**
     * Count of words in the document
     * 
     * @param int $termId
     * @param int $docId
     * 
     * @return int the value
     */
    protected function countOfWordsInDocument($termId, $docId) {
        $searchDocs = $this->dbConn->prepare(""
            . "SELECT COUNT(*) "
            . "FROM inverted_index "
            . "WHERE inverted_index.term_id = '" . $termId . "' "
                . "AND inverted_index.doc_id = '" . $docId . "'");
        $searchDocs->execute();
        $count = (int)$searchDocs->fetchColumn();
        
        return $count === 0 ? 1 : $count;
    }

        /**
     * Calculate IDF value for given term
     * 
     * @param int $termId
     * 
     * @return int TF-IDF value
     */
    protected function calculateIdf($termId) {
        // TODO: implement
    }
}
