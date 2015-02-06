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
     * 
     * @return Term
     */
    public function pop($removeTerm = FALSE) {
        $termObj = $this->list[$termObj->getTerm()];

        if ($removeTerm) {
            unset($this->list[$termObj->getTerm()]);
        }
        
        return $termObj;
    }
    
    /**
     * Return all elements
     * 
     * @param boolean $removeTerm
     * 
     * @return array
     */
    public function popAll($removeTerm = FALSE) {
        $list = $this->list;
        
        if ($removeTerm) {
            $this->list = array();
        }
        
        return $list;
    }

    /**
     * Is the term in the list
     * 
     * @param Term $termObj
     * @return boolean
     */
    public function contains($termObj) {
        return isset($this->list[$termObj->getTerm()]);
    }
    
}
