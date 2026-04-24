<?php

namespace KaiPfeiffer\WPBase\Abstracts;

if (!defined('ABSPATH')) {
    exit;
}

class FormTableAbstract
{
    protected $nonce;
    protected $nonce_field;
    protected $controller;
    protected $label;

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
        $this->controller   = preg_replace('/(\w+)(_Form_Table)/', '$1_Controller', static::class);
        $this->nonce        = $args['nonce'];
        $this->nonce_field  = $args['nonce_field'];
        $this->label        = $args['singular'];
    }

    public function update($request)
    {
        $sanitized      = array();
        $input_types    = $this->controller::get_input_types();
        $input_types[$this->controller::get_primary_key()] = 'integer';
        foreach ($input_types as $key => $type) {
            $sanitized[$key] = $request->get($key, $type);
        }
        error_log(__CLASS__ . '->' . __LINE__ . '->' . print_r($sanitized, true));
        $this->controller::update($sanitized);
    }

    public function display()
    {
        $hidden_fields  = array();
        $id = intval($_REQUEST['id']);

        $get_colums_labels_request = array($this->controller, 'get_column_labels');
        if (is_callable(($get_colums_labels_request))) {
            $column_labels   = call_user_func($get_colums_labels_request);
        }
        $read_request = array($this->controller, 'read');
        if (is_callable(($read_request))) {
            $columns    = call_user_func($read_request, $id);
        }
        $get_title_request = array($this->controller, 'get_title');
        if (is_callable(($get_title_request))) {
            $title    = call_user_func($get_title_request, $columns);
        }
?>
        <h1><?php echo sprintf(__('Edit %s', 'rideshare'), $title) ?></h1>

        <form method="post">
            <table class="form-table" role="presentation">
                <tbody>
                    <?php
                    foreach ($columns as $column_name => $column) {
                        if (isset($column_labels[$column_name])) {
                            echo '<tr>';
                            echo '<th scope="row"><label for="' . esc_attr($column_name) . '">' . esc_html($column_labels[$column_name]['label']) . '</label></th>';
                            echo '<td>';
                            echo '<input type="' . esc_attr($column_labels[$column_name]['type']) . '" name="' . esc_attr($column_name) . '" id="' . esc_attr($column_name) . '" value="' . esc_attr($columns[$column_name] ?? '') . '" class="regular-text">';
                            echo '</td>';
                            echo '</tr>';
                        } else {
                            array_push($hidden_fields, sprintf(
                                '<input type="hidden" name="%s" value="%s">',
                                esc_attr($column_name),
                                esc_attr($columns[$column_name] ?? '')
                            ));
                        }
                    }
                    ?>
                </tbody>
            </table>
            <?php
            wp_nonce_field($this->nonce, $this->nonce_field);
            echo implode("\n", $hidden_fields);
            ?>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo sprintf(__('Update %s', 'rideshare'), $this->label) ?>">
            </p>
        </form>
<?php
    }
}
