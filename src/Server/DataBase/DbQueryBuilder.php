<?php
/**
 * DbQueryBuilder
 *
 * @author camera360_server@camera360.com
 * @copyright Chengdu pinguo Technology Co.,Ltd.
 */

namespace PG\MSF\Server\DataBase;

use PG\MSF\Server\CoreBase\SwooleException;

class DbQueryBuilder
{

    /**
     * Table prefix
     *
     * @var    string
     */
    public $dbprefix = '';
    /**
     * Swap Prefix
     *
     * @var    string
     */
    public $swap_pre = '';
    /**
     * Bind marker
     *
     * Character used to identify values in a prepared statement.
     *
     * @var    string
     */
    public $bind_marker = '?';
    /**
     * @var MysqlAsynPool
     */
    public $dbDrive;
    /**
     * Return DELETE SQL flag
     *
     * @var    bool
     */
    protected $return_delete_sql = false;
    /**
     * Reset DELETE data flag
     *
     * @var    bool
     */
    protected $reset_delete_data = false;
    /**
     * QB SELECT data
     *
     * @var    array
     */
    protected $qb_select = array();
    /**
     * QB DISTINCT flag
     *
     * @var    bool
     */
    protected $qb_distinct = false;
    /**
     * QB FROM data
     *
     * @var    array
     */
    protected $qb_from = array();
    /**
     * QB JOIN data
     *
     * @var    array
     */
    protected $qb_join = array();
    /**
     * QB WHERE data
     *
     * @var    array
     */
    protected $qb_where = array();
    /**
     * QB GROUP BY data
     *
     * @var    array
     */
    protected $qb_groupby = array();
    /**
     * QB HAVING data
     *
     * @var    array
     */
    protected $qb_having = array();
    /**
     * QB keys
     *
     * @var    array
     */
    protected $qb_keys = array();
    /**
     * QB LIMIT data
     *
     * @var    int
     */
    protected $qb_limit = false;
    /**
     * QB OFFSET data
     *
     * @var    int
     */
    protected $qb_offset = false;
    /**
     * QB ORDER BY data
     *
     * @var    array
     */
    protected $qb_orderby = array();

    // Query Builder Caching variables
    /**
     * QB data sets
     *
     * @var    array
     */
    protected $qb_set = array();
    /**
     * QB aliased tables list
     *
     * @var    array
     */
    protected $qb_aliased_tables = array();
    /**
     * QB WHERE group started flag
     *
     * @var    bool
     */
    protected $qb_where_group_started = false;
    /**
     * QB WHERE group count
     *
     * @var    int
     */
    protected $qb_where_group_count = 0;
    /**
     * QB Caching flag
     *
     * @var    bool
     */
    protected $qb_caching = false;
    /**
     * QB Cache exists list
     *
     * @var    array
     */
    protected $qb_cache_exists = array();
    /**
     * QB Cache SELECT data
     *
     * @var    array
     */
    protected $qb_cache_select = array();
    /**
     * QB Cache FROM data
     *
     * @var    array
     */
    protected $qb_cache_from = array();
    /**
     * QB Cache JOIN data
     *
     * @var    array
     */
    protected $qb_cache_join = array();
    /**
     * QB Cache WHERE data
     *
     * @var    array
     */
    protected $qb_cache_where = array();
    /**
     * QB Cache GROUP BY data
     *
     * @var    array
     */
    protected $qb_cache_groupby = array();
    /**
     * QB Cache HAVING data
     *
     * @var    array
     */
    protected $qb_cache_having = array();
    /**
     * QB Cache ORDER BY data
     *
     * @var    array
     */
    protected $qb_cache_orderby = array();
    /**
     * QB Cache data sets
     *
     * @var    array
     */
    protected $qb_cache_set = array();
    /**
     * QB No Escape data
     *
     * @var    array
     */
    protected $qb_no_escape = array();
    /**
     * QB Cache No Escape data
     *
     * @var    array
     */
    protected $qb_cache_no_escape = array();
    /**
     * Protect identifiers flag
     *
     * @var    bool
     */
    protected $_protect_identifiers = true;
    /**
     * Identifier escape character
     *
     * @var    string
     */
    protected $_escape_char = '"';
    /**
     * List of reserved identifiers
     *
     * Identifiers that must NOT be escaped.
     *
     * @var    string[]
     */
    protected $_reserved_identifiers = array('*');
    /**
     * ESCAPE statement string
     *
     * @var    string
     */
    protected $_like_escape_str = " ESCAPE '%s' ";
    /**
     * ESCAPE character
     *
     * @var    string
     */
    protected $_like_escape_chr = '!';
    /**
     * ORDER BY random keyword
     *
     * @var    array
     */
    protected $_random_keyword = array('RAND()', 'RAND(%d)');

    public function __construct($dbDirve)
    {
        $this->dbDrive = $dbDirve;
    }
    // --------------------------------------------------------------------

    /**
     * Select
     *
     * Generates the SELECT portion of the query
     *
     * @param    string
     * @param    mixed
     * @return    DbQueryBuilder
     */
    public function select($select = '*', $escape = null)
    {
        if (is_string($select)) {
            $select = explode(',', $select);
        }

        // If the escape value was not set, we will base it on the global setting
        is_bool($escape) OR $escape = $this->_protect_identifiers;

        foreach ($select as $val) {
            $val = trim($val);

            if ($val !== '') {
                $this->qb_select[] = $val;
                $this->qb_no_escape[] = $escape;

                if ($this->qb_caching === true) {
                    $this->qb_cache_select[] = $val;
                    $this->qb_cache_exists[] = 'select';
                    $this->qb_cache_no_escape[] = $escape;
                }
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Select Max
     *
     * Generates a SELECT MAX(field) portion of a query
     *
     * @param    string    the field
     * @param    string    an alias
     * @return    DbQueryBuilder
     */
    public function select_max($select = '', $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'MAX');
    }

    // --------------------------------------------------------------------

    /**
     * SELECT [MAX|MIN|AVG|SUM]()
     *
     * @used-by    select_max()
     * @used-by    select_min()
     * @used-by    select_avg()
     * @used-by    select_sum()
     *
     * @param    string $select Field name
     * @param    string $alias
     * @param    string $type
     * @return    DbQueryBuilder
     */
    protected function _max_min_avg_sum($select = '', $alias = '', $type = 'MAX')
    {
        if (!is_string($select) OR $select === '') {
            throw new SwooleException('db_invalid_query');
        }

        $type = strtoupper($type);

        if (!in_array($type, array('MAX', 'MIN', 'AVG', 'SUM'))) {
            throw new SwooleException('Invalid function type: ' . $type);
        }

        if ($alias === '') {
            $alias = $this->_create_alias_from_table(trim($select));
        }

        $sql = $type . '(' . $this->protect_identifiers(trim($select)) . ') AS ' . $this->escape_identifiers(trim($alias));

        $this->qb_select[] = $sql;
        $this->qb_no_escape[] = null;

        if ($this->qb_caching === true) {
            $this->qb_cache_select[] = $sql;
            $this->qb_cache_exists[] = 'select';
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Determines the alias name based on the table
     *
     * @param    string $item
     * @return    string
     */
    protected function _create_alias_from_table($item)
    {
        if (strpos($item, '.') !== false) {
            $item = explode('.', $item);
            return end($item);
        }

        return $item;
    }

    // --------------------------------------------------------------------

    /**
     * Protect Identifiers
     *
     * This function is used extensively by the Query Builder class, and by
     * a couple functions in this class.
     * It takes a column or table name (optionally with an alias) and inserts
     * the table prefix onto it. Some logic is necessary in order to deal with
     * column names that include the path. Consider a query like this:
     *
     * SELECT hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @param    string
     * @param    bool
     * @param    mixed
     * @param    bool
     * @return    string
     */
    public function protect_identifiers(
        $item,
        $prefix_single = false,
        $protect_identifiers = null,
        $field_exists = true
    ) {
        if (!is_bool($protect_identifiers)) {
            $protect_identifiers = $this->_protect_identifiers;
        }

        if (is_array($item)) {
            $escaped_array = array();
            foreach ($item as $k => $v) {
                $escaped_array[$this->protect_identifiers($k)] = $this->protect_identifiers($v, $prefix_single,
                    $protect_identifiers, $field_exists);
            }

            return $escaped_array;
        }

        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix. There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        //
        // Added exception for single quotes as well, we don't want to alter
        // literal strings. -- Narf
        if (strcspn($item, "()'") !== strlen($item)) {
            return $item;
        }

        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace('/\s+/', ' ', trim($item));

        // If the item has an alias declaration we remove it and set it aside.
        // Note: strripos() is used in order to support spaces in table names
        if ($offset = strripos($item, ' AS ')) {
            $alias = ($protect_identifiers)
                ? substr($item, $offset, 4) . $this->escape_identifiers(substr($item, $offset + 4))
                : substr($item, $offset);
            $item = substr($item, 0, $offset);
        } elseif ($offset = strrpos($item, ' ')) {
            $alias = ($protect_identifiers)
                ? ' ' . $this->escape_identifiers(substr($item, $offset + 1))
                : substr($item, $offset);
            $item = substr($item, 0, $offset);
        } else {
            $alias = '';
        }

        // Break the string apart if it contains periods, then insert the table prefix
        // in the correct location, assuming the period doesn't indicate that we're dealing
        // with an alias. While we're at it, we will escape the components
        if (strpos($item, '.') !== false) {
            $parts = explode('.', $item);

            // Does the first segment of the exploded item match
            // one of the aliases previously identified? If so,
            // we have nothing more to do other than escape the item
            //
            // NOTE: The ! empty() condition prevents this method
            //       from breaking when QB isn't enabled.
            if (!empty($this->qb_aliased_tables) && in_array($parts[0], $this->qb_aliased_tables)) {
                if ($protect_identifiers === true) {
                    foreach ($parts as $key => $val) {
                        if (!in_array($val, $this->_reserved_identifiers)) {
                            $parts[$key] = $this->escape_identifiers($val);
                        }
                    }

                    $item = implode('.', $parts);
                }

                return $item . $alias;
            }

            // Is there a table prefix defined in the config file? If not, no need to do anything
            if ($this->dbprefix !== '') {
                // We now add the table prefix based on some logic.
                // Do we have 4 segments (hostname.database.table.column)?
                // If so, we add the table prefix to the column name in the 3rd segment.
                if (isset($parts[3])) {
                    $i = 2;
                }
                // Do we have 3 segments (database.table.column)?
                // If so, we add the table prefix to the column name in 2nd position
                elseif (isset($parts[2])) {
                    $i = 1;
                }
                // Do we have 2 segments (table.column)?
                // If so, we add the table prefix to the column name in 1st segment
                else {
                    $i = 0;
                }

                // This flag is set when the supplied $item does not contain a field name.
                // This can happen when this function is being called from a JOIN.
                if ($field_exists === false) {
                    $i++;
                }

                // Verify table prefix and replace if necessary
                if ($this->swap_pre !== '' && strpos($parts[$i], $this->swap_pre) === 0) {
                    $parts[$i] = preg_replace('/^' . $this->swap_pre . '(\S+?)/', $this->dbprefix . '\\1', $parts[$i]);
                } // We only add the table prefix if it does not already exist
                elseif (strpos($parts[$i], $this->dbprefix) !== 0) {
                    $parts[$i] = $this->dbprefix . $parts[$i];
                }

                // Put the parts back together
                $item = implode('.', $parts);
            }

            if ($protect_identifiers === true) {
                $item = $this->escape_identifiers($item);
            }

            return $item . $alias;
        }

        // Is there a table prefix? If not, no need to insert it
        if ($this->dbprefix !== '') {
            // Verify table prefix and replace if necessary
            if ($this->swap_pre !== '' && strpos($item, $this->swap_pre) === 0) {
                $item = preg_replace('/^' . $this->swap_pre . '(\S+?)/', $this->dbprefix . '\\1', $item);
            } // Do we prefix an item with no segments?
            elseif ($prefix_single === true && strpos($item, $this->dbprefix) !== 0) {
                $item = $this->dbprefix . $item;
            }
        }

        if ($protect_identifiers === true && !in_array($item, $this->_reserved_identifiers)) {
            $item = $this->escape_identifiers($item);
        }

        return $item . $alias;
    }

    // --------------------------------------------------------------------

    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @param    mixed
     * @return    mixed
     */
    public function escape_identifiers($item)
    {
        if ($this->_escape_char === '' OR empty($item) OR in_array($item, $this->_reserved_identifiers)) {
            return $item;
        } elseif (is_array($item)) {
            foreach ($item as $key => $value) {
                $item[$key] = $this->escape_identifiers($value);
            }

            return $item;
        } // Avoid breaking functions and literal values inside queries
        elseif (ctype_digit($item) OR $item[0] === "'" OR ($this->_escape_char !== '"' && $item[0] === '"') OR strpos($item,
                '(') !== false
        ) {
            return $item;
        }

        static $preg_ec = array();

        if (empty($preg_ec)) {
            if (is_array($this->_escape_char)) {
                $preg_ec = array(
                    preg_quote($this->_escape_char[0], '/'),
                    preg_quote($this->_escape_char[1], '/'),
                    $this->_escape_char[0],
                    $this->_escape_char[1]
                );
            } else {
                $preg_ec[0] = $preg_ec[1] = preg_quote($this->_escape_char, '/');
                $preg_ec[2] = $preg_ec[3] = $this->_escape_char;
            }
        }

        foreach ($this->_reserved_identifiers as $id) {
            if (strpos($item, '.' . $id) !== false) {
                return preg_replace('/' . $preg_ec[0] . '?([^' . $preg_ec[1] . '\.]+)' . $preg_ec[1] . '?\./i',
                    $preg_ec[2] . '$1' . $preg_ec[3] . '.', $item);
            }
        }

        return preg_replace('/' . $preg_ec[0] . '?([^' . $preg_ec[1] . '\.]+)' . $preg_ec[1] . '?(\.)?/i',
            $preg_ec[2] . '$1' . $preg_ec[3] . '$2', $item);
    }

    // --------------------------------------------------------------------

    /**
     * Select Min
     *
     * Generates a SELECT MIN(field) portion of a query
     *
     * @param    string    the field
     * @param    string    an alias
     * @return    DbQueryBuilder
     */
    public function select_min($select = '', $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'MIN');
    }

    // --------------------------------------------------------------------

    /**
     * Select Average
     *
     * Generates a SELECT AVG(field) portion of a query
     *
     * @param    string    the field
     * @param    string    an alias
     * @return    DbQueryBuilder
     */
    public function select_avg($select = '', $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'AVG');
    }

    // --------------------------------------------------------------------

    /**
     * Select Sum
     *
     * Generates a SELECT SUM(field) portion of a query
     *
     * @param    string    the field
     * @param    string    an alias
     * @return    DbQueryBuilder
     */
    public function select_sum($select = '', $alias = '')
    {
        return $this->_max_min_avg_sum($select, $alias, 'SUM');
    }

    // --------------------------------------------------------------------

    /**
     * DISTINCT
     *
     * Sets a flag which tells the query string compiler to add DISTINCT
     *
     * @param    bool $val
     * @return    DbQueryBuilder
     */
    public function distinct($val = true)
    {
        $this->qb_distinct = is_bool($val) ? $val : true;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * JOIN
     *
     * Generates the JOIN portion of the query
     *
     * @param    string
     * @param    string    the join condition
     * @param    string    the type of join
     * @param    string    whether not to try to escape identifiers
     * @return    DbQueryBuilder
     */
    public function join($table, $cond, $type = '', $escape = null)
    {
        if ($type !== '') {
            $type = strtoupper(trim($type));

            if (!in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'), true)) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }

        // Extract any aliases that might exist. We use this information
        // in the protect_identifiers to know whether to add a table prefix
        $this->_track_aliases($table);

        is_bool($escape) OR $escape = $this->_protect_identifiers;

        if (!$this->_has_operator($cond)) {
            $cond = ' USING (' . ($escape ? $this->escape_identifiers($cond) : $cond) . ')';
        } elseif ($escape === false) {
            $cond = ' ON ' . $cond;
        } else {
            // Split multiple conditions
            if (preg_match_all('/\sAND\s|\sOR\s/i', $cond, $joints, PREG_OFFSET_CAPTURE)) {
                $conditions = array();
                $joints = $joints[0];
                array_unshift($joints, array('', 0));

                for ($i = count($joints) - 1, $pos = strlen($cond); $i >= 0; $i--) {
                    $joints[$i][1] += strlen($joints[$i][0]); // offset
                    $conditions[$i] = substr($cond, $joints[$i][1], $pos - $joints[$i][1]);
                    $pos = $joints[$i][1] - strlen($joints[$i][0]);
                    $joints[$i] = $joints[$i][0];
                }
            } else {
                $conditions = array($cond);
                $joints = array('');
            }

            $cond = ' ON ';
            for ($i = 0, $c = count($conditions); $i < $c; $i++) {
                $operator = $this->_get_operator($conditions[$i]);
                $cond .= $joints[$i];
                $cond .= preg_match("/(\(*)?([\[\]\w\.'-]+)" . preg_quote($operator) . "(.*)/i", $conditions[$i],
                    $match)
                    ? $match[1] . $this->protect_identifiers($match[2]) . $operator . $this->protect_identifiers($match[3])
                    : $conditions[$i];
            }
        }

        // Do we want to escape the table name?
        if ($escape === true) {
            $table = $this->protect_identifiers($table, true, null, false);
        }

        // Assemble the JOIN statement
        $this->qb_join[] = $join = $type . 'JOIN ' . $table . $cond;

        if ($this->qb_caching === true) {
            $this->qb_cache_join[] = $join;
            $this->qb_cache_exists[] = 'join';
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Track Aliases
     *
     * Used to track SQL statements written with aliased tables.
     *
     * @param    string    The table to inspect
     * @return    string
     */
    protected function _track_aliases($table)
    {
        if (is_array($table)) {
            foreach ($table as $t) {
                $this->_track_aliases($t);
            }
            return;
        }

        // Does the string contain a comma?  If so, we need to separate
        // the string into discreet statements
        if (strpos($table, ',') !== false) {
            return $this->_track_aliases(explode(',', $table));
        }

        // if a table alias is used we can recognize it by a space
        if (strpos($table, ' ') !== false) {
            // if the alias is written with the AS keyword, remove it
            $table = preg_replace('/\s+AS\s+/i', ' ', $table);

            // Grab the alias
            $table = trim(strrchr($table, ' '));

            // Store the alias, if it doesn't already exist
            if (!in_array($table, $this->qb_aliased_tables)) {
                $this->qb_aliased_tables[] = $table;
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * Tests whether the string has an SQL operator
     *
     * @param    string
     * @return    bool
     */
    protected function _has_operator($str)
    {
        return (bool)preg_match('/(<|>|!|=|\sIS NULL|\sIS NOT NULL|\sEXISTS|\sBETWEEN|\sLIKE|\sIN\s*\(|\s)/i',
            trim($str));
    }

    // --------------------------------------------------------------------

    /**
     * Returns the SQL string operator
     *
     * @param    string
     * @return    string
     */
    protected function _get_operator($str)
    {
        static $_operators;

        if (empty($_operators)) {
            $_les = ($this->_like_escape_str !== '')
                ? '\s+' . preg_quote(trim(sprintf($this->_like_escape_str, $this->_like_escape_chr)), '/')
                : '';
            $_operators = array(
                '\s*(?:<|>|!)?=\s*',             // =, <=, >=, !=
                '\s*<>?\s*',                     // <, <>
                '\s*>\s*',                       // >
                '\s+IS NULL',                    // IS NULL
                '\s+IS NOT NULL',                // IS NOT NULL
                '\s+EXISTS\s*\(.*\)',        // EXISTS(sql)
                '\s+NOT EXISTS\s*\(.*\)',    // NOT EXISTS(sql)
                '\s+BETWEEN\s+',                 // BETWEEN value AND value
                '\s+IN\s*\(.*\)',            // IN(list)
                '\s+NOT IN\s*\(.*\)',        // NOT IN (list)
                '\s+LIKE\s+\S.*(' . $_les . ')?',    // LIKE 'expr'[ ESCAPE '%s']
                '\s+NOT LIKE\s+\S.*(' . $_les . ')?' // NOT LIKE 'expr'[ ESCAPE '%s']
            );

        }

        return preg_match('/' . implode('|', $_operators) . '/i', $str, $match)
            ? $match[0] : false;
    }

    // --------------------------------------------------------------------

    /**
     * OR WHERE
     *
     * Generates the WHERE portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param    mixed
     * @param    mixed
     * @param    bool
     * @return    DbQueryBuilder
     */
    public function or_where($key, $value = null, $escape = null)
    {
        return $this->_wh('qb_where', $key, $value, 'OR ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * WHERE, HAVING
     *
     * @used-by    where()
     * @used-by    or_where()
     * @used-by    having()
     * @used-by    or_having()
     *
     * @param    string $qb_key 'qb_where' or 'qb_having'
     * @param    mixed $key
     * @param    mixed $value
     * @param    string $type
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    protected function _wh($qb_key, $key, $value = null, $type = 'AND ', $escape = null)
    {
        $qb_cache_key = ($qb_key === 'qb_having') ? 'qb_cache_having' : 'qb_cache_where';

        if (!is_array($key)) {
            $key = array($key => $value);
        }

        // If the escape value was not set will base it on the global setting
        is_bool($escape) OR $escape = $this->_protect_identifiers;

        foreach ($key as $k => $v) {
            $prefix = (count($this->$qb_key) === 0 && count($this->$qb_cache_key) === 0)
                ? $this->_group_get_type('')
                : $this->_group_get_type($type);

            if ($v !== null) {
                if ($escape === true) {
                    $v = ' ' . $this->escape($v);
                }

                if (!$this->_has_operator($k)) {
                    $k .= ' = ';
                }
            } elseif (!$this->_has_operator($k)) {
                // value appears not to have been set, assign the test to IS NULL
                $k .= ' IS NULL';
            } elseif (preg_match('/\s*(!?=|<>|IS(?:\s+NOT)?)\s*$/i', $k, $match, PREG_OFFSET_CAPTURE)) {
                $k = substr($k, 0, $match[0][1]) . ($match[1][0] === '=' ? ' IS NULL' : ' IS NOT NULL');
            }

            $this->{$qb_key}[] = array('condition' => $prefix . $k . $v, 'escape' => $escape);
            if ($this->qb_caching === true) {
                $this->{$qb_cache_key}[] = array('condition' => $prefix . $k . $v, 'escape' => $escape);
                $this->qb_cache_exists[] = substr($qb_key, 3);
            }

        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Group_get_type
     *
     * @used-by    group_start()
     * @used-by    _like()
     * @used-by    _wh()
     * @used-by    _where_in()
     *
     * @param    string $type
     * @return    string
     */
    protected function _group_get_type($type)
    {
        if ($this->qb_where_group_started) {
            $type = '';
            $this->qb_where_group_started = false;
        }

        return $type;
    }

    // --------------------------------------------------------------------

    /**
     * "Smart" Escape String
     *
     * Escapes data based on type
     * Sets boolean and null types
     *
     * @param    string
     * @return    mixed
     */
    public function escape($str)
    {
        if (is_array($str)) {
            $str = array_map(array(&$this, 'escape'), $str);
            return $str;
        } elseif (is_string($str) OR (is_object($str) && method_exists($str, '__toString'))) {
            return "'" . $this->escape_str($str) . "'";
        } elseif (is_bool($str)) {
            return ($str === false) ? 0 : 1;
        } elseif ($str === null) {
            return 'NULL';
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Escape String
     *
     * @param    string|string[] $str Input string
     * @param    bool $like Whether or not the string will be used in a LIKE condition
     * @return    string
     */
    public function escape_str($str, $like = false)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->escape_str($val, $like);
            }

            return $str;
        }

        $str = $this->_escape_str($str);

        // escape LIKE condition wildcards
        if ($like === true) {
            return str_replace(
                array($this->_like_escape_chr, '%', '_'),
                array(
                    $this->_like_escape_chr . $this->_like_escape_chr,
                    $this->_like_escape_chr . '%',
                    $this->_like_escape_chr . '_'
                ),
                $str
            );
        }

        return $str;
    }

    // --------------------------------------------------------------------

    /**
     * Platform-dependant string escape
     *
     * @param    string
     * @return    string
     */
    protected function _escape_str($str)
    {
        return str_replace("'", "''", remove_invisible_characters($str));
    }

    // --------------------------------------------------------------------

    /**
     * WHERE IN
     *
     * Generates a WHERE field IN('item', 'item') SQL query,
     * joined with 'AND' if appropriate.
     *
     * @param    string $key The field to search
     * @param    array $values The values searched on
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function where_in($key = null, $values = null, $escape = null)
    {
        return $this->_where_in($key, $values, false, 'AND ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * Internal WHERE IN
     *
     * @used-by    where_in()
     * @used-by    or_where_in()
     * @used-by    where_not_in()
     * @used-by    or_where_not_in()
     *
     * @param    string $key The field to search
     * @param    array $values The values searched on
     * @param    bool $not If the statement would be IN or NOT IN
     * @param    string $type
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    protected function _where_in($key = null, $values = null, $not = false, $type = 'AND ', $escape = null)
    {
        if ($key === null OR $values === null) {
            return $this;
        }

        if (!is_array($values)) {
            $values = array($values);
        }

        is_bool($escape) OR $escape = $this->_protect_identifiers;

        $not = ($not) ? ' NOT' : '';

        if ($escape === true) {
            $where_in = array();
            foreach ($values as $value) {
                $where_in[] = $this->escape($value);
            }
        } else {
            $where_in = array_values($values);
        }

        $prefix = (count($this->qb_where) === 0 && count($this->qb_cache_where) === 0)
            ? $this->_group_get_type('')
            : $this->_group_get_type($type);

        $where_in = array(
            'condition' => $prefix . $key . $not . ' IN(' . implode(', ', $where_in) . ')',
            'escape' => $escape
        );

        $this->qb_where[] = $where_in;
        if ($this->qb_caching === true) {
            $this->qb_cache_where[] = $where_in;
            $this->qb_cache_exists[] = 'where';
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * OR WHERE IN
     *
     * Generates a WHERE field IN('item', 'item') SQL query,
     * joined with 'OR' if appropriate.
     *
     * @param    string $key The field to search
     * @param    array $values The values searched on
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function or_where_in($key = null, $values = null, $escape = null)
    {
        return $this->_where_in($key, $values, false, 'OR ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * WHERE NOT IN
     *
     * Generates a WHERE field NOT IN('item', 'item') SQL query,
     * joined with 'AND' if appropriate.
     *
     * @param    string $key The field to search
     * @param    array $values The values searched on
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function where_not_in($key = null, $values = null, $escape = null)
    {
        return $this->_where_in($key, $values, true, 'AND ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * OR WHERE NOT IN
     *
     * Generates a WHERE field NOT IN('item', 'item') SQL query,
     * joined with 'OR' if appropriate.
     *
     * @param    string $key The field to search
     * @param    array $values The values searched on
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function or_where_not_in($key = null, $values = null, $escape = null)
    {
        return $this->_where_in($key, $values, true, 'OR ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * LIKE
     *
     * Generates a %LIKE% portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param    mixed $field
     * @param    string $match
     * @param    string $side
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function like($field, $match = '', $side = 'both', $escape = null)
    {
        return $this->_like($field, $match, 'AND ', $side, '', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * Internal LIKE
     *
     * @used-by    like()
     * @used-by    or_like()
     * @used-by    not_like()
     * @used-by    or_not_like()
     *
     * @param    mixed $field
     * @param    string $match
     * @param    string $type
     * @param    string $side
     * @param    string $not
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    protected function _like($field, $match = '', $type = 'AND ', $side = 'both', $not = '', $escape = null)
    {
        if (!is_array($field)) {
            $field = array($field => $match);
        }

        is_bool($escape) OR $escape = $this->_protect_identifiers;
        // lowercase $side in case somebody writes e.g. 'BEFORE' instead of 'before' (doh)
        $side = strtolower($side);

        foreach ($field as $k => $v) {
            $prefix = (count($this->qb_where) === 0 && count($this->qb_cache_where) === 0)
                ? $this->_group_get_type('') : $this->_group_get_type($type);

            if ($escape === true) {
                $v = $this->escape_like_str($v);
            }

            if ($side === 'none') {
                $like_statement = "{$prefix} {$k} {$not} LIKE '{$v}'";
            } elseif ($side === 'before') {
                $like_statement = "{$prefix} {$k} {$not} LIKE '%{$v}'";
            } elseif ($side === 'after') {
                $like_statement = "{$prefix} {$k} {$not} LIKE '{$v}%'";
            } else {
                $like_statement = "{$prefix} {$k} {$not} LIKE '%{$v}%'";
            }

            // some platforms require an escape sequence definition for LIKE wildcards
            if ($escape === true && $this->_like_escape_str !== '') {
                $like_statement .= sprintf($this->_like_escape_str, $this->_like_escape_chr);
            }

            $this->qb_where[] = array('condition' => $like_statement, 'escape' => $escape);
            if ($this->qb_caching === true) {
                $this->qb_cache_where[] = array('condition' => $like_statement, 'escape' => $escape);
                $this->qb_cache_exists[] = 'where';
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Escape LIKE String
     *
     * Calls the individual driver for platform
     * specific escaping for LIKE conditions
     *
     * @param    string|string[]
     * @return    mixed
     */
    public function escape_like_str($str)
    {
        return $this->escape_str($str, true);
    }

    // --------------------------------------------------------------------

    /**
     * NOT LIKE
     *
     * Generates a NOT LIKE portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param    mixed $field
     * @param    string $match
     * @param    string $side
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function not_like($field, $match = '', $side = 'both', $escape = null)
    {
        return $this->_like($field, $match, 'AND ', $side, 'NOT', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * OR LIKE
     *
     * Generates a %LIKE% portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param    mixed $field
     * @param    string $match
     * @param    string $side
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function or_like($field, $match = '', $side = 'both', $escape = null)
    {
        return $this->_like($field, $match, 'OR ', $side, '', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * OR NOT LIKE
     *
     * Generates a NOT LIKE portion of the query.
     * Separates multiple calls with 'OR'.
     *
     * @param    mixed $field
     * @param    string $match
     * @param    string $side
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function or_not_like($field, $match = '', $side = 'both', $escape = null)
    {
        return $this->_like($field, $match, 'OR ', $side, 'NOT', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * Starts a query group, but ORs the group
     *
     * @return    DbQueryBuilder
     */
    public function or_group_start()
    {
        return $this->group_start('', 'OR ');
    }

    // --------------------------------------------------------------------

    /**
     * Starts a query group.
     *
     * @param    string $not (Internal use only)
     * @param    string $type (Internal use only)
     * @return    DbQueryBuilder
     */
    public function group_start($not = '', $type = 'AND ')
    {
        $type = $this->_group_get_type($type);

        $this->qb_where_group_started = true;
        $prefix = (count($this->qb_where) === 0 && count($this->qb_cache_where) === 0) ? '' : $type;
        $where = array(
            'condition' => $prefix . $not . str_repeat(' ', ++$this->qb_where_group_count) . ' (',
            'escape' => false
        );

        $this->qb_where[] = $where;
        if ($this->qb_caching) {
            $this->qb_cache_where[] = $where;
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Starts a query group, but NOTs the group
     *
     * @return    DbQueryBuilder
     */
    public function not_group_start()
    {
        return $this->group_start('NOT ', 'AND ');
    }

    // --------------------------------------------------------------------

    /**
     * Starts a query group, but OR NOTs the group
     *
     * @return    DbQueryBuilder
     */
    public function or_not_group_start()
    {
        return $this->group_start('NOT ', 'OR ');
    }

    // --------------------------------------------------------------------

    /**
     * Ends a query group
     *
     * @return    DbQueryBuilder
     */
    public function group_end()
    {
        $this->qb_where_group_started = false;
        $where = array(
            'condition' => str_repeat(' ', $this->qb_where_group_count--) . ')',
            'escape' => false
        );

        $this->qb_where[] = $where;
        if ($this->qb_caching) {
            $this->qb_cache_where[] = $where;
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * GROUP BY
     *
     * @param    string $by
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function group_by($by, $escape = null)
    {
        is_bool($escape) OR $escape = $this->_protect_identifiers;

        if (is_string($by)) {
            $by = ($escape === true)
                ? explode(',', $by)
                : array($by);
        }

        foreach ($by as $val) {
            $val = trim($val);

            if ($val !== '') {
                $val = array('field' => $val, 'escape' => $escape);

                $this->qb_groupby[] = $val;
                if ($this->qb_caching === true) {
                    $this->qb_cache_groupby[] = $val;
                    $this->qb_cache_exists[] = 'groupby';
                }
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * HAVING
     *
     * Separates multiple calls with 'AND'.
     *
     * @param    string $key
     * @param    string $value
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function having($key, $value = null, $escape = null)
    {
        return $this->_wh('qb_having', $key, $value, 'AND ', $escape);
    }
    // --------------------------------------------------------------------

    /**
     * OR HAVING
     *
     * Separates multiple calls with 'OR'.
     *
     * @param    string $key
     * @param    string $value
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function or_having($key, $value = null, $escape = null)
    {
        return $this->_wh('qb_having', $key, $value, 'OR ', $escape);
    }
    // --------------------------------------------------------------------

    /**
     * ORDER BY
     *
     * @param    string $orderby
     * @param    string $direction ASC, DESC or RANDOM
     * @param    bool $escape
     * @return    DbQueryBuilder
     */
    public function order_by($orderby, $direction = '', $escape = null)
    {
        $direction = strtoupper(trim($direction));

        if ($direction === 'RANDOM') {
            $direction = '';

            // Do we have a seed value?
            $orderby = ctype_digit((string)$orderby)
                ? sprintf($this->_random_keyword[1], $orderby)
                : $this->_random_keyword[0];
        } elseif (empty($orderby)) {
            return $this;
        } elseif ($direction !== '') {
            $direction = in_array($direction, array('ASC', 'DESC'), true) ? ' ' . $direction : '';
        }

        is_bool($escape) OR $escape = $this->_protect_identifiers;

        if ($escape === false) {
            $qb_orderby[] = array('field' => $orderby, 'direction' => $direction, 'escape' => false);
        } else {
            $qb_orderby = array();
            foreach (explode(',', $orderby) as $field) {
                $qb_orderby[] = ($direction === '' && preg_match('/\s+(ASC|DESC)$/i', rtrim($field), $match,
                        PREG_OFFSET_CAPTURE))
                    ? array(
                        'field' => ltrim(substr($field, 0, $match[0][1])),
                        'direction' => ' ' . $match[1][0],
                        'escape' => true
                    )
                    : array('field' => trim($field), 'direction' => $direction, 'escape' => true);
            }
        }

        $this->qb_orderby = array_merge($this->qb_orderby, $qb_orderby);
        if ($this->qb_caching === true) {
            $this->qb_cache_orderby = array_merge($this->qb_cache_orderby, $qb_orderby);
            $this->qb_cache_exists[] = 'orderby';
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Sets the OFFSET value
     *
     * @param    int $offset OFFSET value
     * @return    DbQueryBuilder
     */
    public function offset($offset)
    {
        empty($offset) OR $this->qb_offset = (int)$offset;
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Get SELECT query string
     *
     * Compiles a SELECT query string and returns the sql.
     *
     * @param    string    the table name to select from (optional)
     * @param    bool    TRUE: resets QB values; FALSE: leave QB values alone
     * @return    string
     */
    public function get_compiled_select($table = '', $reset = true)
    {
        if ($table !== '') {
            $this->_track_aliases($table);
            $this->from($table);
        }

        $select = $this->_compile_select();

        if ($reset === true) {
            $this->_reset_select();
        }

        return $select;
    }

    // --------------------------------------------------------------------

    /**
     * From
     *
     * Generates the FROM portion of the query
     *
     * @param    mixed $from can be a string or array
     * @return    DbQueryBuilder
     */
    public function from($from)
    {
        foreach ((array)$from as $val) {
            if (strpos($val, ',') !== false) {
                foreach (explode(',', $val) as $v) {
                    $v = trim($v);
                    $this->_track_aliases($v);

                    $this->qb_from[] = $v = $this->protect_identifiers($v, true, null, false);

                    if ($this->qb_caching === true) {
                        $this->qb_cache_from[] = $v;
                        $this->qb_cache_exists[] = 'from';
                    }
                }
            } else {
                $val = trim($val);

                // Extract any aliases that might exist. We use this information
                // in the protect_identifiers to know whether to add a table prefix
                $this->_track_aliases($val);

                $this->qb_from[] = $val = $this->protect_identifiers($val, true, null, false);

                if ($this->qb_caching === true) {
                    $this->qb_cache_from[] = $val;
                    $this->qb_cache_exists[] = 'from';
                }
            }
        }
        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Compile the SELECT statement
     *
     * Generates a query string based on which functions were used.
     * Should not be called directly.
     *
     * @param    bool $select_override
     * @return    string
     */
    protected function _compile_select($select_override = false)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        // Write the "select" portion of the query
        if ($select_override !== false) {
            $sql = $select_override;
        } else {
            $sql = (!$this->qb_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';

            if (count($this->qb_select) === 0) {
                $sql .= '*';
            } else {
                // Cycle through the "select" portion of the query and prep each column name.
                // The reason we protect identifiers here rather than in the select() function
                // is because until the user calls the from() function we don't know if there are aliases
                foreach ($this->qb_select as $key => $val) {
                    $no_escape = isset($this->qb_no_escape[$key]) ? $this->qb_no_escape[$key] : null;
                    $this->qb_select[$key] = $this->protect_identifiers($val, false, $no_escape);
                }

                $sql .= implode(', ', $this->qb_select);
            }
        }

        // Write the "FROM" portion of the query
        if (count($this->qb_from) > 0) {
            $sql .= "\nFROM " . $this->_from_tables();
        }

        // Write the "JOIN" portion of the query
        if (count($this->qb_join) > 0) {
            $sql .= "\n" . implode("\n", $this->qb_join);
        }

        $sql .= $this->_compile_wh('qb_where')
            . $this->_compile_group_by()
            . $this->_compile_wh('qb_having')
            . $this->_compile_order_by(); // ORDER BY

        // LIMIT
        if ($this->qb_limit) {
            return $this->_limit($sql . "\n");
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Merge Cache
     *
     * When called, this function merges any cached QB arrays with
     * locally called ones.
     *
     * @return    void
     */
    protected function _merge_cache()
    {
        if (count($this->qb_cache_exists) === 0) {
            return;
        } elseif (in_array('select', $this->qb_cache_exists, true)) {
            $qb_no_escape = $this->qb_cache_no_escape;
        }

        foreach (array_unique($this->qb_cache_exists) as $val) // select, from, etc.
        {
            $qb_variable = 'qb_' . $val;
            $qb_cache_var = 'qb_cache_' . $val;
            $qb_new = $this->$qb_cache_var;

            for ($i = 0, $c = count($this->$qb_variable); $i < $c; $i++) {
                if (!in_array($this->{$qb_variable}[$i], $qb_new, true)) {
                    $qb_new[] = $this->{$qb_variable}[$i];
                    if ($val === 'select') {
                        $qb_no_escape[] = $this->qb_no_escape[$i];
                    }
                }
            }

            $this->$qb_variable = $qb_new;
            if ($val === 'select') {
                $this->qb_no_escape = $qb_no_escape;
            }
        }

        // If we are "protecting identifiers" we need to examine the "from"
        // portion of the query to determine if there are any aliases
        if ($this->_protect_identifiers === true && count($this->qb_cache_from) > 0) {
            $this->_track_aliases($this->qb_from);
        }
    }

    // --------------------------------------------------------------------

    /**
     * FROM tables
     *
     * Groups tables in FROM clauses if needed, so there is no confusion
     * about operator precedence.
     *
     * Note: This is only used (and overridden) by MySQL and CUBRID.
     *
     * @return    string
     */
    protected function _from_tables()
    {
        return implode(', ', $this->qb_from);
    }

    // --------------------------------------------------------------------

    /**
     * Compile WHERE, HAVING statements
     *
     * Escapes identifiers in WHERE and HAVING statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of whether
     * where(), or_where(), having(), or_having are called prior to from(),
     * join() and dbprefix is added only if needed.
     *
     * @param    string $qb_key 'qb_where' or 'qb_having'
     * @return    string    SQL statement
     */
    protected function _compile_wh($qb_key)
    {
        if (count($this->$qb_key) > 0) {
            for ($i = 0, $c = count($this->$qb_key); $i < $c; $i++) {
                // Is this condition already compiled?
                if (is_string($this->{$qb_key}[$i])) {
                    continue;
                } elseif ($this->{$qb_key}[$i]['escape'] === false) {
                    $this->{$qb_key}[$i] = $this->{$qb_key}[$i]['condition'];
                    continue;
                }

                // Split multiple conditions
                $conditions = preg_split(
                    '/((?:^|\s+)AND\s+|(?:^|\s+)OR\s+)/i',
                    $this->{$qb_key}[$i]['condition'],
                    -1,
                    PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
                );

                for ($ci = 0, $cc = count($conditions); $ci < $cc; $ci++) {
                    if (($op = $this->_get_operator($conditions[$ci])) === false
                        OR !preg_match('/^(\(?)(.*)(' . preg_quote($op, '/') . ')\s*(.*(?<!\)))?(\)?)$/i',
                            $conditions[$ci], $matches)
                    ) {
                        continue;
                    }

                    // $matches = array(
                    //	0 => '(test <= foo)',	/* the whole thing */
                    //	1 => '(',		/* optional */
                    //	2 => 'test',		/* the field name */
                    //	3 => ' <= ',		/* $op */
                    //	4 => 'foo',		/* optional, if $op is e.g. 'IS NULL' */
                    //	5 => ')'		/* optional */
                    // );

                    if (!empty($matches[4])) {
                        $this->_is_literal($matches[4]) OR $matches[4] = $this->protect_identifiers(trim($matches[4]));
                        $matches[4] = ' ' . $matches[4];
                    }

                    $conditions[$ci] = $matches[1] . $this->protect_identifiers(trim($matches[2]))
                        . ' ' . trim($matches[3]) . $matches[4] . $matches[5];
                }

                $this->{$qb_key}[$i] = implode('', $conditions);
            }

            return ($qb_key === 'qb_having' ? "\nHAVING " : "\nWHERE ")
                . implode("\n", $this->$qb_key);
        }

        return '';
    }

    // --------------------------------------------------------------------

    /**
     * Is literal
     *
     * Determines if a string represents a literal value or a field name
     *
     * @param    string $str
     * @return    bool
     */
    protected function _is_literal($str)
    {
        $str = trim($str);

        if (empty($str) OR ctype_digit($str) OR (string)(float)$str === $str OR in_array(strtoupper($str),
                array('TRUE', 'FALSE'), true)
        ) {
            return true;
        }

        static $_str;

        if (empty($_str)) {
            $_str = ($this->_escape_char !== '"')
                ? array('"', "'") : array("'");
        }

        return in_array($str[0], $_str, true);
    }

    // --------------------------------------------------------------------

    /**
     * Compile GROUP BY
     *
     * Escapes identifiers in GROUP BY statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of wether
     * group_by() is called prior to from(), join() and dbprefix is added
     * only if needed.
     *
     * @return    string    SQL statement
     */
    protected function _compile_group_by()
    {
        if (count($this->qb_groupby) > 0) {
            for ($i = 0, $c = count($this->qb_groupby); $i < $c; $i++) {
                // Is it already compiled?
                if (is_string($this->qb_groupby[$i])) {
                    continue;
                }

                $this->qb_groupby[$i] = ($this->qb_groupby[$i]['escape'] === false OR $this->_is_literal($this->qb_groupby[$i]['field']))
                    ? $this->qb_groupby[$i]['field']
                    : $this->protect_identifiers($this->qb_groupby[$i]['field']);
            }

            return "\nGROUP BY " . implode(', ', $this->qb_groupby);
        }

        return '';
    }

    // --------------------------------------------------------------------

    /**
     * Compile ORDER BY
     *
     * Escapes identifiers in ORDER BY statements at execution time.
     *
     * Required so that aliases are tracked properly, regardless of wether
     * order_by() is called prior to from(), join() and dbprefix is added
     * only if needed.
     *
     * @return    string    SQL statement
     */
    protected function _compile_order_by()
    {
        if (is_array($this->qb_orderby) && count($this->qb_orderby) > 0) {
            for ($i = 0, $c = count($this->qb_orderby); $i < $c; $i++) {
                if ($this->qb_orderby[$i]['escape'] !== false && !$this->_is_literal($this->qb_orderby[$i]['field'])) {
                    $this->qb_orderby[$i]['field'] = $this->protect_identifiers($this->qb_orderby[$i]['field']);
                }

                $this->qb_orderby[$i] = $this->qb_orderby[$i]['field'] . $this->qb_orderby[$i]['direction'];
            }

            return $this->qb_orderby = "\nORDER BY " . implode(', ', $this->qb_orderby);
        } elseif (is_string($this->qb_orderby)) {
            return $this->qb_orderby;
        }

        return '';
    }

    // --------------------------------------------------------------------

    /**
     * LIMIT string
     *
     * Generates a platform-specific LIMIT clause.
     *
     * @param    string $sql SQL Query
     * @return    string
     */
    protected function _limit($sql)
    {
        return $sql . ' LIMIT ' . ($this->qb_offset ? $this->qb_offset . ', ' : '') . $this->qb_limit;
    }

    // --------------------------------------------------------------------

    /**
     * Resets the query builder values.  Called by the get() function
     *
     * @return    void
     */
    protected function _reset_select()
    {
        $this->_reset_run(array(
            'qb_select' => array(),
            'qb_from' => array(),
            'qb_join' => array(),
            'qb_where' => array(),
            'qb_groupby' => array(),
            'qb_having' => array(),
            'qb_orderby' => array(),
            'qb_aliased_tables' => array(),
            'qb_no_escape' => array(),
            'qb_distinct' => false,
            'qb_limit' => false,
            'qb_offset' => false
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Resets the query builder values.  Called by the get() function
     *
     * @param    array    An array of fields to reset
     * @return    void
     */
    protected function _reset_run($qb_reset_items)
    {
        foreach ($qb_reset_items as $item => $default_value) {
            $this->$item = $default_value;
        }
    }

    // --------------------------------------------------------------------

    /**
     * Get INSERT query string
     *
     * Compiles an insert query and returns the sql
     *
     * @param    string    the table to insert into
     * @param    bool    TRUE: reset QB values; FALSE: leave QB values alone
     * @return    string
     */
    public function get_compiled_insert($table = '', $reset = true)
    {
        if ($this->_validate_insert($table) === false) {
            return false;
        }

        $sql = $this->_insert(
            $this->protect_identifiers(
                $this->qb_from[0], true, null, false
            ),
            array_keys($this->qb_set),
            array_values($this->qb_set)
        );

        if ($reset === true) {
            $this->_reset_write();
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Validate Insert
     *
     * This method is used by both insert() and get_compiled_insert() to
     * validate that the there data is actually being set and that table
     * has been chosen to be inserted into.
     *
     * @param    string    the table to insert data into
     * @return    string
     */
    protected function _validate_insert($table = '')
    {
        if (count($this->qb_set) === 0) {
            throw new SwooleException('db_must_use_set');
        }

        if ($table !== '') {
            $this->qb_from[0] = $table;
        } elseif (!isset($this->qb_from[0])) {
            throw new SwooleException('db_must_set_table');
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Insert statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @param    string    the table name
     * @param    array    the insert keys
     * @param    array    the insert values
     * @return    string
     */
    protected function _insert($table, $keys, $values)
    {
        return 'INSERT INTO ' . $table . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ')';
    }

    // --------------------------------------------------------------------

    /**
     * Resets the query builder "write" values.
     *
     * Called by the insert() update() insert_batch() update_batch() and delete() functions
     *
     * @return    void
     */
    protected function _reset_write()
    {
        $this->_reset_run(array(
            'qb_set' => array(),
            'qb_from' => array(),
            'qb_join' => array(),
            'qb_where' => array(),
            'qb_orderby' => array(),
            'qb_keys' => array(),
            'qb_limit' => false
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Insert
     *
     * Compiles an insert string and runs the query
     *
     * @param    string    the table to insert data into
     * @param    array    an associative array of insert values
     * @param    bool $escape Whether to escape values and identifiers
     * @return    bool    TRUE on success, FALSE on failure
     */
    public function insert($callback, $table = '', $set = null, $escape = null)
    {
        if ($set !== null) {
            $this->set($set, '', $escape);
        }

        if ($this->_validate_insert($table) === false) {
            return false;
        }

        $sql = $this->_insert(
            $this->protect_identifiers(
                $this->qb_from[0], true, $escape, false
            ),
            array_keys($this->qb_set),
            array_values($this->qb_set)
        );

        $this->_reset_write();
        return $this->query($sql, $callback);
    }

    // --------------------------------------------------------------------

    /**
     * The "set" function.
     *
     * Allows key/value pairs to be set for inserting or updating
     *
     * @param    mixed
     * @param    string
     * @param    bool
     * @return    DbQueryBuilder
     */
    public function set($key, $value = '', $escape = null)
    {
        $key = $this->_object_to_array($key);

        if (!is_array($key)) {
            $key = array($key => $value);
        }

        is_bool($escape) OR $escape = $this->_protect_identifiers;

        foreach ($key as $k => $v) {
            $this->qb_set[$this->protect_identifiers($k, false, $escape)] = ($escape)
                ? $this->escape($v) : $v;
        }

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param    object
     * @return    array
     */
    protected function _object_to_array($object)
    {
        if (!is_object($object)) {
            return $object;
        }

        $array = array();
        foreach (get_object_vars($object) as $key => $val) {
            // There are some built in keys we need to ignore for this conversion
            if (!is_object($val) && !is_array($val) && $key !== '_parent_name') {
                $array[$key] = $val;
            }
        }

        return $array;
    }

    // --------------------------------------------------------------------

    /**
     * Execute the query
     *
     * Accepts an SQL string as input and returns a result object upon
     * successful execution of a "read" type query. Returns boolean TRUE
     * upon successful execution of a "write" type query. Returns boolean
     * FALSE upon failure, and if the $db_debug variable is set to TRUE
     * will raise an error.
     *
     * @param    string $sql
     * @param    array $binds = FALSE        An array of binding data
     * @param    bool $return_object = NULL
     * @return    mixed
     */
    public function query($sql, $callback)
    {
        if ($sql === '') {
            throw new SwooleException('error', 'Invalid query: ' . $sql);
        }

        // Verify table prefix and replace if necessary
        if ($this->dbprefix !== '' && $this->swap_pre !== '' && $this->dbprefix !== $this->swap_pre) {
            $sql = preg_replace('/(\W)' . $this->swap_pre . '(\S+?)/', '\\1' . $this->dbprefix . '\\2', $sql);
        }
        $this->dbDrive->query($sql, $callback);
    }

    // --------------------------------------------------------------------

    /**
     * Replace
     *
     * Compiles an replace into string and runs the query
     *
     * @param    string    the table to replace data into
     * @param    array    an associative array of insert values
     * @return    bool    TRUE on success, FALSE on failure
     */
    public function replace($callback, $table = '', $set = null)
    {
        if ($set !== null) {
            $this->set($set);
        }

        if (count($this->qb_set) === 0) {
            throw new SwooleException('db_must_use_set');
        }

        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                throw new SwooleException('db_must_set_table');
            }

            $table = $this->qb_from[0];
        }

        $sql = $this->_replace($this->protect_identifiers($table, true, null, false), array_keys($this->qb_set),
            array_values($this->qb_set));

        $this->_reset_write();
        return $this->query($sql, $callback);
    }

    // --------------------------------------------------------------------

    /**
     * Replace statement
     *
     * Generates a platform-specific replace string from the supplied data
     *
     * @param    string    the table name
     * @param    array    the insert keys
     * @param    array    the insert values
     * @return    string
     */
    protected function _replace($table, $keys, $values)
    {
        return 'REPLACE INTO ' . $table . ' (' . implode(', ', $keys) . ') VALUES (' . implode(', ', $values) . ')';
    }

    // --------------------------------------------------------------------

    /**
     * Get UPDATE query string
     *
     * Compiles an update query and returns the sql
     *
     * @param    string    the table to update
     * @param    bool    TRUE: reset QB values; FALSE: leave QB values alone
     * @return    string
     */
    public function get_compiled_update($table = '', $reset = true)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        if ($this->_validate_update($table) === false) {
            return false;
        }

        $sql = $this->_update($this->qb_from[0], $this->qb_set);

        if ($reset === true) {
            $this->_reset_write();
        }

        return $sql;
    }

    // --------------------------------------------------------------------

    /**
     * Validate Update
     *
     * This method is used by both update() and get_compiled_update() to
     * validate that data is actually being set and that a table has been
     * chosen to be update.
     *
     * @param    string    the table to update data on
     * @return    bool
     */
    protected function _validate_update($table)
    {
        if (count($this->qb_set) === 0) {
            throw new SwooleException('db_must_use_set');
        }

        if ($table !== '') {
            $this->qb_from = array($this->protect_identifiers($table, true, null, false));
        } elseif (!isset($this->qb_from[0])) {
            throw new SwooleException('db_must_set_table');
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @param    string    the table name
     * @param    array    the update data
     * @return    string
     */
    protected function _update($table, $values)
    {
        foreach ($values as $key => $val) {
            $valstr[] = $key . ' = ' . $val;
        }

        return 'UPDATE ' . $table . ' SET ' . implode(', ', $valstr)
            . $this->_compile_wh('qb_where')
            . $this->_compile_order_by()
            . ($this->qb_limit ? ' LIMIT ' . $this->qb_limit : '');
    }

    // --------------------------------------------------------------------

    /**
     * UPDATE
     *
     * Compiles an update string and runs the query.
     *
     * @param    string $table
     * @param    array $set An associative array of update values
     * @param    mixed $where
     * @param    int $limit
     * @return    bool    TRUE on success, FALSE on failure
     */
    public function update($callback, $table = '', $set = null, $where = null, $limit = null)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        if ($set !== null) {
            $this->set($set);
        }

        if ($this->_validate_update($table) === false) {
            return false;
        }

        if ($where !== null) {
            $this->where($where);
        }

        if (!empty($limit)) {
            $this->limit($limit);
        }

        $sql = $this->_update($this->qb_from[0], $this->qb_set);
        $this->_reset_write();
        return $this->query($sql, $callback);
    }

    // --------------------------------------------------------------------

    /**
     * WHERE
     *
     * Generates the WHERE portion of the query.
     * Separates multiple calls with 'AND'.
     *
     * @param    mixed
     * @param    mixed
     * @param    bool
     * @return    DbQueryBuilder
     */
    public function where($key, $value = null, $escape = null)
    {
        return $this->_wh('qb_where', $key, $value, 'AND ', $escape);
    }

    // --------------------------------------------------------------------

    /**
     * LIMIT
     *
     * @param    int $value LIMIT value
     * @param    int $offset OFFSET value
     * @return    DbQueryBuilder
     */
    public function limit($value, $offset = 0)
    {
        is_null($value) OR $this->qb_limit = (int)$value;
        empty($offset) OR $this->qb_offset = (int)$offset;

        return $this;
    }

    // --------------------------------------------------------------------

    /**
     * Empty Table
     *
     * Compiles a delete string and runs "DELETE FROM table"
     *
     * @param    string    the table to empty
     * @return    bool    TRUE on success, FALSE on failure
     */
    public function empty_table($callback, $table = '')
    {
        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                throw new SwooleException('db_must_set_table');
            }

            $table = $this->qb_from[0];
        } else {
            $table = $this->protect_identifiers($table, true, null, false);
        }

        $sql = $this->_delete($table);
        $this->_reset_write();
        return $this->query($sql, $callback);
    }

    // --------------------------------------------------------------------

    /**
     * Delete statement
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @param    string    the table name
     * @return    string
     */
    protected function _delete($table)
    {
        return 'DELETE FROM ' . $table . $this->_compile_wh('qb_where')
            . ($this->qb_limit ? ' LIMIT ' . $this->qb_limit : '');
    }

    // --------------------------------------------------------------------

    /**
     * Truncate
     *
     * Compiles a truncate string and runs the query
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @param    string    the table to truncate
     * @return    bool    TRUE on success, FALSE on failure
     */
    public function truncate($callback, $table = '')
    {
        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                throw new SwooleException('db_must_set_table');
            }

            $table = $this->qb_from[0];
        } else {
            $table = $this->protect_identifiers($table, true, null, false);
        }

        $sql = $this->_truncate($table);
        $this->_reset_write();
        return $this->query($sql, $callback);
    }
    // --------------------------------------------------------------------

    /**
     * Truncate statement
     *
     * Generates a platform-specific truncate string from the supplied data
     *
     * If the database does not support the truncate() command,
     * then this method maps to 'DELETE FROM table'
     *
     * @param    string    the table name
     * @return    string
     */
    protected function _truncate($table)
    {
        return 'TRUNCATE ' . $table;
    }
    // --------------------------------------------------------------------

    /**
     * Get DELETE query string
     *
     * Compiles a delete query string and returns the sql
     *
     * @param    string    the table to delete from
     * @param    bool    TRUE: reset QB values; FALSE: leave QB values alone
     * @return    string
     */
    public function get_compiled_delete($table = '', $reset = true)
    {
        $this->return_delete_sql = true;
        $sql = $this->delete($table, '', null, $reset);
        $this->return_delete_sql = false;
        return $sql;
    }
    // --------------------------------------------------------------------

    /**
     * Delete
     *
     * Compiles a delete string and runs the query
     *
     * @param    mixed    the table(s) to delete from. String or array
     * @param    mixed    the where clause
     * @param    mixed    the limit clause
     * @param    bool
     * @return    mixed
     */
    public function delete($callback, $table = '', $where = '', $limit = null, $reset_data = true)
    {
        // Combine any cached components with the current statements
        $this->_merge_cache();

        if ($table === '') {
            if (!isset($this->qb_from[0])) {
                throw new SwooleException('db_must_set_table');
            }

            $table = $this->qb_from[0];
        } elseif (is_array($table)) {
            empty($where) && $reset_data = false;

            foreach ($table as $single_table) {
                $this->delete($single_table, $where, $limit, $reset_data);
            }

            return;
        } else {
            $table = $this->protect_identifiers($table, true, null, false);
        }

        if ($where !== '') {
            $this->where($where);
        }

        if (!empty($limit)) {
            $this->limit($limit);
        }

        if (count($this->qb_where) === 0) {
            throw new SwooleException('db_del_must_use_where');
        }

        $sql = $this->_delete($table);
        if ($reset_data) {
            $this->_reset_write();
        }

        return ($this->return_delete_sql === true) ? $sql : $this->query($sql, $callback);
    }
    // --------------------------------------------------------------------

    /**
     * DB Prefix
     *
     * Prepends a database prefix if one exists in configuration
     *
     * @param    string    the table
     * @return    string
     */
    public function dbprefix($table = '')
    {
        if ($table === '') {
            throw new SwooleException('db_table_name_required');
        }

        return $this->dbprefix . $table;
    }

    /**
     * Set DB Prefix
     *
     * Set's the DB Prefix to something new without needing to reconnect
     *
     * @param    string    the prefix
     * @return    string
     */
    public function set_dbprefix($prefix = '')
    {
        return $this->dbprefix = $prefix;
    }
    // --------------------------------------------------------------------

    /**
     * Start Cache
     *
     * Starts QB caching
     *
     * @return    DbQueryBuilder
     */
    public function start_cache()
    {
        $this->qb_caching = true;
        return $this;
    }
    // --------------------------------------------------------------------

    /**
     * Stop Cache
     *
     * Stops QB caching
     *
     * @return    DbQueryBuilder
     */
    public function stop_cache()
    {
        $this->qb_caching = false;
        return $this;
    }

    /**
     * Flush Cache
     *
     * Empties the QB cache
     *
     * @return    DbQueryBuilder
     */
    public function flush_cache()
    {
        $this->_reset_run(array(
            'qb_cache_select' => array(),
            'qb_cache_from' => array(),
            'qb_cache_join' => array(),
            'qb_cache_where' => array(),
            'qb_cache_groupby' => array(),
            'qb_cache_having' => array(),
            'qb_cache_orderby' => array(),
            'qb_cache_set' => array(),
            'qb_cache_exists' => array(),
            'qb_cache_no_escape' => array()
        ));

        return $this;
    }
    // --------------------------------------------------------------------

    /**
     * Reset Query Builder values.
     *
     * Publicly-visible method to reset the QB values.
     *
     * @return    DbQueryBuilder
     */
    public function reset_query()
    {
        $this->_reset_select();
        $this->_reset_write();
        return $this;
    }
    // --------------------------------------------------------------------

    /**
     * Get
     *
     * Compiles the select statement based on the other functions called
     * and runs the query
     *
     * @param    string    the table
     * @param    string    the limit clause
     * @param    string    the offset clause
     */
    public function get($callback, $table = '', $limit = null, $offset = null)
    {
        if ($table !== '') {
            $this->_track_aliases($table);
            $this->from($table);
        }

        if (!empty($limit)) {
            $this->limit($limit, $offset);
        }
        $this->query($this->_compile_select(), $callback);
        $this->_reset_select();
        return;
    }
    // --------------------------------------------------------------------

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param    object
     * @return    array
     */
    protected function _object_to_array_batch($object)
    {
        if (!is_object($object)) {
            return $object;
        }

        $array = array();
        $out = get_object_vars($object);
        $fields = array_keys($out);

        foreach ($fields as $val) {
            // There are some built in keys we need to ignore for this conversion
            if ($val !== '_parent_name') {
                $i = 0;
                foreach ($out[$val] as $data) {
                    $array[$i++][$val] = $data;
                }
            }
        }

        return $array;
    }
}