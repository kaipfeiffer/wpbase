<?php

namespace KaiPfeiffer\WPBase\Abstracts;

if (!defined('WPINC')) {
    die;
}

/**
 * abstract static class for admin tabs
 *
 * @author  Kai Pfeiffer <kp@loworx.com>
 * @package rideshare
 * @since   1.0.0 
 */

abstract class Admin_Tab_Abstract
{
    const ADMIN_TAB_SLUG = '';

    const ADMIN_TAB_LIB = '';

    /** 
     * AJAX_METHODS
     * 
     * Methods that could by called by the ajax router
     * 
     * @since 1.0.63
     */
    const AJAX_METHODS  = array();



    static function get_lib()
    {
        if(!static::ADMIN_TAB_LIB){
            return null;
        }
        return static::ADMIN_TAB_LIB;
    }

    static function get_slug()
    {
        if(!static::ADMIN_TAB_SLUG){
            return null;
        }
        return static::ADMIN_TAB_SLUG;
    }

    static function get_title()
    {
        return __('Titel', 'rideshare');
    }

    static function get_view()
    {
        $class  = static::class;

        $relative_class_name = strtolower(str_replace('_', '-', preg_replace('/\W/', '', str_replace(__NAMESPACE__, '', $class))));
        return '<div>' . $relative_class_name . '</div>';

        return static::ADMIN_TAB_SLUG;
    }


    /**
     * is_allowed
     * 
     * checks, if the requested method could be called via ajax
     * 
     * @param string
     * @since   1.0.0
     */
    static function is_allowed(string $name)
    {
        return in_array($name, static::AJAX_METHODS);
    }
}
