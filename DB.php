<?php

/**
 * Class DB represents a query builder
 *
 * @author alexander
 */
class DB {

    private static $__instances = array();
    protected static $_db_adapter = null;
    public static $config = array();
    
    private static $__default_config = array(
        'host' => 'localhost',
        'username' => 'root',
        'password' => '',
        'database' => '',
        'adapter' => 'PDO',
        'driver' => 'mysql'
    );
    
    private $_table = null,
            $_class = null,
            $_operation = 'select',
            $_select = '*',
            $_where = null,
            $_order = null,
            $_limit = null,
            $_group = null,
            $_having = null,
            $_procedure = null,
            $_joins = array(),
            $_update = null,
            $_insert = null,
            $_delete = null,
            $_query = null,
            $_includes = array(),
            $_table_columns = null,
            $_result = null,
            $_relationships = array();

    public function __construct($class_name) {
        self::$config = array_merge(self::$__default_config, self::$config);

        $this->connect();

        $this->_table = $class_name::get_table();
        $this->_class = $class_name;

        $this->build_relationship();
    }

    public function __clone() {
        
    }

    public static function instance($modelName) {
        if (!isset(self::$__instances[$modelName])) {
            self::$__instances[$modelName] = new DB($modelName);
        }
        
        return self::$__instances[$modelName];
    }

    private function reset_instance() {
        $class_name = $this->_class;
        $this->_select = '*';
        $this->_table = $class_name::get_table();
        $this->_where = null;
        $this->_order = null;
        $this->_limit = null;
        $this->_group = null;
        $this->_having = null;
        $this->_procedure = null;
        $this->_joins = array();
        $this->_update = null;
        $this->_insert = null;
        $this->_delete = null;
    }

    public function get_columns() {
        if (is_null($this->_table_columns)) {
            $class_name = $this->_class;
            $table = $class_name::get_table();
            $result = mysql_query(
                    "SELECT `COLUMN_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_NAME` = '{$table}'"
            );

            if (!$result) {
                throw new SQLException("Cannot get information_sceme for table '{$table}'");
            }

            $this->_table_columns = array();
            while ($row = mysql_fetch_row($result)) {
                $this->_table_columns[] = $row[0];
            }
        }

        return $this->_table_columns;
    }

    public function connect() {
        $adapter_class = self::$_db_adapter = self::$config['adapter'] . "Adapter";
        
        if (!class_exists($adapter_class)) {
            if (file_exists(__DIR__ . "/adapters/$adapter_class.php")) {
                require_once __DIR__ . "/adapters/$adapter_class.php";
            } else {
                throw new UnknownAdapterException("Unknown adapter '$adapter_class'");
            }
        }

        $adapter_class::instance()->connect(
                self::$config['host'], 
                self::$config['username'], 
                self::$config['password'], 
                self::$config['database'],
                self::$config['driver']
        );

        return $this;
    }

    private function build_query() {
        if (!in_array($this->_operation, array('select', 'insert', 'update', 'delete'))) {
            throw new SQLException("Undefined operation '{$this->_operation}'.");
        }

        $this->{"build_{$this->_operation}_query"}();

        $this->reset_instance();
    }

    private function build_select_query() {
        $class_name = $this->_class;

        $this->_query = "SELECT {$this->_select} ";

        $from_clause = $this->_table;
        if ($from_clause == $class_name::get_table()) {
            $from_clause = "`$from_clause`";
        }

        $this->_query .= "FROM {$from_clause} ";

        if (!is_null($this->_where)) {
            $this->_query .= "WHERE {$this->_where} ";
        }

        if (!is_null($this->_group)) {
            $this->_query .= "GROUP BY {$this->_group} ";
        }

        if (!is_null($this->_having)) {
            $this->_query .= "HAVING {$this->_having} ";
        }

        if (!is_null($this->_order)) {
            $this->_query .= "ORDER BY {$this->_order} ";
        }

        if (!is_null($this->_limit)) {
            $this->_query .= "LIMIT {$this->_limit} ";
        }

        if (!is_null($this->_procedure)) {
            $this->_query .= "PROCEDURE {$this->_procedure}";
        }
    }

    private function build_insert_query() {
        $this->_query = "INSERT INTO `{$this->_table}` {$this->_insert} ";
    }

    private function build_update_query() {
        $this->_query = "UPDATE `{$this->_table}` SET {$this->_update} ";
        if (!is_null($this->_where)) {
            $this->_query .= "WHERE {$this->_where} ";
        }

        if (!is_null($this->_order)) {
            $this->_query .= "ORDER BY {$this->_order} ";
        }

        if (!is_null($this->_limit)) {
            $this->_query .= "LIMIT {$this->_limit} ";
        }
    }

    private function build_delete_query() {
        $this->_query = "DELETE FROM `{$this->_table}` ";

        if (!is_null($this->_where)) {
            $this->_query .= "WHERE {$this->_where} ";
        }

        if (!is_null($this->_order)) {
            $this->_query .= "ORDER BY {$this->_order} ";
        }

        if (!is_null($this->_limit)) {
            $this->_query .= "LIMIT {$this->_limit} ";
        }
    }

    public static function sql_query($sql, array $params = array()) {
        $adapter_class = self::$_db_adapter;
        return $adapter_class::instance()->query($sql, $params);
    }

    public function setClass($class) {
        $this->_class = $class;
    }

    public function getClass() {
        return $this->_class;
    }

    public function fetch_object() {
        return mysql_fetch_object($this->_result, $this->_class);
    }

    private function build_relationship() {
        $class_name = $this->_class;

        $has_many = $class_name::$has_many;
        $belongs_to = $class_name::$belongs_to;
        $has_one = $class_name::$has_one;

        foreach ($has_many as $relation) {
            $this->_relationships[$relation[0]] = new HasManyRelation($class_name, $relation);
        }

        foreach ($belongs_to as $relation) {
            $this->_relationships[$relation[0]] = new BelongsToRelation($class_name, $relation);
        }

        foreach ($has_one as $relation) {
            $this->_relationships[$relation[0]] = new HasOneRelation($class_name, $relation);
        }
    }

    private function include_relations($list) {
        $includes = $this->_includes;
        $this->_includes = array();
        foreach ($includes as $association) {
            if (isset($this->_relationships[$association])) {
                $list = $this->_relationships[$association]->attach_includes($list);
            } else {
                throw new UndefinedRelationshipException("Undefined relationship '$association'.");
            }
        }

        return $list;
    }

    public function load_relation($object, $attr) {
        if (isset($this->_relationships[$attr])) {
            return $this->_relationships[$attr]->load($object);
        }

        return false;
    }

    public function create(array $params) {
        $this->_operation = 'insert';

        $columns = '';
        $values = '';

        foreach ($params as $column => $value) {
            $value = mysql_real_escape_string($value);

            $columns .= "`{$column}`, ";
            $values .= "'{$value}', ";
        }

        $columns = rtrim($columns, ', ');
        $values = rtrim($values, ', ');

        $this->_insert = "({$columns}) VALUES ({$values})";

        $this->exec();

        return mysql_insert_id();
    }

    public function update(array $fields) {
        $this->_operation = 'update';

        $updateQuery = '';
        $whereStatement = '';
        $orderStatement = '';
        $limitStatement = '';

        foreach ($fields as $column => $val) {
            $val = mysql_real_escape_string($val);
            $updateQuery .= "`{$column}`='{$val}', ";
        }

        $updateQuery = rtrim($updateQuery, ', ');

        $this->_update = "{$updateQuery}";

        return $this;
    }

    public function delete() {
        $this->_operation = 'delete';
        $this->_delete = true;

        return $this;
    }

    public function select($select = '*') {
        $this->_operation = 'select';
        $this->_select = $select;

        return $this;
    }

    public function from($table) {
        $this->_table = $table;
        return $this;
    }

    public function join($type, $table, $on = null) {
        $join = "{$type} JOIN {$table}";

        if ($on != null) {
            $join .= " ON {$on}";
        }

        $this->_joins[] = $join;

        return $this;
    }

    public function inner_join($table, $on = null) {
        $this->join('INNER', $table, $on);
        return $this;
    }

    public function left_join($table, $on = null) {
        $this->join('LEFT', $table, $on);
        return $this;
    }

    public function right_join($table, $on = null) {
        $this->join('RIGHT', $table, $on);
        return $this;
    }

    public function natural_join($table, $type = null) {
        $this->join("NATURAL {$type}", $table, null);
        return $this;
    }

    public function includes(array $table_list = array()) {
        $this->_includes = $table_list;
        return $this;
    }

    public function where($value) {
        $this->_where = $value;
        return $this;
    }

    public function order($value) {
        $this->_order = $value;

        return $this;
    }

    public function limit($value) {
        $this->_limit = $value;

        return $this;
    }

    public function group($value) {
        $this->_group = $value;

        return $this;
    }

    public function having($value) {
        $this->_having = $value;

        return $this;
    }

    public function procedure($value) {
        $this->_procedure = $value;

        return $this;
    }

    public function exec(array $params = array()) {
        $this->build_query();

        $this->_result = self::sql_query($this->_query, $params);

        if ($this->_operation == 'select' && !$this->_result) {
            throw new SQLException(mysql_error());
        }

        return $this;
    }

    public function fetch(array $params = array()) {
        $this->exec($params);
        $list = new ModelList();

        $in_values = array();

        while ($object = $this->fetch_object()) {
            $list->append($object);
        }

        return $this->include_relations($list);
    }

    public function count(array $params = array()) {
        $this->select('count(*)');
        $this->exec($params);
        $result = mysql_fetch_row($this->_result);
        return $result[0];
    }

    public function custom_query($query, array $params = array()) {
        $this->_result = self::sql_query($query, $params);
        $list = new ModelList();

        while ($object = $this->fetch_object()) {
            $list->append($object);
        }

        return $list;
    }

    public function affected_rows() {
        return mysql_affected_rows();
    }

    public function last_query() {
        return $this->_query;
    }

    public function result() {
        return $this->_result;
    }

}

?>
