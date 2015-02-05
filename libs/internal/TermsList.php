<?php

/**
 * List of terms
 *
 * @author Mihail Nikolov <micobg@gmail.com>
 */
class TermsList {
    
    /**
     * Keep list of terms
     * 
     * @var array (
     *  key     = term
     *  value   = Term object
     * )
     */
    protected $list = array();

    /**
     * Insert term in the list
     * 
     * @param Term $termObj
     */
    public function insert($termObj) {
        $this->list[$termObj->getTerm()] = $termObj;
    }
    
    public function contains($termObj) {
        return isset($this->list[$termObj->getTerm()]);
    }
    
}
