<?php

/**
 * Holds methods that search for results in db
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */
class SearchEngine {
    
    protected $dbConn;

    protected $searchWords;
    protected $searchTerms;

    public function __construct() {
        $this->dbConn = dbConn::getInstance();
        $this->searchTerms = new TermsList();
    }

    /**
     * @param string $searchField searched phrase
     *
     * @return array documents (sorted)
     */
    public function search($searchField) {
        $this->searchWords = $this->splitSearchWords($searchField);

        $documentsManager = new DocumentsManager();
        $docIds = $this->getResultSet();
        $documents = array();
        foreach ($docIds as $docId) {
            $documents[] = $documentsManager->getDocumentsById($docId);
        }
        unset($docId);

        return $documents;
    }

    /**
     * Returns a list of sorted docIds (result of searching)
     *
     * @return array sorted (DESC) docIds
     */
    protected function getResultSet() {
        $scores = $this->calculateDocsScores();

        // sort documents by score (DESC)
        arsort($scores);

        // return only docIds (sorted)
        return array_keys($scores);
    }
    
    /**
     * Split search field to array of words
     * 
     * @param string $searchField searched phrase
     *
     * @return array search words
     */
    protected function splitSearchWords($searchField) {
        $matches = array();
        preg_match_all('/\w+/iu', mb_strtolower($searchField, mb_detect_encoding($searchField)), $matches);
        
        return $matches[0];
    }
    
    /**
     * Calculate docs scores
     *
     * @return array
     */
    protected function calculateDocsScores() {
        $scores = array();
        $tfIdfWeights = $this->getTfIdfForAllSearchWords();
        $fullPhraseDocuments = $this->getFullPhraseDocuments();
        foreach($tfIdfWeights as $docId => $termsTfIdf) {
            $scores[$docId] = array_sum($termsTfIdf);
            if (array_search($docId, $fullPhraseDocuments) !== FALSE) {
                $scores[$docId] *= 2;
            }
        }
        unset($docId);
        unset($termsTfIdf);

        return $scores;
    }

    /**
     * Calculate TF-IDF value for all pairs term-doc
     *
     * @return array TF-IDF for all pairs term-doc
     */
    protected function getTfIdfForAllSearchWords() {
        $tfIdfWeights = array();
        foreach ($this->searchWords as $word) {
            $term = new Term($word);
            $term->init();
            $this->searchTerms->push($term);

            $docsIds = $term->getDocuments();
            foreach ($docsIds as $docId) {
                $tfIdfWeights[$docId][$term->getId()] = $this->calculateTfIdf($term->getId(), $docId);
            }
            unset($docId);
        }
        unset($word);

        return $tfIdfWeights;
    }
    
    /**
     * Returns list of documents (as ids) which contains full searched phrase
     * (not just individual words)
     * 
     * @return array docs ids
     */
    protected function getFullPhraseDocuments() {
        $termsIds = array();
        $terms = $this->searchTerms->popAll();
        foreach ($terms as $termObj) {
            $termsIds[] = $termObj->getId();
        }
        
        $searchDocs = $this->dbConn->prepare("
            SELECT
                ii.doc_id, 
                MAX(o.position) AS max_position,
                MIN(o.position) AS min_position,
                IF (COUNT(o.position) > 1, COUNT(o.position), 0) AS count_of_occurrances
            FROM inverted_index AS ii
            JOIN occurrences AS o 
                ON o.inverted_index_id = ii.id
            WHERE 
                ii.term_id IN (" . implode(', ', $termsIds) . ")
            GROUP BY ii.doc_id
            HAVING max_position - min_position = count_of_occurrances - 1");
        $searchDocs->execute();

        return __($searchDocs->fetchAll())->pluck('doc_id');
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
     * Calculate TF (term frequency) value for given term and doc
     * 
     * @param int $termId
     * @param int $docId
     * 
     * @return float TF-IDF value
     */
    protected function calculateTf($termId, $docId) {
        return floatval($this->countOfOccurrences($termId, $docId) / $this->countOfWordsInDocument($termId, $docId));
    }
    
    /**
     * How many times the term occurs in the document
     * 
     * @param int $termId
     * @param int $docId
     * 
     * @return int the value
     */
    protected function countOfOccurrences($termId, $docId) {
        $searchDocs = $this->dbConn->prepare("
            SELECT COUNT(occurrences.id)
            FROM inverted_index
            JOIN occurrences ON occurrences.inverted_index_id = inverted_index.id
            WHERE inverted_index.term_id = '" . $termId . "'
                AND inverted_index.doc_id = '" . $docId . "'");
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
        $searchDocs = $this->dbConn->prepare("
            SELECT COUNT(*)
            FROM inverted_index
            WHERE inverted_index.term_id = '" . $termId . "'
                AND inverted_index.doc_id = '" . $docId . "'");
        $searchDocs->execute();
        $count = (int)$searchDocs->fetchColumn();
        
        return $count === 0 ? 1 : $count;
    }

    /**
     * Calculate IDF (inverse document frequency) value for given term
     * 
     * @param int $termId
     * 
     * @return float TF-IDF value
     */
    protected function calculateIdf($termId) {
        return log(floatval($this->countOfAllDocuments() / $this->countOfDocumentsContainingTheTerm($termId)));
    }

    /**
     * Returns a count of all indexed documents
     *
     * @return int
     */
    protected function countOfAllDocuments() {
        $searchDocs = $this->dbConn->prepare("SELECT COUNT(*) FROM docs");
        $searchDocs->execute();

        return (int)$searchDocs->fetchColumn();
    }

    /**
     * Returns a count of documents containing the given term
     *
     * @param int $termId
     *
     * @return int
     */
    protected function countOfDocumentsContainingTheTerm($termId) {
        $searchDocs = $this->dbConn->prepare("
            SELECT COUNT(*)
            FROM inverted_index
            WHERE inverted_index.term_id = '" . $termId . "'");
        $searchDocs->execute();
        $count = (int)$searchDocs->fetchColumn();

        return $count === 0 ? 1 : $count;
    }
    
    /**
     * Returns  search words
     * 
     * @return array
     */
    public function getSearchWords() {
        $words = array();
        foreach ($this->searchWords as $word) {
            if (!array_search($word, Term::$stopWords)) {
                $words[] = $word;
            }
        }
        unset($word);
        
        return $words;
    }
}
