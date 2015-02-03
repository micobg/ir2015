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
    
    public function __construct() {
        //
    }
    
    /**
     * Insert term in the list
     * 
     * @param Term $termObj
     */
    public function insert(Term $termObj) {
        $this->list[$termObj->getTerm()] = $termObj;
    }
    
}
