<?php

/**
 * Manage documents in docs table
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */
class DocumentsManager {
    
    protected $dbConn;

    public function __construct() {
        $this->dbConn = dbConn::getInstance();
    }
    
    /**
     * Reetrun list of all files from file/ dir that are not indexed
     * 
     * @return array file names of all unindexe files
     */
    public function getUnindexedFiles() {
        // list all files that are not indexed
        $files = Helper::dirToArray(FILES_DIR);

        // all indexed documents
        $allDocsInDb = __($this->getAllDocuments())->pluck('file_name');

        // remove already indexed files
        foreach ($files as $key => $fileName) {
            if (__($allDocsInDb)->includ(FILES_DIR . $fileName)) {
                unset($files[$key]);
            }
        }
        unset($key);
        unset($fileName);
        
        return $files;
    }
    
    /**
     * Return all indexed documents (from db)
     * 
     * @return array list of all documents
     */
    protected function getAllDocuments() {
        $selectAllDocs = $this->dbConn->prepare("SELECT * FROM docs");
        $selectAllDocs->execute();
        
        return $selectAllDocs->fetchAll(PDO::FETCH_ASSOC);
    }
    
}
