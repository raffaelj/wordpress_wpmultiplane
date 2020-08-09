<?php

$this->module('tables')->extend([

    'tables' => function($extended = false, $type = 'table') {

        $stores = [];

        foreach($this->app->helper('fs')->ls('*.table.php', "#storage:tables/{$this->dbname}") as $path) {

            $store = include($path->getPathName());

            if ($extended) {
                $store['itemsCount'] = $this->count($store['name']);
            }

            $stores[$store['name']] = $store;

        }

        return $stores;

    }, // end of tables()

    'table' => function($name) {

        static $tables; // cache

        if (is_null($tables)) {
            $tables = [];
        }

        if (!is_string($name)) {
            return false;
        }

        if (!isset($tables[$name])) {

            $tables[$name] = false;

            if ($path = $this->exists($name)) {
                $tables[$name] = include($path);
            }

            else { // create table schema on the fly, but don't save it (low performance)
                $tables[$name] = $this->createTableSchema($name, $data = [], $fromDatabase = true, $store = false);
            }

        }

        return $tables[$name];

    }, // end of table()

    'count' => function($table, $options = []) {

        $_table = $this->table($table);
        $table = $_table['_id'];

        $filtered_query = $this->query($_table, $options);
        $query          = $filtered_query['query'];
        $params         = $filtered_query['params'];

        $stmt = $this('db')->run($query, $params);
        $count = $stmt->rowCount();

        return $count;

    }, // end of count()

    'find' => function($table, $options = []) {

        $_table = $this->table($table);

        if (!$_table) return false;

        $name  = $table; // reset table name to stored _id
        $table = $_table['_id'];

        $this->app->trigger('tables.find.before', [$name, &$options]);
        $this->app->trigger("tables.find.before.{$name}", [$name, &$options]);

        $filtered_query = $this->query($_table, $options);
        $query          = $filtered_query['query'];
        $params         = $filtered_query['params'];
        $normalize      = !empty($filtered_query['normalize'])
                          ? $filtered_query['normalize'] : null;

        // temporary debug functionality - will be removed in the future
        if ($this->app->retrieve('tables/debug', false)) {

            $this->app->helpers['fs']->write("#storage:tmp/.querylog.txt",
                date('Y-m-d H:i:s', time()) . "\r\n"
                . (($t = debug_backtrace()[1]) ? "{$t['file']} - {$t['line']} - {$t['function']}\r\n" :'')
                . $query . "\r\n"
                . 'params: ' . json_encode($params) . "\r\n\r\n"
                , FILE_APPEND);

        }

        $entries = empty($query) ? [] : $this('db')->run($query, $params)->fetchAll(\PDO::FETCH_ASSOC);

        // cast comma separated values from GROUP_CONCAT query as array
        if (!empty($normalize)) {
            $entries = $this->normalizeGroupConcat($entries, $normalize);
        }

        // remove null values
        foreach ($entries as &$entry) {
            foreach ($entry as $key => &$val) {
                if ($entry[$key] === null) {
                    unset($entry[$key]);
                }
            }
        }

        $this->app->trigger('tables.find.after', [$name, &$entries]);
        $this->app->trigger("tables.find.after.{$name}", [$name, &$entries]);

        return $entries;

    }, // end of find()

    'findOne' => function($table, $criteria = [], $projection = null, $populate = false, $fieldsFilter = []) {

        $_table = $this->table($table);

        if (!$_table) return false;

        $name       = $table;
        $options    = [
            'filter'       => $criteria,
            'fields'       => $projection,
            'populate'     => $populate,
            'fieldsFilter' => $fieldsFilter,
            'limit'        => 1
        ];

        $entries = $this->find($name, $options);

        return $entries[0] ?? null;

    }, // end of findOne()

    'exists' => function($name) {

        // check if schema file exists
        return $this->app->path("#storage:tables/{$this->dbname}/{$name}.table.php");

    }, // end of exists()

    'save' => function($table, $data, $options = []) {

        // to do:
        // * revisions

        $_table = $this->table($table);

        if (!$_table) return false;

        $name        = $_table['name'];
        $data        = isset($data[0]) ? $data : [$data];
        $modified    = time();
        $primary_key = $_table['primary_key'];

        $tasks = null; // for many-to-many relations

        $columns = null;
        $query   = null;
        $params  = null;

        $_fields = array_column($_table['fields'], 'name');

        foreach ($data as &$entry) {

            $isUpdate = isset($entry[$primary_key]);

            // to do: adjust database schema to store meta data
            if (isset($entry['_created']))  unset($entry['_created']);
            if (isset($entry['_modified'])) unset($entry['_modified']);
            if (isset($entry['_by']))       unset($entry['_by']);
            if (isset($entry['_mby']))      unset($entry['_mby']);

            // cast fields
            foreach ($_table['fields'] as $field) {

                if ($field['type'] == 'relation'
                    && isset($entry[$field['name']])
                    && (isset($field['options']['type'])
                        && (  $field['options']['type'] == 'one-to-one'
                           || $field['options']['type'] == 'many-to-many')
                       )
                    ) {

                    // many-to-many field

                    $ref_table = $field['options']['target']['table'];

                    // sloppy check, relations field always sends array
                    if (is_string($entry[$field['name']])) {

                        // entry didn't change, do nothing
                        continue;

                    }

                    if (empty($entry[$field['name']])) {

                        // rows may exist, but nothing is selected, remove all

                        $tasks[] = [
                            'task' => 'remove',
                            'table' => $ref_table,
                            'data' => [
                                $field['options']['target']['identifier'] => $entry[$primary_key],
                            ]
                        ];

                        continue;

                    }

                    // resolve many-to-many relations

                    $result_exists = [];
                    if ($isUpdate) {

                        $identifier = $field['options']['target']['identifier'];

                        $parts = [];
                        $parts[] = "SELECT * FROM " . sqlIdentQuote($ref_table);
                        $parts[] = "WHERE " . sqlIdentQuote($identifier) . " = :$primary_key";
                        $query = implode(' ', $parts);
                        $params[":$primary_key"] = $entry[$primary_key];

                        $stmt = $this('db')->run($query, $params);
                        $result_exists = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                    }

                    if (empty($result_exists)) {

                        // no rows exist, insert all entries

                        foreach ($entry[$field['name']] as $ref_field => $ref_entry) {

                            $tasks[] = [
                                'task' => 'save',
                                'table' => $ref_table,
                                'data' => [
                                    $field['options']['target']['identifier'] => $entry[$primary_key] ?? '__last_insert_id',
                                    $field['options']['target']['related_identifier'] => $ref_entry
                                ]
                            ];

                        }

                    }

                    else {

                        // some entries may have changed

                        $revert_result = array_column($result_exists, $identifier, $field['options']['target']['related_identifier']);

                        $sent_fields = $entry[$field['name']];

                        $delete_related_field = [];
                        $save_related_field = $sent_fields;

                        foreach ($revert_result as $existing_entry => $i) {

                            if (in_array($existing_entry, $sent_fields)) {

                                // entry exists, do nothing

                                $key = array_search($existing_entry, $save_related_field);

                                if ($key !== false)
                                    unset($save_related_field[$key]);

                            }

                            elseif (!in_array($existing_entry, $sent_fields)) {

                                // entry exists, but wasn't sent --> delete it

                                $delete_related_field[] = $existing_entry;

                                $key = array_search($existing_entry, $save_related_field);

                                if ($key !== false)
                                    unset($save_related_field[$key]);

                            }

                        }

                        foreach ($delete_related_field as $ref_entry) {

                            $tasks[] = [
                                'task' => 'remove',
                                'table' => $ref_table,
                                'data' => [
                                    $field['options']['target']['identifier'] => $entry[$primary_key],
                                    $field['options']['target']['related_identifier'] => $ref_entry
                                ]
                            ];

                        }

                        foreach ($save_related_field as $ref_entry) {

                            $tasks[] = [
                                'task' => 'save',
                                'table' => $ref_table,
                                'data' => [
                                    $field['options']['target']['identifier'] => $entry[$primary_key],
                                    $field['options']['target']['related_identifier'] => $ref_entry
                                ]
                            ];

                        }

                    }

                }

                elseif ($field['type'] == 'relation'
                    && isset($entry[$field['name']])
                    && (isset($field['options']['type'])
                        && (  $field['options']['type'] == 'one-to-many')
                       )
                    ) {

                    // one-to-many field

                    // cast first key if single select field contains array
                    if (is_array($entry[$field['name']]))
                        $entry[$field['name']] = $entry[$field['name']][0];

                    $columns[] = $field['name'];

                }

                elseif ($field['type'] == 'relation'
                    && isset($entry[$field['name']])
                    && (isset($field['options']['type'])
                        && (  $field['options']['type'] == 'many-to-one')
                       )
                    ) {

                    // many-to-one field

                    unset($entry[$field['name']]);

                }

                elseif (isset($entry[$field['name']])) {

                    // normal fields

                    if ($entry[$field['name']] !== null)
                        $columns[] = $field['name'];

                }

            }

            $parts = [];
            
            if (!$isUpdate) {

                // to do (eventually): insert if not exist

                if ($columns) {

                    $escaped_columns = array_map('sqlIdentQuote', $columns);

                    $parts[] = "INSERT INTO " . sqlIdentQuote($name);
                    $parts[] = "(" . implode(',', $escaped_columns) . ")";
                    $parts[] = "VALUES (:" . implode(',:', $columns) . ")";

                    $query = implode(' ', $parts);

                    $params = [];
                    foreach ($columns as $col)
                        $params[':'.$col] = $entry[$col];

                }

            }
            else { // is update

                if ($columns) {

                    $parts[] = "UPDATE " . sqlIdentQuote($name);
                    $parts[] = "SET";
                    
                    foreach ($columns as $col)
                        if ($col != $primary_key)
                            $set[] = sqlIdentQuote($col) . " = :$col";

                    $parts[] = implode(', ', $set);
                    $parts[] = "WHERE " . sqlIdentQuote($primary_key) . " = :$primary_key";

                    $query = implode(' ', $parts);

                    $params = [];
                    foreach ($columns as $col) {
                        $params[':'.$col] = $entry[$col];
                    }

                }

            }

            $this->app->trigger('tables.save.before', [$name, &$entry, $isUpdate]);
            $this->app->trigger("tables.save.before.{$name}", [$name, &$entry, $isUpdate]);

            $stmt = $this('db')->run($query, $params);

            if ($stmt && !$isUpdate) {

                $__last_insert_id = $this('db')->lastInsertId();

                $entry[$primary_key] = $__last_insert_id;

            }
            
            else {
                $__last_insert_id = $entry[$primary_key];
            }

            $ret = $stmt ? true : false;

            $this->app->trigger('tables.save.after', [$name, &$entry, $isUpdate]);
            $this->app->trigger("tables.save.after.{$name}", [$name, &$entry, $isUpdate]);

            // run tasks (save and remove) for referenced tables
            if ($ret && $tasks) {

                foreach ($tasks as $t) {
                    $task = $t['task'];

                    // search for string '__last_insert_id' and replace it
                    if (!$isUpdate) {
                        
                        // to do: better logic to avoid data manipulation.
                        // Theoretically it's possible, that someone really wants
                        // to insert the string '__last_insert_id'
                        foreach ($t['data'] as $key => &$val) {

                            if ($val == '__last_insert_id') {
                                $val = $__last_insert_id;
                            }

                        }

                    }

                    $task_return = $this->$task($t['table'], $t['data']);

                }

            }

            $return[] = $ret ? $entry : false;

        }

        return count($return) == 1 ? $return[0] : $return;

    }, // end of save()

    'remove' => function($table, $criteria) {

        if (!is_array($criteria) || empty($criteria)) return false;

        $_table = $this->table($table);

        if (!$_table) return false;

        $name  = $table;
        $table = $_table['_id'];

        $primary_key = $_table['primary_key'];
        $_fields = $_table['fields'];
        $fields = array_column($_fields, 'name');

        // check foreign key relations
        $tasks = null;
        foreach ($_fields as $field) {

            $referenced_by = $this->getReferences($table, $field['name'], 'is_referenced_by');

            if ($referenced_by) {

                foreach ($referenced_by as $ref) {

                    $ref_table = $this->table($ref['table']);

                    if (empty($ref_table['auto_delete_by_reference']) || $ref_table['auto_delete_by_reference'] !== true) {

                        $result_exists = $this->count($ref['table'], [
                            // 'fields' => [$ref_table['primary_key'] => true],
                            // to do: https://github.com/raffaelj/cockpit_Tables/issues/26
                            'filter' => [$ref['field'] => $criteria[$ref_table['primary_key']]]
                        ]);

                        if ($result_exists) {

                            return ['error' => 
                                $this('i18n')->get('This entry can\'t be deleted, because it\'s referenced.') . '<br>' . $ref['table'] . ": $result_exists " . $this('i18n')->get('entries')
                            ];

                        }

                        continue;

                    }

                    $tasks[] = [
                        'table' => $ref['table'],
                        'data' => [
                            $ref['field'] => $criteria[$primary_key]
                        ],
                    ];

                }

            }

        }

        // call own remove function for referenced tables first
        if ($tasks) {

            foreach ($tasks as $task) {

                $this->app->trigger('tables.removereference.before', [$task['table'], &$task['data']]);
                $this->app->trigger("tables.remove.removereference.{$task['table']}", [$task['table'], &$task['data']]);

                $result = $this->remove($task['table'], $task['data']);

                $this->app->trigger('tables.removereference.after', [$task['table'], $result]);
                $this->app->trigger("tables.removereference.after.{$task['table']}", [$task['table'], $result]);

            }

        }

        // filter rules
        $filter = null;
        foreach ($criteria as $field => $value) {
            if (in_array($field, $fields))
                $filter[$field] = $value;
        }

        $query = '';
        $parts = [];
        $params = [];

        if ($filter) {
             $parts[] = "DELETE FROM " . sqlIdentQuote($table);

             $i = 0;
             foreach ($filter as $field => $value) {

                $parts[] = $i == 0 ? "WHERE" : "AND";
                $parts[] = sqlIdentQuote($field) . " = :$field";

                $params[":$field"] = $value;
                
                $i++;

             }
        }

        $query = implode(' ', $parts);

        if (empty(trim($query))) return false;

        // temporary debug functionality - will be removed in the future
        if ($this->app->retrieve('tables/debug', false)) {

            $this->app->helpers['fs']->write("#storage:tmp/.querylog.txt",
                date('Y-m-d H:i:s', time()) . "\r\n"
                . (($t = debug_backtrace()[1]) ? "{$t['file']} - {$t['line']} - {$t['function']}\r\n" :'')
                . $query . "\r\n"
                . 'params: ' . json_encode($params) . "\r\n\r\n"
                , FILE_APPEND);

        }

        $this->app->trigger('tables.remove.before', [$name, &$criteria]);
        $this->app->trigger("tables.remove.before.{$name}", [$name, &$criteria]);

        $result = $this('db')->run($query, $params) ? true : false;

        $this->app->trigger('tables.remove.after', [$name, $result]);
        $this->app->trigger("tables.remove.after.{$name}", [$name, $result]);

        return $result ? true : false;

    }, // end of remove()

    'createTableSchema' => function($name = '', $data = [], $fromDatabase = false, $store = true, $extended = false) {

        if (!trim($name)) {
            return false;
        }

        $relations = [];

        if ($fromDatabase) {

            // load the missing part for initialization and extend tables module
            require_once(__DIR__.'/init_field_schema.php');

            // now the functions getTableSchema() and formatTableSchema() exist
            if (empty($data)) $data = $this->getTableSchema($name);

            if (empty($data)) return false;

            $data = $this->formatTableSchema($data, $store, $extended);
            
            if ($extended) {
                $relations = $data['relations'];
                $data      = $data['data'];
            }
            
        }

        $configpath = $this->app->path('#storage:')."/tables/{$this->dbname}";

        if (!$this->app->path($configpath)) {
            if (!$this->app->helper('fs')->mkdir($configpath)) {
                return false;
            }
        }

        $time = time();
        if (!is_array($data)) $data = [];

        $table = array_replace_recursive([
            'name'      => $name,
            'label'     => '',
            '_id'       => $name,
            'fields'    => [],
            'sortable'  => false,
            'in_menu'   => false,
            '_created'  => $time,
            '_modified' => $time
        ], $data);

        // sql driver addon uses slashes in table names
        $clean = str_replace('/', '__', $name);
        $table['name'] = str_replace('/', '__', $table['name']);
        if (empty($table['label'])) $table['label'] = $table['_id'];

        if ($store) {

            $export = var_export($table, true);

            if (!$this->app->helper('fs')->write("#storage:tables/{$this->dbname}/{$clean}.table.php", "<?php\n return {$export};")) {
                return false;
            }

            $this->app->trigger('tables.createtableschema', [$table]);

        }

        return $extended ? compact('table', 'relations') : $table;

    }, // end of createTableSchema()

    'resetField' => function($table, $field) {

        $_table = $this->table($table);

        if (!$_table) return false;

        $table = $_table['_id'];
        $_field = null;

        $schema = $this->createTableSchema($table, null, true, false);

        foreach($schema['fields'] as $fld) {
            if ($fld['name'] == $field) {
                $_field = $fld;
                break;
            }
        }

        if (!$_field) return false;

        $k = null;
        foreach($_table['fields'] as $key => $fld) {
            if ($fld['name'] == $field) {
                $k = $key;
                break;
            }
        }

        if (!$k) {
            $_table['fields'][] = $_field;
            $k = count($_table['fields']) -1;
        } else {
            foreach($_table['fields'][$k] as $key => &$val) {
                if ($key == 'type' || $key == 'options' || ($key == 'required' && !$val))
                    $val = $_field[$key];
            }
        }

        $this->updateTableSchema($table, $_table);

        return $_table['fields'][$k];

    }, // end of resetField()

    'updateTableSchema' => function($name, $data = []) {

        $metapath = $this->app->path("#storage:tables/{$this->dbname}/{$name}.table.php");

        if (!$metapath) {
            return false;
        }

        $data['_modified'] = time();

        $table  = include($metapath);
        $table  = array_merge($table, $data);
        $export = var_export($table, true);

        if (!$this->app->helper('fs')->write($metapath, "<?php\n return {$export};")) {
            return false;
        }

        $this->app->trigger('tables.updatetableschema', [$table]);
        $this->app->trigger("tables.updatetableschema.{$name}", [$table]);

        if (function_exists('opcache_reset')) opcache_reset(); // to do: What does this line do exactly?

        return $table;

    }, // end of updateTableSchema()

    'saveTableSchema' => function($name, $data) {

        if (!trim($name)) {
            return false;
        }

        return isset($data['_id']) ? $this->updateTableSchema($name, $data) : $this->createTableSchema($name, $data);

    }, // end of saveTableSchema()

    'removeTableSchema' => function($name) {

        if ($table = $this->table($name)) {

            $this->app->helper('fs')->delete("#storage:tables/{$this->dbname}/{$name}.table.php");

            $this->app->trigger('tables.removetableschema', [$name]);
            $this->app->trigger("tables.removetableschema.{$name}", [$name]);

            return true;
        }

        return false;

    }, // end of removeTableSchema()

    'getReferences' => function($table_name, $field_name, $type) {

        static $references; // cache

        if (is_null($references)) {
            $path = $this->app->path("#storage:tables/{$this->dbname}/relations.php");
            $references = file_exists($path) ? include($path) : [];
        }

        if (!empty($references[$table_name][$field_name][$type])) {
            return $references[$table_name][$field_name][$type];
        }

        return false;

    }, // end of getReferences()

    'getStoredRelations' => function() {

        $path = $this->app->path("#storage:tables/{$this->dbname}/relations.php");
        
        $relations = file_exists($path) ? include($path) : [];
        
        return $relations;

    }, // end of getStoredRelations()

    'getDatabaseRelations' => function() {

        $relations = [];
        $origTables = $this->listTables();

        // original relations
        $relations = [];
        foreach ($origTables as $name) {
            $data = $this->createTableSchema(
                $name,  // table name
                null,   // data
                true,   // fromDatabase
                false,  // store
                true    // extended
            );

            $relations = array_merge($data['relations'], $relations);
        }

        return $relations;

    }, // end of getDatabaseRelations()

    'fixWrongRelations' => function() {

        $relations = $this->getDatabaseRelations();

        $export = var_export($relations, true);

        return $this->app->helper('fs')->write("#storage:tables/{$this->dbname}/relations.php", "<?php\n return {$export};");

    }, // end of fixWrongRelations()

    'is_filtered_out' => function($field_name, $fields, $primary_key = '') {

        // select all
        if (!$fields)
            return false;

        // one filter is set to true - don't select any other fields
        if (in_array(true, $fields)) {

            if (isset($fields[$field_name]) && $fields[$field_name] == true)
                return false;

            // return primary_key, too if not explicitly set to false
            if ($field_name == $primary_key && ( !isset($fields[$primary_key]) || $fields[$primary_key] == true))
                return false;

            return true;

        }

        else {

            if (!isset($fields[$field_name]))
                return false;

            if (isset($fields[$field_name]) && $fields[$field_name] == false)
                return true;

        }

    }, // end of is_filtered_out()

    'query' => function($_table, $options = []) {

        if (is_string($_table)) {
            $_table = $this->table($_table);
        }

        $db_config = [
            'host'   => $this->host,
            'dbname' => $this->dbname,
            'prefix' => $this->prefix,
        ];

        $query = new \Tables\Helper\Query($this->app, $db_config);

        $query->init($_table, $options);

        return $query->getQuery(true);

    }, // end of query()

    'normalizeGroupConcat' => function($entries, $normalize) {

        foreach ($entries as $key => &$entry) {
            foreach ($normalize as $n) {

                if (!isset($n['field']) || !isset($n['separator'])) {
                    continue;
                }

                if (!empty($entry[$n['field']])) {

                    $entry[$n['field']] = explode($n['separator'], $entry[$n['field']]);

                    // Joins with GROUP_CONCAT for many-to-many fields are complicated
                    // they can fail if the separator exists in the text.
                    // Do some more SQL queries instead
                    if (isset($n['populate'])) {

                        $_table = $this->table($n['populate']['table']);
                        if (!$_table) continue;

                        $parts = [];

                        // allow templating in display_field
                        // "{field_one} - {field_two}"
                        if (strpos($n['populate']['field'], '{') !== false) {

                            $select = '';
                            $template_parts = preg_split('#{|}#', $n['populate']['field'], -1, PREG_SPLIT_NO_EMPTY);

                            foreach ($template_parts as $t) {
                                $select .= in_array($t, $_table['database_schema']['columns']) ? sqlIdentQuote($t).',' : '"'.$t.'",';
                            }
                            $select = trim($select, ',');

                            $parts[] = "SELECT CONCAT($select) AS " . sqlIdentQuote($n['field']);

                        } else {
                            if (!in_array($n['populate']['field'], $_table['database_schema']['columns'])) {
                                continue;
                            }
                            $parts[] = "SELECT " . sqlIdentQuote($n['populate']['field']);
                        }

                        $parts[] = "FROM "  . sqlIdentQuote($n['populate']['table']);
                        $parts[] = "WHERE " . sqlIdentQuote($_table['primary_key']);
                        $parts[] = "IN (" . implode(',', $entry[$n['field']]) . ")";

                        $query = implode(' ', $parts);

                        $stmt = $this('db')->run($query);
                        $entry[$n['field']] = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                    }

                }

            }
        }

        return $entries;

    }, // end of normalizeGroupConcat()

    'listTables' => function($options = []) {

        $table_type = isset($options['type']) && $options['type'] == 'view'
                      ? 'VIEW' : 'BASE TABLE';

        $parts[] = "SELECT `TABLE_NAME`";
        $parts[] = "FROM `information_schema`.`TABLES`";
        $parts[] = "WHERE `TABLE_SCHEMA` = :database";
        $parts[] = "AND `TABLE_TYPE` = :table_type";

        $params = [
            ':database'   => $this->dbname,
            ':table_type' => $table_type,
        ];

        // check, if single table exists
        if (!empty($options['name'])) {
            $parts[] = "AND `TABLE_NAME` = :table_name";
            $params[':table_name'] = $options['name'];
        }

        $query = implode(' ', $parts);

        $tables = $this('db')->run($query, $params)->fetchAll(\PDO::FETCH_COLUMN);

        return $tables;

    }, // end of listTables()

]);

/***
 * first argument:
 * input:  (string)  column_name  || (array) ['table_name','column_name']
 * output: (string) `column_name` || (string) `table_name`.`column_name`
 * 
 * second argument (optional)
 * add " AS `column_name`" to the select statement
 */
function sqlIdentQuote($identifier, $as = null) {

    $escaped = null;
    $as = $as ? " AS `$as`" : '';

    if (!is_array($identifier)) {
        $escaped = trim($identifier);
        return ($escaped == '*') ? $escaped : "`$escaped`" . ($as ?? '');
    }

    foreach ($identifier as $part) {
        $escaped[] = sqlIdentQuote($part);
    }
    
    return implode('.', $escaped) . ($as ?? '');

} // end of sqlIdentQuote()

// ACL
include_once(__DIR__.'/acl.php');
