<?php
/**
 * Represent inverted index
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */

class InvertedIndex {

    protected $dbConn;
    
    protected $relations;

    public function __construct() {
        $this->dbConn = dbConn::getInstance();
    }
    
    /**
     * Add relation to the inverted index
     *
     * @param Term $term term object
     * @param Document $doc document object
     * @param int $position position of occurrence of the term in the document
     */
    public function addRelation($term, $doc, $position) {
        // insert into db
        $invertedIndexId = $this->insert($term, $doc);

        // save occurraence in db
        $this->addOccurrence($invertedIndexId, $position);
    }
    
    /**
     * Insert relation into db and get relation id
     * 
     * @param Term $term
     * @param Document $doc
     * 
     * @return int inverted_index_id
     * @throws Exception
     */
    protected function insert($term, $doc) {
        // insert into db
        $insertRelation = $this->dbConn->prepare(""
            . "INSERT INTO inverted_index(term_id, doc_id) "
            . "VALUES ('" . $term->getId() . "', '" . $doc->getId() . "')");
        $result = $insertRelation->execute();
        if (!$result) {
            throw new Exception('Insertion of relation into inverted index failed', 500);
        }
        $invertedIndexId = $this->dbConn->lastInsertId();
        
        // add to the object
        $this->relations[$invertedIndexId] = array(
            'doc_id' => $doc->getId(),
            'term_id' => $term->getId(),
            'occurrences' => array()
        );
        
        return $invertedIndexId;
    }
    
    /**
     * Add occurrences to object and insert into db
     * 
     * @param int $invertedIndexId
     * @param int $position
     */
    protected function addOccurrence($invertedIndexId, $position) {
        // insert into db
        $insertOccurrence = $this->dbConn->prepare(""
            . "INSERT INTO occurrences(inverted_index_id, `position`) "
            . "VALUES ('" . $invertedIndexId . "', '" . $position . "')");
        $result = $insertOccurrence->execute();
        if (!$result) {
            throw new Exception('Insertion of occurrence into db failed', 500);
        } 
        
        $this->relations[$invertedIndexId]['occurrences'][] = $position;
    }
}