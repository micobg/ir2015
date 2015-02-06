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
    public function push($termObj) {
        $this->list[$termObj->getTerm()] = $termObj;
    }

    /**
     * Returns a term by given term string
     *
     * @param Term $termObj
     * @param boolean $removeTerm
     */
    public function pop($termObj, $removeTerm = FALSE) {
        $this->list[$termObj->getTerm()] = $termObj;

        if ($removeTerm) {
            unset($this->list[$termObj->getTerm()]);
        }
    }
    
    public function contains($termObj) {
        return isset($this->list[$termObj->getTerm()]);
    }
    
}
