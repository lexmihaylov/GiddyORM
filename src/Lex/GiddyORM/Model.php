<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of model
 *
 * @author alexander
 */
namespace Lex\GiddyORM;

class Model {

    public static
            $table = null,
            $primary_key = 'id',
            $validate = array(),
            $has_many = array(),
            $belongs_to = array(),
            $has_one = array(),
            $before_validate = array(),
            $before_update = array(),
            $before_save = array(),
            $before_create = array(),
            $before_delete = array(),
            $after_save = array(),
            $after_update = array(),
            $after_create = array(),
            $after_delete = array(),
            $on_construction = array();
    
    private static
            $__instance_data = array();
    
    private $__errors = null;

    public function __construct() {
        $this->executeFilters('on_construction');
        $this->__errors = new Validator($this);
    }
    
    public function errors() {
        return $this->__errors;
    }

    public function __get($name) {
        if (($relationship = static::DB()->load_relation($this, $name)) !== false) {
            $this->{$name} = $relationship;

            return $this->{$name};
        }

        trigger_error("Undefined model attribute '$name'.", E_USER_WARNING);

        return null;
    }

    public static function get_table() {
        $model_class = get_called_class();
        if (!isset(self::$__instance_data[$model_class]['table'])) {
            if (is_null(static::$table)) {
                self::$__instance_data[$model_class]['table'] = 
                        Util::pluralize(
                                Util::decamelize($model_class)
                        );
            } else {
                self::$__instance_data[$model_class]['table'] = static::$table;
            }
        }

        return self::$__instance_data[$model_class]['table'];
    }

    /**
     * 
     * @param type $name
     * @param type $arguments
     * @return type
     * @throws ModelFinderException
     */
    public static function __callStatic($name, $arguments) {
        if($name == 'find') {
            return static::DB()->where("`" . static::get_table() . "`.`".static::$primary_key."` = :".static::$primary_key."")
                            ->fetch(array(
                                ':'.static::$primary_key => $arguments[0]
                            ))->first();
        }
        
        if (preg_match('/^find_by_/', $name)) {
            if (count($arguments) != 1) {
                throw new ModelFinderException("'$name' accepts 1 argument, " . count($arguments) . ' given.');
            }

            $column_name = str_replace("find_by_", '', $name);
            $value = $arguments[0];

            $results = static::DB()->where("`" . static::get_table() . "`.`$column_name` = :$column_name")
                            ->fetch(array(
                                ':'.$column_name => $value
            ));
            
            if($column_name == static::$primary_key) {
                return $results->first();
            }
            
            return $results;
        }
    }

    private function get_column_attributes() {
        $fields = array();
        $columns = static::DB()->get_columns();
        foreach ($columns as $column) {
            if (property_exists($this, $column)) {
                $fields[$column] = $this->{$column};
            }
        }

        return $fields;
    }

    private function executeFilters($type) {
        $method_list = null;
        switch ($type) {
            case 'before_validate':
                $method_list = static::$before_validate;
                break;
            case 'before_save':
                $method_list = static::$before_save;
                break;
            case 'before_update':
                $method_list = static::$before_update;
                break;
            case 'before_create':
                $method_list = static::$before_create;
                break;
            case 'after_save':
                $method_list = static::$after_save;
                break;
            case 'after_update':
                $method_list = static::$after_update;
                break;
            case 'after_create':
                $method_list = static::$after_create;
                break;
            case 'on_construction':
                $method_list = static::$on_construction;
                break;
        }

        if (!is_null($method_list))
            foreach ($method_list as $method) {
                $this->{$method}();
            }
    }

    private function __validate($action) {
        $this->errors()->validate($action);

        if (method_exists($this, 'validate')) {
            $this->validate($action);
        }

        return $this->errors()->is_valid();
    }

    /**
     * Query builder for current model
     * @return DB
     */
    public static function DB() {
        $modelName = get_called_class();
        return DB::instance($modelName);
    }

    // database interface static
    public static function select($select = '*') {
        return static::DB()->select($select);
    }
    
    public static function delete() {
        return static::DB()->delete();
    }

    public static function where($value) {
        return static::DB()->where($value);
    }

    public static function includes(array $table_list) {
        return static::DB()->includes($table_list);
    }

    public static function get_columns() {
        return static::DB()->get_columns();
    }

    public static function fetch_object() {
        return static::DB()->fetch_object();
    }

    public static function from($table) {
        return static::DB()->from($table);
    }

    public static function join($type, $table, $on = null) {
        return static::DB()->join($type, $table, $on);
    }

    public static function inner_join($table, $on = null) {
        return static::DB()->inner_join($table, $on);
    }

    public static function left_join($table, $on = null) {
        return static::DB()->left_join($table, $on);
    }

    public static function right_join($table, $on = null) {
        return static::DB()->right_join($table, $on);
    }

    public static function natural_join($table, $type = null) {
        return static::DB()->natural_join($table, $type);
    }

    public static function order($value) {
        return static::DB()->order($value);
    }

    public static function limit($value) {
        return static::DB()->limit($value);
    }

    public static function group($value) {
        return static::DB()->group($value);
    }

    public static function having($value) {
        return static::DB()->having($value);
    }

    public static function procedure($value) {
        return static::DB()->procedure($value);
    }

    public static function count(array $params = array()) {
        return static::DB()->count();
    }

    public static function create(array $columns = array()) {
        $model = new static;
        foreach ($columns as $name => $value) {
            $model->{$name} = $value;
        }

        return $model;
    }

    public function destroy() {
        $this->executeFilters('before_delete');

        static::DB()->delete()
                ->where('`' . static::$primary_key . '` = :id')
                ->exec(array('id' => $this->{static::$primary_key}));

        $this->executeFilters('after_delete');
    }

    public function save($validate = true) {
        if (property_exists($this, 'id') && !is_null($this->id)) {
            

            
            if ($validate) {
                $this->executeFilters('before_validate');
                if (!$this->__validate('update')) {
                    return;
                }
            }
            
            $this->executeFilters('before_save');
            $this->executeFilters('before_update');
            
            $fields = $this->get_column_attributes();
            unset($fields[static::$primary_key]);
            static::DB()->update($fields)
                    ->where('`' . static::$primary_key . '` = :id')
                    ->exec(array('id' => $this->{static::$primary_key}));

            $this->executeFilters('after_update');
        } else {
            
            if ($validate) {
                $this->executeFilters('before_validate');
                if (!$this->__validate('create')) {
                    return;
                }
            }
            
            $this->executeFilters('before_save');
            $this->executeFilters('before_create');
            
            $fields = $this->get_column_attributes();
            $this->{static::$primary_key} = static::DB()->create($fields);

            $this->executeFilters('after_create');
        }

        $this->executeFilters('after_save');
    }

    public function refresh(array $includes = array()) {
        $pk = static::$primary_key;

        $refreshed_object = false;
        if (property_exists($this, $pk)) {
            $refreshed_object = static::DB()
                    ->where("`$pk`=:$pk")
                    ->includes($includes)
                    ->fetch(array($pk => $this->{$pk}))
                    ->first();
        }

        if (!$refreshed_object) {
            throw new ModelFinderException(
            "Can't refresh model object. " .
            "Corresponding database record is missing or there is no primary key value."
            );
        }

        $properties = get_object_vars($refreshed_object);

        foreach ($properties as $attribute => $value) {
            $this->{$attribute} = $value;
        }

        return $this;
    }

    public function to_string() {
        return serialize($this);
    }

    public function to_json() {
        return json_encode($this);
    }

}
