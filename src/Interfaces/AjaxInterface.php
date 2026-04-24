<?php

namespace KaiPfeiffer\WPBase\Interfaces;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajax-Interface
 * 
 * @since   1.0.0
 */

interface AjaxInterface
{
	/**
	 * is_allowed
	 * 
	 * checks, if the requested method could be called via ajax
	 * 
	 * @param string
	 */
    static function is_allowed(string $name);
}
