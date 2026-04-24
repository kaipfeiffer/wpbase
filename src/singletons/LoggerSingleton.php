<?php

namespace KaiPfeiffer\WPBase\Singletons;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @class Logger Singleton
 * 
 * @version     1.0.0
 * @package     sbu-webapp
 * @author   
 */
class LoggerSingleton
{

    use \KaiPfeiffer\WPBase\Traits\SingletonTrait;

    /*
    *   KONSTANTEN
    */



    /**
     * enabled
     * 
     * true if the logger should log
     * 
     * @var bool
     */
    protected $enabled    = false;



    /**
     * override_enabled
     * 
     * override value for logger should log
     * 
     * @var bool
     */
    protected $override_enabled    = false;


    /**
     * logfile
     * 
     * path to the logfile
     * 
     * @var string
     */
    protected $logfile    = null;

    /**
     * override_logfile
     * 
     * overrides the path to the logfile
     * 
     * @var string
     */
    protected $override_logfile    = null;


    /**
     * type
     * 
     * set to "3" to log into file
     * 
     * @var int
     */
    protected $type    = 0;




    /**
     * PUBLIC METHODS
     */

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function get_instance($enabled = false, $logfile = '')
    {
        if (static::$instance === null) {
            static::$instance = new static($enabled, $logfile);
        } else {
            // static::$instance::$class_name
            // error_log(__CLASS__ . '->' . __FUNCTION__ . '->' . __LINE__ . ' STATIC: ' . static::$instance::$class_name);
        }

        return static::$instance;
    }

    /**
     * get_backtrace
     * 
     * gets the backtrace of the call
     * 
     * @return string
     */
    protected function get_backtrace()
    {
        $caller = debug_backtrace()[1];

        $caller = 'LOGGER:'.$caller['file'] . '->' . $caller['line'] . ':';

        return $caller;
    }


    /**
     * log
     * 
     * logs the message
     * 
     * @param string|array
     * @param int
     */
    public function log($message = '', $error_level = E_USER_NOTICE)
    {
        if (is_string($message)) {
            $message   = array($message);
        }

        if (WP_DEBUG ?? null) {
            $prefix = $this->get_backtrace();
            foreach ($message as $content) {
                trigger_error(
                    $prefix . $content,
                    $error_level
                );
            }
        } elseif ($this->enabled) {
            $prefix = $this->get_backtrace();
            error_log(
                $prefix,
                $this->logfile ? 3 : 0,
                $this->logfile
            );
            foreach ($message as $content) {
                error_log(
                    $content,
                    $this->logfile ? 3 : 0,
                    $this->logfile
                );
            }
        } elseif ($this->override_enabled) {
            $prefix = $this->get_backtrace();
            error_log(
                $prefix,
                $this->logfile ? 3 : 0,
                $this->logfile
            );
            foreach ($message as $content) {
                error_log(
                    $content,
                    $this->override_logfile ? 3 : 0,
                    $this->override_logfile
                );
            }
            $this->override_enabled = false;
            $this->override_logfile = null;
        }
    }


    /**
     * set
     * 
     * overrides settings
     * 
     * @param string|array
     * @param int
     */
    public function set($enabled = false, $logfile = '')
    {
        $this->override_enabled = $enabled;
        $this->override_logfile = $logfile;
        return $this;
    }

    /**
     * Konstruktor
     */
    protected function __construct($enabled = false, $logfile = '')
    {
        $this->enabled = $enabled;
        $this->logfile = $logfile;
    }
}
