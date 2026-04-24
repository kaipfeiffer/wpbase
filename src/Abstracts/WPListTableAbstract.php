<?php

namespace KaiPfeiffer\WPBase\Abstracts;

if (!defined('ABSPATH')) {
    exit;
}

if (! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    require_once(ABSPATH . 'wp-admin/includes/template.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-screen.php');
    require_once(ABSPATH . 'wp-admin/includes/screen.php');
}

abstract class WPListTableAbstract extends \WP_List_Table
{
    protected $nonce;
    protected $nonce_field;
    protected $controller;

    public function __construct($args = array())
    {
        $args = wp_parse_args(
            $args,
            array(
                'plural'   => '',
                'singular' => '',
                'ajax'     => false,
                'screen'   => null,
                'nonce'     => 'nonce_field',
                'nonce'     => str_replace(__NAMESPACE__ . '\\', '', __CLASS__)
            )
        );
        $this->controller   = preg_replace('/(\w+)(_WP_List_Table)/', '$1_Controller', static::class);
        $this->nonce        = $args['nonce'];
        $this->nonce_field  = $args['nonce_field'];
        unset($args['nonce'], $args['nonce_field']);
        parent::__construct($args);
    }

    public function ajax_response()
    {
        $this->prepare_items();

        ob_start();
        $this->_column_headers = $this->get_column_info();
        $this->print_column_headers();
        ob_clean();
        ob_start();
        if (! empty($_REQUEST['no_placeholder'])) {
            $this->display_rows();
        } else {
            $this->display_rows_or_placeholder();
        }

        $rows = ob_get_clean();

        $response = array(
            'rows' => $rows,
            'current_page'  => $this->get_pagenum()
        );

        if (isset($this->_pagination_args['total_items'])) {
            $singular   = '%s ' . $this->_args['singular'];
            $plural     = '%s ' . $this->_args['plural'];
            $response['total_items_i18n'] = sprintf(
                /* translators: Number of items. */
                _n($singular, $plural, $this->_pagination_args['total_items']),
                number_format_i18n($this->_pagination_args['total_items'])
            );
        }

        if (isset($this->_pagination_args['total_pages'])) {
            $response['total_pages']      = $this->_pagination_args['total_pages'];
            $response['total_pages_i18n'] = number_format_i18n($this->_pagination_args['total_pages']);
        }

        die(wp_json_encode($response));
    }

    public function ajax_user_can()
    {
        return current_user_can('manage_options');
    }


    function display()
    {
        wp_nonce_field($this->nonce . '_ajax', $this->nonce_field . '_ajax');
        parent::display();
    }


    function column_default($item, $column_name)
    {

        return htmlspecialchars($item[$column_name]) ?? null;
    }

    function get_columns()
    {
        $columns = array();
        $get_colums_labels_request = array($this->controller, 'get_column_labels');


        if (is_callable(($get_colums_labels_request))) {
            $column_details   = call_user_func($get_colums_labels_request);
            foreach ($column_details as $column_name => $column_detail) {
                if (is_array($column_detail)) {
                    $columns[$column_name] = $column_detail['label'];
                } else {
                    $columns[$column_name] = $column_detail;
                }
            }
        } else {

        }
        return $columns;
    }

    function prepare_items()
    {
        $per_page       = $this->get_items_per_page('users_per_page', 5);
        $current_page   = $this->get_pagenum() - 1;

        $this->_column_headers = $this->get_column_info();

        $nonce_ok   = wp_verify_nonce($_REQUEST[$this->nonce_field], $this->nonce);
        if ((defined('DOING_AJAX') && DOING_AJAX) || wp_is_json_request()) {
            $nonce_ok   = true;
        }

        if (($_REQUEST['s'] ?? null) && $nonce_ok) {
            $search_request = array($this->controller, 'search');
            if (is_callable(($search_request))) {
                $this->items = call_user_func($search_request, $_REQUEST['s'], $current_page, $per_page);
            } else {

            }
        } else {
            $read_request = array($this->controller, 'read');
            if (is_callable(($read_request))) {
                $this->items = call_user_func($read_request, null, $current_page, $per_page);
            } else {

            }
        }

        $get_row_cnt_request = array($this->controller, 'get_row_cnt');
        if (is_callable(($get_row_cnt_request))) {
            $total_items    = call_user_func($get_row_cnt_request);
        } else {

        }


        $this->set_pagination_args(array(
            'current_page' => $current_page,
            'total_items' => $total_items,
            'per_page'    => $per_page,
        ));
    }
}
