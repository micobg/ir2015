<?php
/**
 * Represent inverted index
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */

class InvertedIndex {

    protected $dbConn;

    public function __construct() {
        $this->dbConn = dbConn::getInstance();
    }
    /**
     * Add relation to the inverted index
     *
     * @param Term $term term object
     * @param Document $doc document object
     * @param int $position // TODO: comment this
     */
    public function addRelation($term, $doc, $position) {
        $insertRelation = $this->dbConn->prepare("
            INSERT INTO inverted_index(term_id, doc_id, `position`)
            VALUES ('" . $term->getId() . "', '" . $doc->getId() . "', '" . $position . "')"
        );
        $insertRelation->execute();
    }
}