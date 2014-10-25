<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Relationship
 *
 * @author alexander
 */
namespace Lex\GiddyORM;

abstract class Relationship {

    protected static $valid_options = array(
        'foreign_key', 
        'primary_key', 
        'model', 
        'select', 
        'conditions', 
        'limit', 
        'order', 
        'group', 
        'through'
    );
    protected $_attribute_name,
            $_primary_key,
            $_foreign_key,
            $_main_model,
            $_related_table,
            $_model,
            $_select,
            $_conditions,
            $_limit,
            $_order,
            $_group,
            $_through;

    /**
     * Relationship helper class
     */
    public function __construct($main_model, array $relation) {
        $this->_main_model = $main_model;
        $this->__parse_relation($relation);
    }

    private function __parse_relation(array $relation) {
        if (count($relation) < 1) {
            throw new RelationshipException("Empty array given for relationship.");
        }

        $this->_attribute_name = $relation[0];
        unset($relation[0]);

        if (isset($relation['model'])) {
            $this->_model = $relation['model'];
            unset($relation['model']);
        } else {
            $singularize = false;

            if (get_called_class() == 'HasManyRelation') {
                $singularize = true;
            }

            $this->_model = Util::classify($this->_attribute_name, $singularize);
        }

        foreach ($relation as $option => $value) {

            if (property_exists($this, "_$option") && in_array($option, static::$valid_options)) {

                $this->{"_$option"} = $value;
            } else {
                throw new RelationshipException(
                "Invalid option parameter '$option'. " .
                "Available options are " . implode(', ', static::$valid_options) . "."
                );
            }
        }
    }

    protected function load_eager_query_builder() {
        $class_name = $this->_model;

        $query_builder = $class_name::DB();

        if (!empty($this->_select)) {
            $query_builder->select($this->_select);
        }

        if (!empty($this->_order)) {
            $query_builder->order($this->_order);
        }

        return $query_builder;
    }

    protected function load_query_builder() {
        $class_name = $this->_model;

        $query_builder = $class_name::DB();

        if (!empty($this->_select)) {
            $query_builder->select($this->_select);
        }

        if (!empty($this->_limit)) {
            $query_builder->limit($this->_limit);
        }

        if (!empty($this->_order)) {
            $query_builder->order($this->_order);
        }

        if (!empty($this->_group)) {
            $query_builder->group($this->_group);
        }

        return $query_builder;
    }

    protected function attach_to_models($model_list) {
        $class_name = $this->_model;
        $primary_key = $this->_primary_key;
        $foreign_key = $this->_foreign_key;

        /* @var $queryBuilder DB */
        $query_builder = $this->load_query_builder();

        $in_values = '';
        $loaded_values = array();
        foreach ($model_list as $model_object) {
            $value = $model_object->{$primary_key};
            if (!in_array($value, $loaded_values)) {
                $in_values .= "'$value',";
                $loaded_values[] = $value;
            }
        }

        unset($loaded_values);

        $in_values = rtrim($in_values, ',');


        $where = "`" . $this->_related_table . "`.`$foreign_key` IN ( $in_values )";
        $where_prarams = array();

        if (!empty($this->_conditions)) {
            if (is_array($this->_conditions)) {
                reset($this->_conditions);
                $conditions = key($this->_conditions);
                $where_prarams = current($this->_conditions);
            } else {
                $conditions = $this->_conditions;
            }

            $where .= " AND ( $conditions )";
        }

        $query_builder->where($where);

        $related_objects = $query_builder->fetch($where_prarams);

        $relation_type = get_called_class();
        for ($i = 0; $i < count($model_list); $i++) {

            // setting default attribute value
            if ($relation_type == 'HasManyRelation') {
                $model_list[$i]->{$this->_attribute_name} = array();
            } else {
                $model_list[$i]->{$this->_attribute_name} = null;
            }

            foreach ($related_objects as $related_object) {
                if ($model_list[$i]->{$primary_key} == $related_object->{$foreign_key}) {
                    if ($relation_type == 'HasManyRelation') {
                        $model_list[$i]->{$this->_attribute_name}[] = $related_object;
                    } elseif ($relation_type == 'HasOneRelation') {
                        $model_list[$i]->{$this->_attribute_name} = $related_object;
                        break;
                    } elseif ($relation_type == 'BelongsToRelation') {
                        $model_list[$i]->{$this->_attribute_name} = $related_object;
                        break;
                    }
                }
            }
        }

        return $model_list;
    }

    protected function query_and_load_association($object) {
        if (!property_exists($object, $this->_foreign_key)) {
            throw new RelationshipException(
            "Can't load association. One or more key attributes are missing. " .
            "Check the model's object for errors and/or missing attributes."
            );
        }

        $foreign_key_value = $object->{$this->_foreign_key};

        $query_builder = $this->load_query_builder();

        $where = "`$this->_related_table`.`$this->_primary_key` = '$foreign_key_value'";
        $where_prarams = array();

        if (!empty($this->_conditions)) {
            if (is_array($this->_conditions)) {
                reset($this->_conditions);
                $conditions = key($this->_conditions);
                $where_prarams = current($this->_conditions);
            } else {
                $conditions = $this->_conditions;
            }

            $where .= " AND ( $conditions )";
        }

        $query_builder->where($where);

        $relation_type = get_called_class();
        $fetch_one = false;
        if ($relation_type == 'HasOneRelation' || $relation_type == 'BelongsToRelation') {

            $query_builder->limit(1);
            $fetch_one = true;
        }

        $related_objects = $query_builder->fetch($where_prarams);

        if ($fetch_one) {
            if (count($related_objects) > 0) {
                $related_objects = $related_objects->first();
            } else {
                $related_objects = null;
            }
        }

        return $related_objects;
    }

    abstract function attach_includes($model_list);

    abstract function load($object);
}

class HasManyRelation extends Relationship {

    protected static $valid_options = array(
        'limit', 'select', 'conditions', 'order',
        'group', 'through', 'foreign_key', 'model'
    );

    function __construct($main_model, array $relation) {
        parent::__construct($main_model, $relation);

        $class_name = $this->_model;
        $main_class_name = $this->_main_model;
        $this->_related_table = $class_name::get_table();
        $this->_primary_key = $main_class_name::$primary_key;


        if (empty($this->_foreign_key)) {
            $this->_foreign_key = Util::keyfy($main_class_name);
        }
    }

    public function attach_includes($model_list) {

        return $this->attach_to_models($model_list);
    }

    public function load($object) {
        return $this->query_and_load_association($object);
    }

}

class HasOneRelation extends Relationship {

    protected static $valid_options = array(
        'select', 'conditions', 'order', 'foreign_key', 'model'
    );

    function __construct($main_model, array $relation) {
        parent::__construct($main_model, $relation);

        $class_name = $this->_model;
        $main_class_name = $this->_main_model;
        $this->_related_table = $class_name::get_table();
        $this->_primary_key = $main_class_name::$primary_key;


        if (empty($this->_foreign_key)) {
            $this->_foreign_key = Util::keyfy($main_class_name);
        }
    }

    public function attach_includes($model_list) {

        return $this->attach_to_models($model_list);
    }

    public function load($object) {
        return $this->query_and_load_association($object);
    }

}

class BelongsToRelation extends Relationship {

    protected static $valid_options = array(
        'select', 'conditions', 'order', 'foreign_key', 'model'
    );

    function __construct($main_model, array $relation) {
        parent::__construct($main_model, $relation);

        $class_name = $this->_model;
        $main_class_name = $this->_main_model;
        $this->_related_table = $class_name::get_table();
        $primary_key = $class_name::$primary_key;

        $foreign_key = $this->_foreign_key;
        if (empty($this->_foreign_key)) {
            $foreign_key = Util::keyfy($class_name);
        }

        $this->_foreign_key = $primary_key;
        $this->_primary_key = $foreign_key;
    }

    public function attach_includes($model_list) {

        return $this->attach_to_models($model_list);
    }

    public function load($object) {
        return $this->query_and_load_association($object);
    }

}

?>
