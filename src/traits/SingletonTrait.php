<?php

namespace KaiPfeiffer\WPBase\Traits;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}


/**
 * Settings
 * 
 * define "constants" to use in the classes
 * 
 * @author  Kai Pfeiffer <kp@loworx.com>
 * 
 * @since   0.1.0 
 */
trait SingletonTrait
{
    /**
     * Die Instanz der Klasse.
     * 
     * @since    0.1.0
     */
    protected static $instance = null;


	/**
	 * gets the instance via lazy initialization (created on first usage)
	 */
	public static function get_instance()
	{
		if (static::$instance === null) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * darf nur privat aufgerufen werden
	 */
	protected function __construct()
	{
	}

	/**darf nicht geklont werden
	 */
	private function __clone()
	{
	}

	/**
	 * prevent from being unserialized (which would create a second instance of it)
	 */
	public function __wakeup()
	{
		throw new \Exception("Cannot unserialize singleton");
	}
}
