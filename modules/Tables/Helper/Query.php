<?php

namespace Tables\Helper;

class Query {

    // variables for a possible multi database usage and prefixed tables in 
    // the future - not implemented yet
    protected $host   = '';
    protected $dbname = '';
    protected $prefix = '';

    // mandatory variables
    protected $_table      = [];         // table definitions
    protected $table       = '';         // table name
    protected $primary_key = '';         // column name of primary key

    // query variables
    protected $select   = [];
    protected $joins    = [];
    protected $group_by = '';
    protected $where    = [];
    protected $order_by = [];
    protected $query    = '';
    protected $params   = [];

    protected $where_in = [];           // subquery to filter by m:n entries

    // cast filter options
    protected $fields          = null;  // (un)select columns
    protected $populate        = false; // auto-join
    protected $limit           = null;  // LIMIT
    protected $offset          = 0;     // OFFSET
    protected $fulltext_search = false; // WHERE foo LIKE "%bar%" AND baz LIKE "%bar%"
    protected $filter          = false; // WHERE foo = "bar"
    protected $sort            = false; // ORDER BY

    // cast fields
    protected $database_columns = [];   // check field names against column names
    protected $available_fields = [];   // for filter iterations
    protected $normalize        = [];   // contains field names with GROUP_CONCAT strings

    public function __construct($app, $db_config) {

        $this->app = $app;

        $this->host   = $db_config['host']   ?? '';
        $this->dbname = $db_config['dbname'] ?? '';
        $this->prefix = $db_config['prefix'] ?? '';

    } // end of __construct()

    public function init($_table, $options) {

        $this->_table = $_table;

        $this->table            = $this->_table['_id'] ?? $this->_table['name'];
        $this->primary_key      = $this->_table['primary_key'];
        $this->database_columns = $this->_table['database_schema']['columns'] ?? [];

        $this->initFilters($options);
        $this->initFields();
        $this->setWhere();
        $this->setOrderBy();
        $this->setQuery();

        return $this;

    } // end of init()

    public function initFilters($options) {

        // cast filter options
        $this->fields   = $options['fields']   ?? null; // (un)select columns

        $this->populate = $options['populate'] ?? false; // auto-join

        $this->limit    = isset($options['limit']) ? (int)$options['limit'] : null;
        $this->offset   = isset($options['skip'])  ? (int)$options['skip']  : 0;

        $this->fulltext_search = !empty($options['filter'])
                && is_string($options['filter'])
                ? $options['filter'] : false;

        $this->filter   = !empty($options['filter'])
                && is_array($options['filter']) && !$this->fulltext_search
                ? $options['filter'] : false;

        $this->sort     = isset($options['sort']) && is_array($options['sort'])
                ? array_map(function($e){return $e == -1 ? 'DESC' : 'ASC';}, $options['sort'])
                : false;

    } // end of initFilters()

    public function initFields() {

        foreach ($this->_table['fields'] as $field) {

            // quick fix for boolean field search filters
            // not the right place, but I don't want to iterate over all fields again
            if(isset($this->filter[$field['name']]) && $field['type'] == 'boolean') {
                $this->filter[$field['name']] = $this->fixBooleanFieldFilter($this->filter[$field['name']]);
            }

            // To do:
            // This line is the reason, why some search filters don't work, when
            // fields filters are active
            if ($this->is_filtered_out($field['name'])) {
                continue;
            }

            // column exists in current table
            if (in_array($field['name'], $this->database_columns)){

                // normal fields, do standard logic
                if ($field['type'] != 'relation') {
                    $this->initNormalField($field);
                }

                // resolve one-to-many fields
                else {
                    $this->initOneToManyField($field);
                }

            }

            // resolve many-to-many and many-to-one fields
            elseif ($field['type'] == 'relation') {

                if (isset($field['options']['type']) && $field['options']['type'] == 'many-to-one') {
                    $this->initManyToOneField($field);
                } else {
                    $this->initManyToManyField($field);
                }

            }

        }

    } // end of initFields()

    public function initNormalField($field) {

        $this->select[] = sqlIdentQuote([$this->table, $field['name']], $field['name']);
        $this->available_fields[] = ['table' => $this->table, 'field' => $field['name']];

    } // end of initNormalField()

    public function initOneToManyField($field) {
        
        $ref = $this->getReferences($this->table, $field['name'], 'references');

        $display_field = $field['options']['source']['display_field'] ?? $field['options']['source']['identifier'] ?? false;

        if (!$ref) return;

        if (!($this->app->module('tables')->hasaccess($ref['table'], 'entries_view')
          ||  $this->app->module('tables')->hasaccess($ref['table'], 'populate'))) {
            return;
        }

        // no auto-join
        if (!$this->populate) {
            $this->select[] = sqlIdentQuote([$this->table, $field['name']], $field['name']);
            $this->available_fields[] = ['table' => $this->table, 'field' => $field['name']];
        }

        // auto-join
        else {

            $referenced_table = $ref['table'];

            $this->joins[] = "LEFT OUTER JOIN " . sqlIdentQuote($referenced_table);
            $this->joins[] = "ON " . sqlIdentQuote([$this->table, $field['name']]);
            $this->joins[] = "= " . sqlIdentQuote([$referenced_table, $ref['field']]); // to do: params

            // $this->select[] = sqlIdentQuote([$referenced_table, $ref['display_field']], $field['name']);
            $this->select[] = sqlIdentQuote([$referenced_table, $display_field], $field['name']);
            // $this->available_fields[] = ['table' => $referenced_table, 'field' => $ref['display_field']];
            $this->available_fields[] = ['table' => $referenced_table, 'field' => $display_field];
            $this->available_fields[] = ['table' => $this->table, 'field' => $field['name']];

        }

    } // end of initOneToManyField()

    public function initManyToOneField($field) {

        $table = $field['options']['source']['table'];
        $key   = $field['options']['source']['identifier'];
        $r_key = $field['options']['source']['related_identifier'];

        if (!($this->app->module('tables')->hasaccess($table, 'entries_view')
          ||  $this->app->module('tables')->hasaccess($table, 'populate'))) {
            return;
        }

        $separator = $field['options']['separator'] ?? ',';

        $this->joins[] = "LEFT OUTER JOIN " . sqlIdentQuote($table);
        $this->joins[] = "ON " . sqlIdentQuote([$this->table, $this->primary_key]);
        $this->joins[] = "= " . sqlIdentQuote([$table, $r_key]);

        $select_comma_separated = sqlIdentQuote([$table, $key]);

        $this->available_fields[] = ['table' => $table, 'field' => $r_key];

        $this->select[] = "GROUP_CONCAT(DISTINCT $select_comma_separated SEPARATOR '$separator') AS " . sqlIdentQuote($field['name']);

        if (empty($this->group_by)) {
            $this->group_by = sqlIdentQuote([$this->table, $this->primary_key]);
        }

        // GROUP_CONCAT IDs and add a key, so normalizeGroupConcat() knows,
        // how to handle it --> used for entries view and for export
        if ($this->populate == 2) {

            $display_field = $field['options']['source']['display_field'] ?? false;

            $this->normalize[] = [
                'field' => $field['name'],
                'separator' => $separator,
                'populate' => [
                    'table' => $table,
                    'field' => $display_field,
                ]
            ];

        }

        // GROUP_CONCAT IDs
        else {
            $this->normalize[] = ['field' => $field['name'], 'separator' => $separator];
        }

    } // end of initManyToOneField()

    public function initManyToManyField($field) {

        // check options

        $many_to_many_table     = $field['options']['target']['table'] ?? false;
        $many_to_many_table_key = $field['options']['target']['identifier'] ?? false;

        $referenced_table       = $field['options']['source']['table'] ?? false;
        $referenced_table_key   = $field['options']['source']['identifier'] ?? false;
        $referenced_table_field = $field['options']['target']['related_identifier'] ?? false;

        if (!($this->app->module('tables')->hasaccess($referenced_table, 'entries_view')
          ||  $this->app->module('tables')->hasaccess($referenced_table, 'populate'))) {
            return;
        }

        // don't break the query if one of the options is not set or is empty string

        if ( empty($many_to_many_table)
          || empty($many_to_many_table_key)
          || empty($referenced_table)
          || empty($referenced_table_key)
          || empty($referenced_table_field)
        ) return;

        // build the query with two joins and a GROUP_CONCAT with separator

        $separator = $field['options']['separator'] ?? ',';

        $this->joins[] = "LEFT OUTER JOIN " . sqlIdentQuote($many_to_many_table);
        $this->joins[] = "ON " . sqlIdentQuote([$this->table, $this->primary_key]);
        $this->joins[] = "= " . sqlIdentQuote([$many_to_many_table, $many_to_many_table_key]);

        $select_comma_separated = sqlIdentQuote([$many_to_many_table, $referenced_table_field]);
        // $this->available_fields[] = ['table' => $many_to_many_table, 'field' => $referenced_table_field];

        $this->select[] = "GROUP_CONCAT(DISTINCT $select_comma_separated SEPARATOR '$separator') AS " . sqlIdentQuote($field['name']);

        // GROUP_CONCAT IDs and add a key, so normalizeGroupConcat() knows, how to handle it
        // used for entries view and for export
        if ($this->populate == 2) {

            $source_table_display_field = $field['options']['source']['display_field'] ?? false;
            $source_table = $field['options']['source']['table'] ?? false;

            $this->normalize[] = [
                'field' => $field['name'],
                'separator' => $separator,
                'populate' => [
                    'table' => $source_table,
                    'field' => $source_table_display_field,
                ]
            ];

            // EXPERIMENTAL!!! - find entries by m:n value
            if ($this->fulltext_search || isset($this->filter[$field['name']])) {
                $this->createSubQuery($field);
            }

        }

        // GROUP_CONCAT IDs
        else {
            $this->normalize[] = ['field' => $field['name'], 'separator' => $separator];
        }

        // make m:n fields sortable (and searchable --> doesn't work properly)
        // if ($this->fulltext_search
        if (isset($this->filter[$field['name']])
            || isset($this->sort[$field['name']])
            ) {

            $this->joins[] = "LEFT OUTER JOIN " . sqlIdentQuote($referenced_table);
            $this->joins[] = "ON " . sqlIdentQuote([$many_to_many_table, $referenced_table_field]);
            $this->joins[] = "= " . sqlIdentQuote([$referenced_table, $referenced_table_key]);

            if ($this->populate == 2) {
                $this->available_fields[] = ['table' => $source_table, 'field' => $source_table_display_field];
            }
            
        }

        // make m:n fields sortable
        if (isset($this->sort[$field['name']])) {

            // replace the virtual field name with the actual table representation
            // and keep the existing order
            $new_sort = [];
            foreach($this->sort as $key => $val) {

                if ($key == $field['name']) {

                    // sort by display_field
                    if ($this->populate == 2) {
                        $new_sort[$source_table_display_field] = $val;
                    }

                    // sort by id
                    else {
                        $new_sort[$referenced_table_field] = $val;
                    }

                } else {
                    $new_sort[$key] = $val;
                }

            }
            $this->sort = $new_sort;

        }

        // GROUP BY is always the same, don't overwrite it for all relation fields
        if (empty($this->group_by)) {
            $this->group_by = sqlIdentQuote([$this->table, $this->primary_key]);
        }

    }

    public function setWhere() {

        $filter = $this->filter;

        // apply filters to fields, that aren't available
        // full text search doesn't work if fields are filtered out
        // and field is not in positive field list

        $fields = [];
        foreach ($this->database_columns as $col) {
            $fields[] = ['table' => $this->table, 'field' => $col];
        }

        $fields = array_unique(array_merge(
            $fields,
            $this->available_fields
        ), SORT_REGULAR);

        // fulltext search WHERE foo LIKE %bar%
        // may cause performance issues
        // to do: FTS keys and MATCH AGAINST for better performance
        if ($this->fulltext_search) {

            foreach ($fields as $field) {

                if ($field['field'] == $this->primary_key) continue;

                $this->where[] = isset($this->where[0]) ? 'OR' : 'WHERE';

                $this->where[] = sqlIdentQuote([$field['table'], $field['field']]) . " LIKE :{$field['field']}_fulltextsearch";

                $this->params[":{$field['field']}_fulltextsearch"] = "%{$this->fulltext_search}%";

            }

            if (!empty($this->where_in)) {
                foreach ($this->where_in as $where_in) {
                    $this->where[] = isset($this->where[0]) ? 'OR' : 'WHERE';
                    $this->where[] = $where_in;
                }
            }

        }

        // exact match WHERE foo="bar" AND ...
        // doesn't work for m:n fields
        elseif ($filter) {

            foreach ($fields as $field) {

                if (isset($filter[$field['field']])) {

                    if (is_array($filter[$field['field']])) {

                        if (isset($filter[$field['field']]['$in'])) {
                            $in = '';
                            foreach ($filter[$field['field']]['$in'] as $k => $v) {

                                $in .= ":{$field['field']}_in_{$k},";
                                $this->params[":{$field['field']}_in_{$k}"] = $v;

                            }
                            $this->where[] = isset($this->where[0]) ? 'AND' : 'WHERE';
                            $this->where[] = sqlIdentQuote([$field['table'], $field['field']]) . ' IN (' . rtrim($in, ',') . ')';
                        }
                        else {
                            continue;
                            // to do: add mongo filter options like $and, $or etc
                        }

                    }

                    else {
                        // quick check if search term ends with asterisk
                        $search = $filter[$field['field']];
                        preg_match('#(.*)\*#', $search, $matches);

                        if (isset($matches[1])) {
                            $this->where[] = isset($this->where[0]) ? 'AND' : 'WHERE';
                            $this->where[] = sqlIdentQuote([$field['table'], $field['field']]) . ' LIKE :' . $field['field'];
                            $search = '%' . $matches[1] . '%';

                        }

                        else {
                            $this->where[] = isset($this->where[0]) ? 'AND' : 'WHERE';
                            $this->where[] = sqlIdentQuote([$field['table'], $field['field']]) . ' = :' . $field['field'];
                        }

                        $this->params[":".$field['field']] = $search;
                    }

                }

            }

            // EXPERIMENTAL!!!

            // $or filter - WHERE foo = "bar" OR baz = "foobar"
            // to do:
            // * doesn't work with referenced tables, yet
            if (isset($filter['$or'])) {

                foreach ($filter['$or'] as $field_name => $field_filter) {

                    if (!in_array($field_name, $this->_table['database_schema']['columns'])) {
                        continue;
                    }
                    
                    // quick check if search term ends with asterisk
                    $search = $field_filter;
                    preg_match('#(.*)\*#', $search, $matches);

                    if (isset($matches[1])) {
                        $this->where[] = isset($this->where[0]) ? 'OR' : 'WHERE';
                        $this->where[] = sqlIdentQuote([$this->table, $field_name]) . ' LIKE :' . $field_name;
                        $search = '%' . $matches[1] . '%';
                    }

                    else {
                        $this->where[] = isset($this->where[0]) ? 'OR' : 'WHERE';
                        $this->where[] = sqlIdentQuote([$this->table, $field_name]) . ' = :' . $field_name;
                    }

                    $this->params[":".$field_name] = $search;

                }

            }

            if (!empty($this->where_in)) {
                foreach ($this->where_in as $where_in) {
                    $this->where[] = isset($this->where[0]) ? 'AND' : 'WHERE';
                    $this->where[] = $where_in;
                }
            }

        }

    } // end of setWhere()

    public function setOrderBy() {

        $fields = array_column($this->available_fields, 'table', 'field');

        if ($this->sort) {

            foreach ($this->sort as $field => $direction) {

                if (isset($fields[$field])) {

                    $this->order_by[] = sqlIdentQuote([$fields[$field], $field]) . " " . $direction;

                }

            }

        }

    } // end of setOrderBy()

    public function setQuery() {

        $parts = [];

        $parts[] = "SELECT " . implode(', ', $this->select);
        $parts[] = "FROM " . sqlIdentQuote($this->table);

        if (!empty($this->joins))    $parts[] = implode(' ', $this->joins);
        if (!empty($this->where))    $parts[] = implode(' ', $this->where);
        if (!empty($this->group_by)) $parts[] = "GROUP BY " . $this->group_by;
        if (!empty($this->order_by)) $parts[] = "ORDER BY " . implode(', ', $this->order_by);
        if ($this->limit)            $parts[] = "LIMIT ".$this->offset.", ".$this->limit;

        $this->query = implode(' ', $parts);

    } // end of setQuery()

    public function getQuery($extended = false) {

        if ($extended) {
            return [
                'query'     => $this->query,
                'params'    => $this->params,
                'normalize' => $this->normalize
            ];
        }

        else {
            return $this->query;
        }

    } // end of getQuery()

    public function getParams() {

        return $this->params;

    } // end of getParams()

    public function getNormalizeInfo() {

        return $this->normalize;

    } // end of getNormalizeInfo()

    public function is_filtered_out($field_name) {

        // select all
        if (!$this->fields) return false;

        // one filter is set to true - don't select any other fields
        if (in_array(true, $this->fields)) {

            if (isset($this->fields[$field_name]) && $this->fields[$field_name] == true) {
                return false;
            }

            // return primary_key, too if not explicitly set to false
            if ($field_name == $this->primary_key && ( !isset($this->fields[$this->primary_key]) || $this->fields[$this->primary_key] == true)) {
                return false;
            }

            return true;

        }

        else {

            if (!isset($this->fields[$field_name])) {
                return false;
            }

            if (isset($this->fields[$field_name]) && $this->fields[$field_name] == false) {
                return true;
            }

        }

    } // end of is_filtered_out()

    public function getReferences($table_name, $field_name, $type) {

        return $this->app->module('tables')->getReferences($table_name, $field_name, $type);

    } // end of getReferences()

    public function fixBooleanFieldFilter($value) {

        if ($value === true || $value === 'true')
            return 1;

        return 0;

    } // end of fixBooleanFieldFilter()

    public function createSubQuery($field) {

        $many_to_many_table         = $field['options']['target']['table'];
        $many_to_many_table_key     = $field['options']['target']['identifier'];
        $referenced_table_field     = $field['options']['target']['related_identifier'];
        $source_table               = $field['options']['source']['table'] ?? false;
        $source_table_display_field = $field['options']['source']['display_field'] ?? false;

        $quoted_source = sqlIdentQuote([$source_table, $source_table_display_field]);
        $quoted_ref    = sqlIdentQuote([$many_to_many_table, $many_to_many_table_key]);

        $subquery = [];
        $subquery[] = sqlIdentQuote([$this->table, $this->primary_key]);
        $subquery[] = 'IN (';

        $subquery[] = 'SELECT ' . $quoted_ref;
        $subquery[] = 'FROM ' . sqlIdentQuote($many_to_many_table);

        $subquery[] = "LEFT OUTER JOIN " . sqlIdentQuote($source_table);
        $subquery[] = "ON " . sqlIdentQuote([$source_table, 'id']);
        $subquery[] = "= " . sqlIdentQuote([$many_to_many_table, $referenced_table_field]);

        if ($this->fulltext_search) {
            $subquery[] = 'WHERE';
            $subquery[] = $quoted_source;
            $subquery[] = "LIKE :{$field['name']}_fulltextsearch";

            $this->params[":{$field['name']}_fulltextsearch"] = '%' . $this->fulltext_search . '%';
        } else {

            $filter = $this->filter[$field['name']];

            if (is_string($filter)) {
                $subquery[] = 'WHERE';
                $subquery[] = $quoted_source;
                $subquery[] = "= :{$field['name']}";

                $this->params[":{$field['name']}"] = $filter;
            }

            if (is_array($this->filter[$field['name']])) {
                $where  = [];

                // AND - ignore the order, so it's the same like $all
                if (isset($filter[0])) {
                    $filter = ['$all' => $filter];
                }
                if (isset($filter['$all'])) {

                    $count = count($filter['$all']);
                    foreach ($filter['$all'] as $k => $val) {

                        $where[] = isset($where[0]) ? 'OR' : 'WHERE';
                        $where[] = $quoted_source;
                        $where[] = "= :{$field['name']}_all_{$k}";

                        $this->params[":{$field['name']}_all_{$k}"] = $val;
                    }

                    $where[] = 'GROUP BY ' . $quoted_ref;
                    $where[] = 'HAVING COUNT(*) > ' . ($count - 1);

                }

                // OR
                if (isset($filter['$in'])) {

                    foreach ($filter['$in'] as $k => $val) {

                        $where[] = isset($where[0]) ? 'OR' : 'WHERE';
                        $where[] = $quoted_source;
                        $where[] = "= :{$field['name']}_in_{$k}";

                        $this->params[":{$field['name']}_in_{$k}"] = $val;

                    }
                }

                $subquery[] = implode(' ', $where);

            }

        }

        $subquery[] = ')';
        $this->where_in[] = implode(' ', $subquery);

    } // end of createSubQuery()

}




if (!function_exists('sqlIdentQuote')) {

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

    }

}
