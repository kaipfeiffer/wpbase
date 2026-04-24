<?php

namespace KaiPfeiffer\WPBase;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Static Class for Database-Access via wpdb
 *
 * @since      1.0.0
 * @package    WPBase
 * @author     Kai Pfeiffer <kp@idevo.de>
 */

abstract class ModelAbstract
{

    /**
     * VARIABLES
     */

    /**
     * $chunk_size
     * 
     * number of entries per chunk during import
     * 
     * @var integer
     */
    protected static $chunk_size = 30;

    /**
     * $columns
     * associative array with column names and their prepare-placeholders
     * 
     * @var array
     */
    protected static $columns;

    /**
     * $error
     * error id
     * 
     * @var integer
     */
    protected static $error;


    /**
     * $error_message
     * 
     * message to error
     * 
     * @var string
     */
    protected static $error_message;

    /**
     * $import_file
     * 
     * the name of the file to import
     * 
     * @var string
     */
    protected static $import_file;

    /**
     * $page_size
     * 
     * the number of entries to deliver
     * 
     * @var integer
     */
    protected static $page_size = 30;

    /**
     * $prefix
     * the prefix of this plugin
     * 
     * @var string
     */
    protected static $prefix = 'rideshare_';


    /**
     * $primary
     * the name of the primary index,default is "id"
     * 
     * @var string
     */
    protected static $primary = 'id';


    /**
     * $table_name
     * 
     * the name of the table without wp-prefix
     * 
     * @var string
     */
    protected static $table_name;


    /**
     * $chunk_list
     * 
     * list of chunks during import
     * 
     * @var array
     */
    protected static $chunk_list;


    /**
     * PRIVATE METHODS
     */


    /**
     *  db_delta
     * 
     * creates the table
     */
    abstract static function db_delta();


    /**
     * get_labels
     * 
     * @return: array
     */
    abstract static function get_labels(): array;


    /**
     * get_input_types
     * 
     * @return: array
     */
    abstract static function get_input_types(): array;

    
    /**
     * get_plugin_dir_path
     * 
     * @return string    the path to the plugin directory
     */
	abstract protected static function get_plugin_dir_path(): string;


    /**
     * check
     * 
     * checks if the submitted data columns are allowed
     * the messages should be stored in the value of the associated column
     * 
     * @param   array
     * @return  array
     * @since   0.1.1
     */
    public static function check(array $data): array
    {
        $columns    = static::get_input_types();
        return array_diff_key($columns, $data);
    }

    /**
     * escape_placeholder
     * 
     * escape the placholder for prepare-statements with double "%"
     * 
     * @param   string  the placeholder
     * @return  string  escaped placeholder
     */
    protected static function escape_placeholder(string $placeholder): string
    {
        $placeholder = str_replace('%', '%%', $placeholder);
        return $placeholder;
    }


    /**
     * get_columns
     * 
     * get the columns with their placeholders
     * 
     * @return array    columns with their placeholders
     */
    static function get_columns(): array
    {
        return static::$columns;
    }


    /**
     * get_db
     * 
     * @return wpdb   the wpdb instance for database access
     */
    protected static function get_db()
    {
        global $wpdb;
        return $wpdb;
    }


    /**
     * get_defaults
     * 
     * get default values to the table columns
     * 
     * @return array    default values
     */
    protected static function get_defaults(): array
    {
        return array();
    }

    
    /**
     * get primary_key
     * 
     * 
     * 
     * @param   string
     * @return  array|null
     * @since   1.0.0
     */
    public static function get_primary_key(string $table_name = null): ?array
    {
        return array('column' => static::$primary, 'placeholder' => static::$columns[static::$primary]);
    }


    /**
     * get row count
     * 
     * get the number of rows in the table
     * 
     * @return  int|null
     * @since   1.0.0
     */
    public static function get_row_cnt(?array $query = null): ?int
    {
        $table_name     = static::get_table_name();
        $primary_key    = static::get_primary_key();

        if ($query) {
            $queries    = array();
            $values     = array();

            if (array_is_list($query)) {
                foreach ($query as $details) {
                    $detail = static::parse_query($details);
                    if ($detail) {
                        array_push($queries, $detail);
                        $value = static::parse_value($details);
                        array_push($values, $value);
                    }
                }
            } elseif (is_array($query)) {
                $detail = static::parse_query($query);
                if ($detail) {
                    array_push($queries, $detail);
                    $value = static::parse_value($query);
                    array_push($values, $value);
                }
            }

            $sql = sprintf(
                'SELECT COUNT(`%1$s`) FROM `%2$s` WHERE %3$s;',
                $primary_key['column'],
                $table_name,
                implode(' OR ', $queries)
            );
            $sql    = static::get_db()->prepare($sql, ...$values);
        } else {
            $sql = sprintf('SELECT COUNT(`%1$s`) FROM `%2$s`;', $primary_key['column'], $table_name);
        }

        $row_cnt    = static::get_db()->get_var($sql);
        return $row_cnt;
    }


    /**
     * get_table_name
     * 
     * @param   string  => optional der Tabellenname, der mit Prefixes versehenwerden soll
     *                              ansonsten wird der Name aus static::$table_name genutzt
     * @return  string => Tabellenname mit dem Prefix der Wordpress-Installation 
     * 
     * liefert den Tabellennamen mit dem Prefix der Wordpress-Installation zurück
     */
    public static function get_table_name(?string $table_name = null): string
    {
        global $wpdb;

        $table_name = $table_name ? $table_name : static::$table_name;
        return $wpdb->prefix . static::$prefix . $table_name;
    }


    /**
     * get_update_defaults
     * 
     * get default update values to the table columns
     * 
     * @return array    default update values
     */
    protected static function get_update_defaults(): array
    {
        return array();
    }


    /**
     * parse_query
     * 
     * @param   array   query details with keys "column", "comparator" and "value"
     * @return  string|null   the parsed query part for the where clause or null if the column is not supported
     */
    protected static function parse_query(array $query): ?string
    {
        $columns = static::get_columns();

        $column     = $query['column'];
        $comparator = $query['comparator'] ?? '=';

        // error_log(__CLASS__ . '->' . __LINE__ . '->' . $columns[$column] . '->' . print_r($query, 1));
        if ($columns[$column] ?? null) {
            $placeholder = $columns[$column];
            switch (strtolower($comparator)) {
                case 'like%':
                case '%like':
                case '%like%':
                    return sprintf(
                        '`%s` LIKE %s',
                        $column,
                        $placeholder
                    );
                default:
                    return sprintf(
                        '`%s` %s %s',
                        $column,
                        $comparator,
                        $placeholder
                    );
            }
        }
        return null;
    }


    /**
     *  parse_value
     * 
     *  parse the value for the query based on the comparator
     * 
     * @param   array   query details with keys "column", "comparator" and "value"
     * @return  mixed   the parsed value for the query
     */
    protected static function parse_value(array $query): mixed
    {
        // error_log(__CLASS__ . '->' . __LINE__ . '->' . print_r($query, 1));
        switch (strtolower($query['comparator'] ?? '')) {
            case '%like':
                $value = '%' . static::get_db()->esc_like($query['value']);
                break;
            case '%like%':
                $value = '%' . static::get_db()->esc_like($query['value']) . '%';
                break;
            case 'like%':
                $value = static::get_db()->esc_like($query['value']) . '%';
                break;
            default:
                $value = $query['value'];
        }
        return $value;
    }


    /**
     * sanitize_column
     * 
     * sanitize values
     * 
     * @param   string 
     * @param   string
     * @return  mixed
     */
    protected static function sanitize_column(string $key, string $value)
    {
        $columns    = array_merge(static::$columns, (static::get_input_types()));
        if (isset($columns[$key])) {
            switch ($columns[$key]) {
                case  'date-input':
                    $value = preg_replace('/[^\d-]/', '', $value);
                    $value = preg_match('/\d{4}-\d{2}-\d{2}/', $value) ? $value : '';
                    return $value;
                case 'textarea':
                    return sanitize_textarea_field($value);
                case 'wp_select':
                case 'number':
                case '%d':
                    return intval($value);
                case 'decimal':
                    $value = preg_match('/\,\d+\./', $value) ? str_replace(',', '', $value) : $value;
                    $value = preg_match('/\.\d+\,/', $value) ? str_replace('.', '', $value) : $value;
                    $value = str_replace(',', '.', $value);
                case '%f':
                    return floatval($value);
                case '%s':
                default:
                    return sanitize_text_field($value);
            }
        }
        return $value;
    }


    /**
     * sanitize
     * 
     * sanitize values
     * 
     * @param   array
     * @return  array
     */
    static function sanitize(array $data): array
    {
        $sanitized = array();

        foreach ($data as $key => $value) {
            if (isset(static::$columns[$key])) {
                $sanitized[$key] = static::sanitize_column($key, $value);
            }
        }
        return $sanitized;
    }


    /**
     * set_values
     * 
     * set the values of the table columns
     * 
     * @param   array   columns with the values to set
     * @return  array   list aof placeholders
     */
    protected static function set_values(array $columns): array
    {
        $placeholders = $chunk_list = array();

        //
        //

        foreach ($columns as $key => $value) {
            if (array_key_exists($key, static::$columns)) {
                //
                array_push($placeholders, static::$columns[$key]);
                // array_push($placeholders, static::escape_placeholder(static::$columns[$key]));
                $chunk_list[$key] = sprintf(static::$columns[$key], $value);
            }
        }

        return array($chunk_list, $placeholders);
    }


    /**
     *  table_exists
     * 
     * check if table exists
     * 
     * @return  bool    true id table exists
     */
    protected static function table_exists(): bool
    {
        global $wpdb;

        $sql = sprintf(
            'SELECT COUNT(TABLE_NAME)
        FROM 
           information_schema.TABLES 
        WHERE 
           TABLE_SCHEMA LIKE "%1$s" AND 
            TABLE_TYPE LIKE "BASE TABLE" AND
            TABLE_NAME = "%2$s";',
            $wpdb->dbname,
            static::get_table_name()
        );

        return (bool)$wpdb->get_var($sql);
    }


    /**
     * PUBLIC METHODS
     */

    /**
     * create_multi
     * 
     * add multiple new rows to the table
     * 
     * @param   array       array with rows to insert
     * @return  int|null    if successful, the number of inserted records
     */
    public static function create_multi(array $rows): ?int
    {
        global $wpdb;

        $inserts    = array();
        $values     = array();

        // Die Felder in einem Rutsch in die Datenbank eintragen
        foreach ($rows as $row) {
            list($chunklist, $placeholders) = static::set_values($row);
            array_push($inserts, '(' . implode(',', $placeholders) . ')');
            array_push($values, ...array_values($chunklist));
        }

        $sql    = 'INSERT INTO `' . static::get_table_name() . '` (`' . implode('`,`', array_keys($chunklist)) . '`) VALUES ' . implode(',', $inserts) . ';';

        $result = $wpdb->query($wpdb->prepare($sql, $values));

        return $result;
    }


    /**
     * create
     * 
     * add a new row to the table
     * 
     * @param   array       associative array with key => value pairs for insertion
     * @return  array|null  if successful, the stored data row
     */
    public static function create($columns): ?array
    {
        global $wpdb;

        $placeholders   = $chunk_list   = array();
        $row            = null;

        // merge default columns with updated values
        $columns = array_merge(static::get_defaults(), $columns);

        // set only supported keys and retrieve prepare-placeholders
        list($chunk_list, $placeholders)   = static::set_values($columns);


        $result = $wpdb->insert(static::get_table_name(), $chunk_list, $placeholders);
        if ($result) {

            $row = static::read($wpdb->insert_id,);
        }
        return $row;
    }


    /**
     * get_primary
     * 
     * get the primary-key of the model
     * 
     * @return string     Primary-Key
     */
    public static function get_primary(): string
    {
        return static::$primary;
    }


    /**
     * delete
     * 
     * get the row to committed ID
     * 
     * @param integer|array primary id or where columns for selection
     * @return int|bool     the number of affected rows or false
     */
    public static function delete($where = null)
    {
        global $wpdb;

        // if where is an integer or array
        if ($where) {
            $placeholders = $chunk_list = array();

            // if an integer is submitted
            if (is_int($where)) {
                $id     = $where;
                $where  = array();
                array_push($placeholders, static::$columns[static::$primary]);
                $where[static::$primary] = sprintf(
                    static::$columns[static::$primary],
                    $id
                );
            }
            // an array was submitted
            else {
                // set only supported keys and retrieve prepare-placeholders
                list($chunk_list, $placeholders)   = static::set_values($where);
                $where          = $chunk_list;
            }
            $result = $wpdb->delete(static::get_table_name(), $where, $placeholders);
        }
    }


    /**
     * get
     * 
     * gets rows
     * 
     * @param integer         page
     * @param integer         page_size
     * @return array|null     the fetched data row
     */
    public static function get(?int $page = 0, ?int $page_size = null): array
    {
        global $wpdb;

        $page_size = $page_size ? $page_size : static::$page_size;

        $sql = sprintf(
            'SELECT
                    `%1$s`
                FROM
                    `%2$s`
                WHERE
                    1
                LIMIT
                    %3$d,%4$d;',
            implode('`,`', array_keys(static::$columns)),
            static::get_table_name(),
            $page * $page_size,
            $page_size
        );

        $result = $wpdb->get_results($sql, ARRAY_A);

        return $result;
    }


    /**
     * import
     * 
     */
    protected static function import()
    {
        global $wpdb;

        $csv_file_path = static::get_plugin_dir_path() . 'data/imports/' . static::$import_file;


        if (file_exists($csv_file_path)) {
            $file       = fopen($csv_file_path, "r");
            $table      = static::get_table_name();
            $columns    = null;
            $chunk_list = [];
            $chunk      = [];

            // Anzahl der Zeilen pro Insert
            $chunk_size = $left = static::$chunk_size;

            while (($row = fgetcsv($file)) !== FALSE) {
                if (!$columns) {
                    $columns = '(`' . implode('`,`', $row) . '`)';
                } else {
                    // Wenn Chunk vollständig ist
                    if (!$left) {
                        $left = $chunk_size;
                        array_push($chunk_list, $chunk);
                        $chunk = [];
                    }
                    // Zeile hinzufügen
                    array_push($chunk, '("' . implode('","', $row) . '")');
                    $left--;
                }
            }

            // Wenn sich noch Zeilen im chunk befinden
            if ($left) {
                array_push($chunk_list, $chunk);
            }

            // Alle chunks durchgehen
            for ($i = 0; $i < count($chunk_list); $i++) {
                $sql = sprintf(
                    'INSERT INTO `%1$s` %2$s VALUES %3$s;',
                    $table,
                    $columns,
                    implode(',', $chunk_list[$i])
                );
                $wpdb->query($sql);
            }
        }
    }


    /**
     * raw_sql
     * 
     * get result of a raw sql-statement
     * 
     * @param string                    statement
     * @param array|null                values
     * @return array|object|null|void   the fetched data row
     */
    public static function raw_sql($stmt, $params = null)
    {
        global $wpdb;
        if ($params) {
            $stmt   = $wpdb->prepare($stmt, array_values($params));
        }

        $result = $wpdb->get_results($stmt, ARRAY_A);

        return $result;
    }


    /**
     * read
     * 
     * get the row to committed ID
     * 
     * @param integer                   ID of the required row
     * @param bool                      or-operator for where clause
     * @param integer                   page
     * @param integer|null              page_count
     * @return array|object|null|void   the fetched data row
     */
    public static function read($where, $or = false, $page = 0, $page_count = null)
    {
        global $wpdb;

        $where_stmts    = array();
        $operator       = $or ? ' OR ' : ' AND ';
        $pagination     = null;
        $placeholders   = $chunk_list   = array();
        $id             = null;

        // an array was submitted
        if (is_array($where)) {
            // set only supported keys and retrieve prepare-placeholders
            list($chunk_list, $placeholders)   = static::set_values($where);
            $where          = $chunk_list;

            if ($page) {
                $pagination     = sprintf(
                    'LIMIT 
                        %1$d,%2$d',
                    $page * $page_count,
                    $page_count
                );
            }
        }
        // if an integer is submitted
        elseif (is_int($where)) {
            $id     = $where;
            $where  = array();

            // set placeholder
            array_push($placeholders, static::$columns[static::$primary]);

            $where[static::$primary] = sprintf(
                static::$columns[static::$primary],
                $id
            );
        } else {
            $where_stmts    = array('1');
            $where          = array();
        }

        foreach (array_keys($where) as $index => $key) {
            array_push(
                $where_stmts,
                sprintf(
                    '`%1$s` = %2$s ' . "\n",
                    $key,
                    $placeholders[$index]
                )
            );
        }

        $sql = sprintf(
            'SELECT
                    *
                FROM
                    `%1$s`
                WHERE
                    %2$s
                    %3$s;',
            static::get_table_name(),
            implode($operator, $where_stmts),
            $pagination
        );

        // if a single row ist queried
        if ($id) {
            $result = $wpdb->get_row($wpdb->prepare($sql, array_values($where)), ARRAY_A);
        } else {
            $result = $wpdb->get_results($wpdb->prepare($sql, array_values($where)), ARRAY_A);
        }
        return $result;
    }

    /**
     *  setup_table
     * 
     * setup the table
     */
    public static function setup_table()
    {
        $table_exists   = static::table_exists();

        // create the table
        static::db_delta();
        // if the table doesn't exist
        if (!$table_exists) {
            // insert data
            if (static::$import_file) {
                // static::import();
            }
            // create indices
            // static::create_indices();
        }
    }


    /**
     * update
     * 
     * updates a new row of the table
     * 
     * @param   array       associative array with key => value pairs for update
     * @return  array|null  if successful, the stored data row
     */
    public static function update($columns, $where = null)
    {
        global $wpdb;

        $row            = null;

        if ($where || array_key_exists(static::$primary, $columns)) {
            $placeholders   = array();

            // if no where values were submitted
            if (!$where) {
                // create where with primary key
                $where[static::$primary] = sprintf(
                    static::$columns[static::$primary],
                    $columns[static::$primary]
                );
            }

            // merge default columns with updated values
            $columns = array_merge(static::get_update_defaults(), $columns);

            list($chunk_list, $placeholders)   = static::set_values($columns);

            $result = $wpdb->update(static::get_table_name(), $chunk_list, $where, $placeholders);

            // if update was successful
            if ($result) {
                // $row = static::read($chunk_list);
                $row = static::read(array(static::$primary => $columns[static::$primary]));
            }
        } else {
            // throw error
        }
        return $row;
    }
}
