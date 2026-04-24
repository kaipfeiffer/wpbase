<?php

namespace KaiPfeiffer\WPBase\Abstracts;

if (!defined('WPINC')) {
    die;
}

/**
 * Abstract Static Class for Database-Access via wpdb
 *
 * @author  Kai Pfeiffer <kp@loworx.com>
 * @package rideshare
 * @since   0.1.0 
 */

abstract class ControllerAbstract implements \KaiPfeiffer\WPBase\Interfaces\AjaxInterface
{
    /**
     * AJAX_METHODS 
     * 
     * list of permitted functions, that can be called via Ajax
     * all requests to functions that ar not listed here are blocked
     */
    const AJAX_METHODS  = array('get', 'post');

    /** 
     * NONCE  
     * 
     * nonce for requests
     */
    const NONCE = '';


    const LOG_CLASS_NOT_FOUND = 1;

    const LOG_METHOD_NOT_CALLABLE = 2;

    const LOG_GET_COLUMN_LABELS = 8;   

    const LOG_FLAGS = 0
            // | 1 // LOG_CLASS_NOT_FOUND
            // | 2 // LOG_METHOD_NOT_CALLABLE
            // | 8 // LOG_GET_COLUMN_LABELS
    ;


    /**
     * class that provides database access
     * 
     * @var class
     */
    private $model;

    /**
     * $model_class
     * 
     * @var string
     */
    static protected $model_class = null;


    /*
    *   PROTECTED METHODS
    */


    /**
     * log_class_not_found
     * 
     * @since   0.1.0
     */
    protected static function log_class_not_found()
    {
        throw new \Exception("Model class not found for controller " . static::class);
    }

    /**
     * log_method_not_callable
     * 
     * @since   0.1.0
     */
    protected static function log_method_not_callable($method)
    {
        throw new \Exception("Method " . print_r($method, 1) . " not callable");
    }

    /**
     * get_model_class
     * 
     * Get-Request
     * 
     * @return  string  class name of the tramp controller
     * @since    0.1.0
     */
    protected static function get_model_class()
    {
        //
        if (static::$model_class === null) {
            static::$model_class = str_replace('_Controller', '_Model', static::class);
        }
        return static::$model_class;
    }


    /*
    *   PUBLIC METHODS
    */

    /**
     * check
     * 
     * Check data for missings
     * 
     * @param   array   $data
     * @return  array   list of missing columns
     * @since    0.1.0
     */
    public static function check(array $data): ?array
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            static::log_class_not_found();
            return  null;
        }

        $method   = array($model_class, 'check');
        if (!is_callable($method)) {
            return array();
        }
        $missings = call_user_func($method, $data);
        return ($missings);
    }

    static function get_column_labels()
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            static::log_class_not_found();
            return  null;
        }
        $method   = array($model_class, 'get_labels');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return array();
        }

        $columns = call_user_func($method);

        $method   = array($model_class, 'get_input_types');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return array();
        }

        static::LOG_GET_COLUMN_LABELS & static::LOG_FLAGS  &&

        $input_types = call_user_func($method);

        foreach ($columns as $key => $label) {
            $columns[$key]  = array(
                'type' => 'text',
                'label' => $label
            );
        }
        return $columns;
    }


    /**
     * get_columns
     *
     * @return  ?array
     * @since   0.1.0
     */
    public static function get_columns(): ?array
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            return static::log_class_not_found();
        }

        $method   = array($model_class, 'get_input_types');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return array();
        }

        $columns = array_map(function ($i) {
            return '';
        }, call_user_func($method));

        return $columns;
    }


    /**
     * get_primary_key
     *
     * @return  ?string
     * @since   0.1.0
     */
    public static function get_primary_key(): ?string
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            static::log_class_not_found();
            return  null;
        }

        $method   = array($model_class, 'get_primary_key');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return null;
        }

        $primary_key = call_user_func($method);
        return $primary_key['column'] ?? null;
    }


    /**
     * get_row_cnt
     *
     * @return  ?int
     * @since   0.1.0
     */
    public static function get_row_cnt(): ?int
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            static::log_class_not_found();
            return null;
        }

        $method = array($model_class, 'get_row_cnt');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return null;
        }

        $row_cnt = call_user_func($method);
        return $row_cnt;
    }


    /**
     * create
     * 
     * Create row
     * 
     * @param   array   $data
     * @return  integer id of the created row
     * @since    0.1.0
     */
    public static function create($data)
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            return static::log_class_not_found();
        }

        $method = array($model_class, 'create');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return null;
        }

        $id = call_user_func($method, $data);
        return ($id);
    }


    /**
     * delete
     * 
     * Delete-Request
     * 
     * @param   array	request
     * @return  array   result für json
     * @since    0.1.0
     */
    public static function delete($request)
    {
        return (array('request' => $request, 'method' => __FUNCTION__, 'class' => __CLASS__, 'nonce' => static::NONCE));
    }


    /**
     * read
     * 
     * Read-Request
     * 
     * @param   integer  $id
     * @param   integer  $page
     * @return  array    result für json
     * @since    0.1.0
     */
    public static function read($id = null, $page = null, $per_page = null)
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            static::log_class_not_found();
            return null;
        }

        $method = array($model_class, 'read');
            error_log(__CLASS__ . '->' . __LINE__ . '->Controller->read:' . is_callable(($method)) . '#');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return null;
        }

        $location_columns = call_user_func($method, $id, null, $page, $per_page);
        // works!
        // error_log(__CLASS__ . '->' . __LINE__ . '->Controller->Columns:' . print_r($location_columns,1));
        return ($location_columns);
    }

    /**
     * get_input_types
     *
     * @return  array
     * @since   0.1.0
     */
    static function get_input_types()
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            return static::log_class_not_found();
        }
        $method = array($model_class, 'get_input_types');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return array();
        }

        $input_types = call_user_func($method);

        return $input_types;
    }

    /**
     * 
     */
    static function search(string $s, ?int $page = null, ?int $per_page = null)
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            return static::log_class_not_found();
        }

        $method = array($model_class, 'search');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return null;
        }

        $rows = call_user_func($method, $s, $page, $per_page);
        return ($rows);
    }


    /**
     * get
     * 
     * Get-Request
     * 
     * @param   array|object	request
     * @return  array   result für json
     * @since    0.1.0
     */
    public static function get(array|object $request): ?array
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            static::log_class_not_found();
            return  null;
        }

        $id = $request['id'] ?? null;
        if ($id) {
            $id = intval($id);
        }
        $page = $request['page'] ?? null;
        if ($page) {
            $page = intval($page);
        }

        $method = array($model_class, 'read');
        if (!is_callable($method)) {
            static::log_method_not_callable($method);
            return null;
        }

        $location_columns = call_user_func($method, $id, $page);
        return (array(
            'request' => $request,
            'method' => __FUNCTION__,
            'class' => $model_class,
            'nonce' => static::NONCE,
            'result' => $location_columns,
        ));
    }



    /**
     * is_allowed
     * 
     * checks, if the requested method could be called via ajax
     * 
     * @param string
     * @since   0.1.0
     */
    static function is_allowed(string $name)
    {
        return in_array($name, static::AJAX_METHODS);
    }


    /**
     * patch
     * 
     * Patch-Request
     * 
     * @param   array	request
     * @return  array   result für json
     * @since    0.1.0
     */
    public static function patch($request)
    {
        return (array('request' => $request, 'method' => __FUNCTION__, 'class' => __CLASS__, 'nonce' => static::NONCE));
    }


    /**
     * post
     * 
     * Post-Request
     * 
     * @param   array	request
     * @return  array   result für json
     * @since    0.1.0
     */
    public static function post($request)
    {
        return (array('request' => $request, 'method' => __FUNCTION__, 'class' => __CLASS__, 'nonce' => static::NONCE));
    }


    /**
     * put
     * 
     * Put-Request
     * 
     * @param   array	request
     * @return  array   result für json
     * @since    0.1.0
     */
    public static function put($request)
    {
        return (array('request' => $request, 'method' => __FUNCTION__, 'class' => __CLASS__, 'nonce' => static::NONCE));
    }

    /**
     * update
     * 
     * Update row
     * 
     * @param   integer $id
     * @param   array   $data
     * @return  boolean true on success, false on failure
     * @since    0.1.0
     */
    public static function update($data)
    {
        $model_class = static::get_model_class();
        if ($model_class === null) {
            return static::log_class_not_found();
        }

        error_log(__CLASS__ . '->' . __LINE__ . '->Controller->update: data:' . print_r($data,1));
        $method = array($model_class, 'update');
        if (!is_callable($method)) {
            error_log(__CLASS__ . '->' . __LINE__ . '->Controller->update: method not callable:' . print_r($method,1));
            static::log_method_not_callable($method);
            return null;
        }
        $result = call_user_func($method, $data);
        return ($result);
    }
}
