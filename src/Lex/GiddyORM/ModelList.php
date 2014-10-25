<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ModelList
 *
 * @author alexander
 */
namespace Lex\GiddyORM;

class ModelList extends ArrayObject {
    public function __construct($array = array()) {
        parent::__construct($array);
    }
    
    public function first() {
		if($this->count() > 0) {
			return $this->offsetGet (0);
		}
		
		return false;
    }
    
    public function last() {
		if($this->count() > 0) {
			return $this->offsetGet($this->count() - 1);
		}
		
		return false;
    }
}
