<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Validation
 *
 * @author alexander
 */

namespace Lex\GiddyORM;

class Validator {
    private $__class_name = null;
    private $__object = null;
    private $__current_action = null;
    private $__errors = array();
    
    private static $__validation_methods = array(
        'presence_of',
        'length_of',
        'size_of',
        'value_of',
        'numericality_of',
        'format_of',
        'uniqueness_of'
    );
    
    private static $__validation_range_options = array(
        'in',
        'within',
        'is',
        'maximum',
        'minimum',
        'match'
    );
    
    private static $__validation_numeric_options = array(
        'less_than',
        'greater_than',
        'equal_to',
        'less_than_or_equal_to',
        'greater_than_or_equal_to',
        'odd',
        'even',
        'only_integer'
    );
    
    private static $_validation_settings = array(
        'on' => 'save',
        'allow_null' => false,
        'allow_blank' => false,
        'message' => null
    );
    
    private static $__actions = array(
        'save',
        'update',
        'create'
    );


    public function __construct($object) {
        $this->__class_name = get_class($object);
        $this->__object = $object;
    }
    
    private function __get_model_attribute($attribute) {
		if(property_exists($this->__object, $attribute)) {
			return $this->__object->{$attribute};
		}
		
		return null;
	}
    
    private function __parse_validation($validation) {
        if(!is_array($validation)) {
            throw new ValidationException("The validation has to be represented by an array.");
        }
        
        $params_count = count($validation);
        
        $parsed = array();
        
        if($params_count < 2 || $params_count > 4) {
            throw new ValidationException(
                    'Validations should contain atleast 2 elements and a maximum of 4 elements .'.
                    'Example: array($method, $field, [$options], [$settings]).'
                    );
        }
        
        reset($validation);
        $parsed['method'] = current($validation);
        
        if(!in_array($parsed['method'], self::$__validation_methods)) {
            throw new ValidationException("Unknown validation method '{$parsed['method']}'.");
        }
        
        next($validation);
        
        $parsed['field'] = current($validation);
        
        $parsed['settings'] = array();
        $parsed['option'] = array();
        
        if($params_count == 3) {
            next($validation);
            
            $key_value = key($validation);
            if(is_numeric($key_value)) {
                $parsed['settings'] = current($validation);
            } else {
				
                if(!in_array($key_value, self::$__validation_numeric_options) 
                        && !in_array($key_value, self::$__validation_range_options)) {
                    throw new ValidationException("Unknown validation option '$key_value'.");
                }
                
                $parsed['option'] = array(
                    $key_value => current($validation)
                );
            }
        } elseif($params_count == 4) {
            next($validation);
            
            $option_name = key($validation);
            if(!in_array($option_name, self::$__validation_numeric_options) 
                    && !in_array($option_name, self::$__validation_range_options)){
                throw new ValidationException("Unknown validation option '$option_name'.");
            }
            
            $parsed['option'] = array(
                $option_name => current($validation)
            );
            
            next($validation);
            $parsed['settings'] = current($validation);
        }
        
        return $parsed;
    }
    
    public function is_valid($field = null) {
        if(is_null($field)) {
            if(count($this->__errors) > 0){
                return false;
            }
        
            return true;
        } else {
            if(isset($this->__errors[$field])) {
                return false;
            }
            
            return true;
        }
    }
    
    public function has_errors() {
        return !$this->is_valid();
    }
    
    public function add_error($field, $message) {
        if(isset($this->__errors[$field])) {
            if(is_array($this->__errors[$field])) {
                $this->__errors[$field][] = $message;
            } else {
                $this->__errors[$field] = array(
                    $this->__errors[$field],
                    $message
                );
            }
        } else {
            $this->__errors[$field] = $message;
        }
    }
    
    public function get($field = null) {
        if(!is_null($field)) {
            return $this->__errors[$field];
        }
        
        return $this->__errors;
    }
    
    public function get_all() {
        return $this->get();
    }
    
    public function validate($action) {
		$this->__current_action = $action;
        if(!in_array($action, self::$__actions)) {
            throw new ValidationException(
                    "Unknown validation action '$action'. " +
                    "Valid actions are '".implode(', ', self::$__actions)."'"
                    );
        }
        
        $class = $this->__class_name;
        $validations = $class::$validate;
        foreach($validations as $validation) {
            $this->__process_validation($validation, $action);
        }
        
        $this->__current_action = null;
    }
    
    private function __process_validation(array $validation, $action) {
        $validation = $this->__parse_validation($validation);
        $settings = array_merge(self::$_validation_settings, $validation['settings']);
        

        if (!in_array($settings['on'], self::$__actions)) {
            throw new ValidationException(
            "The 'on' setting should contain one of " .
            implode(', ', self::$__actions) . ".");
        }

        if ($settings['on'] !== 'save') {
            if ($settings['on'] !== $action) {
                return;
            }
        }

        if ($settings['allow_null']) {
            if (is_null($this->__get_model_attribute($validation['field']))) {
                return;
            }
        }

        if ($settings['allow_blank']) {
            if ($this->__get_model_attribute($validation['field']) === '') {
                return;
            }
        }

        if (!$this->{$validation['method']}(
                        $validation['field'], $validation['option'])) {

            $this->add_error($validation['field'], $settings['message']);
        }
    }
    
    // ****** BEGIN RANGE OPTIONS ****** 
    private function __within($value, $range) {
        if(!is_array($range)) {
            throw new ValidationException("Within has to be an Array");
            return true;
        }
        
        if(count($range) !== 2) {
            throw new ValidationException(
                    "Within has to contain exactly 2 numeric values");
            return true;
        }
        
        if($value < $range[0] || $value > $range[1]) {
            return false;
        }
        
        return true;
    }
    
    private function __in($value, $range) {
        if(!is_array($range)) {
            throw new ValidationException("In has to be an Array");
            return true;
        }
        
        if(count($range) > 0) {
            throw new ValidationException("In has to contain atleas 1 value.");
            return true;
        }
        
        if(!in_array($value, $range)) {
            return false;
        }
        
        return true;
    }
    
    private function __is($value, $exact_value) {
        if(!is_string($exact_value) && !is_numeric($exact_value)) {
            throw new ValidationException(
                    "The value of 'is' has to be either a string or a number.");
            return true;
        }
        
        if($value != $exact_value) {
            return false;
        }
        
        return true;
    }
    
    private function __match($value, $pattern) {
        if(!is_string($pattern)) {
            throw new ValidationException(
                    "The value of 'match' has to be a string containing a pattern.");
            return true;
        }
        
        if(!preg_match($pattern, $value)) {
            return false;
        }
        
        return true;
    }
    
    private function __maximum($value, $max) {
        if($value > $max) {
            return false;
        }
        
        return true;
    }
    
    private function __minimum($value, $min) {
        if($value < $min) {
            return false;
        }
        
        return true;
    }
    // ****** END RANGE OPTIONS ****** 
    
    // ****** BEGIN NUMERIC OPTIONS ****** 
    private function __less_than($value, $validation_value) {        
        if($value >= $validation_value) {
            return false;
        }
        
        return true;
    }
    
    private function __greater_than($value, $validation_value) {        
        if($value <= $validation_value) {
            return false;
        }
        
        return true;
    }
    
    private function __equal_to($value, $validation_value) {        
        if($value != $validation_value) {
            return false;
        }
        
        return true;
    }
    
    private function __odd($value, $attribute) {        
        if(!is_bool($attribute)) {
            throw new ValidationException("The odd option has to contain a boolean.");
        }
        
        if($value % 2 == 0) {
            return !$attribute;
        }
        
        return $attribute;
    }
    
    private function __even($value, $attribute) {        
        if(!is_bool($attribute)) {
            throw new ValidationException("The even option has to contain a boolean.");
        }
        
        if($value % 2 != 0) {
            return !$attribute;
        }
        
        return $attribute;
    }
    
    private function __less_than_or_equal_to($value, $validation_value) {        
        if($value > $validation_value) {
            return false;
        }
        
        return true;
    }
    
    private function __greater_than_or_equal_to($value, $validation_value) {        
        if($value < $validation_value) {
            return false;
        }
        
        return true;
    }
    
    private function __only_integer($value, $attribute) {
        if(!is_bool($attribute)) {
            throw new ValidationException("The only_integer option has to contain a boolean.");
        }
        
        if($attribute) {
            return is_integer($value);
        }
        
        return true;
    }
    // ****** END NUMERIC OPTIONS ****** 
    
    protected function presence_of($field, array $params) {
        return !is_blank($this->__get_model_attribute($field));
    }

    protected function size_of($field, array $params) {
        reset($params);
        $option = key($params);
        $attribute = current($params);
        $value = strlen($this->__get_model_attribute($field));
        
        switch ($option) {
            case 'within':
                return $this->__within($value, $attribute);
            case 'in':
                return $this->__in($value, $attribute);
            case 'maximum':
                return $this->__maximum($value, $attribute);
            case 'minimum':
                return $this->__minimum($value, $attribute);
            case 'is':
                return $this->__is($value, $attribute);
            default :
                throw new ValidationException(
                        "Unsupported option '$option'. ".
                        "Supported options are within, in, is, minimum and maximum."
                        );
                return true;
        }
    }

    protected function length_of($field, array $params) {
        return $this->size_of($field, $params);
    }
    
    protected function value_of($field, array $params) {
        reset($params);
        $option = key($params);
        $attribute = current($params);
        $value = $this->__get_model_attribute($field);
        
        switch ($option) {
            case 'in':
                return $this->__in($value, $attribute);
            case 'is':
                return $this->__is($value, $attribute);
            default :
                throw new ValidationException(
                        "Unsupported option '$option'. ".
                        "Supported options are in and is."
                        );
                return true;
        }
    }


    protected function numericality_of($field, array $params) {
        $value = $this->__get_model_attribute($field);
        
        if(!is_numeric($value)) {
            return false;
        }
        
        if(count($params) > 0) {
            reset($params);
            $option = key($params);
            $attribute = current($params);


            switch ($option) {
                case 'less_than':
                    return $this->__less_than($value, $attribute);
                case 'greater_than':
                    return $this->__greater_than($value, $attribute);
                case 'equal_to':
                    return $this->__equal_to($value, $attribute);
                case 'less_than_or_equal_to':
                    return $this->__less_than_or_equal_to($value, $attribute);
                case 'greater_than_or_equal_to':
                    return $this->__greater_than_or_equal_to($value, $attribute);
                case 'odd':
                    return $this->__odd($value, $attribute);
                case 'even':
                    return $this->__even($value, $attribute);
                case 'only_integer':
                    return $this->__only_integer($value, $attribute);
                default :
                    throw new ValidationException(
                            "Unsupported option '$option'. ".
                            "Supported options are ".
                            "only_integer, less_than, greater_than, equal_to, ".
                            "less_than_or_equal_to, greater_than_or_equal_to, ".
                            "odd and even."
                            );
                    return true;
            }
        }
        
        return true;
    }

    protected function format_of($field, array $params) {
        reset($params);
        $option = key($params);
        $pattern = current($params);
        $value = $this->__get_model_attribute($field);
        
        switch ($option) {
            case 'match':
                return $this->__match($value, $pattern);
            default :
                throw new ValidationException(
                    "Unsupported option '$option'. " .
                    "Supported options is match."
                    );
                return true;
        }
    }
    
    protected function uniqueness_of($field, array $params) {
        $class_name = $this->__class_name;
        $where = array();
        $bind_params = array();
        if(is_array($field)) {
            foreach ($field as $attr) {				
                $where[] = "`$attr` = ':$attr'";
                $bind_params[$attr] = $this->__get_model_attribute($attr);
            }
        } else {			
            $where[] = "`$field` = ':$field'";
            $bind_params[$field] = $this->__get_model_attribute($field);
        }
        
        if($this->__current_action == 'update') {
			$where[] = "`" . $class_name::$primary_key . "` <> '" . $this->__get_model_attribute($class_name::$primary_key) . "'";
		}
        
        return ( ($class_name::DB()->where(implode(' AND ',$where))->count($bind_params) > 0)? false : true );
    }

}

?>
