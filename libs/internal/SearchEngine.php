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

    protected $fullPhraseMultiplier = 2;

    /**
     * Cache
     */
    protected $cache;

    public function __construct() {
        $this->dbConn = dbConn::getInstance();
        $this->searchTerms = new TermsList();
        
        // init the cache
        $this->cache = array(
            'countOfAllDocuments' => $this->countOfAllDocuments(),
            'countOfDocumentsContainingTheTerm' => array(),
            'countOfWordsInDocument' => array()
        );
    }

    /**
     * @param string $searchField searched phrase
     *
     * @return array documents (sorted)
     */
    public function search($searchField) {
        $this->searchWords = $this->splitSearchWords($searchField);
        $searchWordsForMatching = $this->getSearchWords();
        
        $documentsManager = new DocumentsManager();
        $docIds = $this->getResultSet();
        $documents = array();
        foreach ($docIds as $docId) {
            $document = $documentsManager->getDocumentsById($docId);
            $document['summary'] = $documentsManager->getSummary($document['content'], $searchWordsForMatching);
            $document['content'] = $documentsManager->formatConent($document['content'], $searchWordsForMatching);
            $document['suggestions'] = $this->getSuggestions($docId);
            $documents[] = $document;
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
                $scores[$docId] *= $this->fullPhraseMultiplier;
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
            WHERE inverted_index.term_id = :term_id
                AND inverted_index.doc_id = :doc_id");
        $searchDocs->execute(array(
            ':term_id' => $termId,
            ':doc_id' => $docId
        ));
        
        return (int)$searchDocs->fetchColumn();
    }

    /**
     * Count of words in the document
     * 
     * @param int $docId
     * 
     * @return int the value
     */
    protected function countOfWordsInDocument($docId) {
        if (isset($this->cache['countOfWordsInDocument'][$docId])) {
            return $this->cache['countOfWordsInDocument'][$docId];
        }
        
        $searchDocs = $this->dbConn->prepare("
            SELECT count_of_words
            FROM docs
            WHERE id = :doc_id");
        $searchDocs->execute(array(
            ':doc_id' => $docId
        ));
        $count = (int)$searchDocs->fetchColumn();
        
        // prevent division by zero
        $this->cache['countOfWordsInDocument'][$docId] = $count === 0 ? 1 : $count;
        return $this->cache['countOfWordsInDocument'][$docId];
    }

    /**
     * Calculate IDF (inverse document frequency) value for given term
     * 
     * @param int $termId
     * 
     * @return float TF-IDF value
     */
    protected function calculateIdf($termId) {
        return log(floatval($this->cache['countOfAllDocuments'] / $this->countOfDocumentsContainingTheTerm($termId)));
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
        if (isset($this->cache['countOfDocumentsContainingTheTerm'][$termId])) {
            return $this->cache['countOfDocumentsContainingTheTerm'][$termId];
        }
        
        $searchDocs = $this->dbConn->prepare("
            SELECT count_of_documents
            FROM terms
            WHERE id = :term_id");
        $searchDocs->execute(array(
            ':term_id' => $termId
        ));
        $count = (int)$searchDocs->fetchColumn();

        // prevent division by zero
        $this->cache['countOfDocumentsContainingTheTerm'][$termId] = $count === 0 ? 1 : $count;
        return $this->cache['countOfDocumentsContainingTheTerm'][$termId];
    }
    
    /**
     * Get 3 words from the document with potential the highest TF-IDF
     * 
     * Potential becaule we will look only for term.count_of_documents * doc.count_of_terms
     * to be min. These metric is divider in TF-IDF formula and if it is lower, 
     * the TF-IDF potential can be higher.
     * 
     * @param int $docId
     * 
     * @return array
     */
    protected function getSuggestions($docId) {
        $searchDocs = $this->dbConn->prepare("
            SELECT
                t.term,
                COUNT(o.id) / (d.count_of_terms * t.count_of_documents) AS metric
            FROM docs AS d
            JOIN inverted_index AS ii
                ON ii.doc_id = d.id
            JOIN terms AS t
                ON t.id = ii.term_id
            JOIN occurrences AS o 
                ON o.inverted_index_id = ii.id
            WHERE
                d.id = :doc_id
            GROUP BY t.id
            ORDER BY metric DESC
            LIMIT 3
        ");
        $searchDocs->execute(array(
            ':doc_id' => $docId
        ));
            
        return __($searchDocs->fetchAll(PDO::FETCH_ASSOC))->pluck('term');
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
