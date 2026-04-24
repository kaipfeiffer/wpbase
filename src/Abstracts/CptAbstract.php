<?php

namespace KaiPfeiffer\WPBase;

if (!defined('ABSPATH')) {
	exit;
}

abstract class CptAbstract
{
	/*
    *   KONSTANTEN
    */

	/** 
	 * NONCE
	 * 
	 * @const string
	 */
	const NONCE = '';


	/** 
	 * AJAX_TARGET
	 * 
	 * Das Ajax-Ziel
	 * 
	 * @const string
	 */
	const AJAX_TARGET = '';

	const CLASSNAME	= __CLASS__;


	const LOG_POST_TYPE_TEMPLATE = 1;

	const LOG_WP_INSERT_POST_DATA = 2;

	const LOG_LOG_WP_AFTER_INSERT_NEW_POST = 4;

	const LOG_FLAGS = 0
		// | 1 // LOG_POST_TYPE_TEMPLATE
		// | 2 // LOG_WP_INSERT_POST_DATA
		// | 4 // LOG_WP_AFTER_INSERT_NEW_POST
	;

	/**
	 * check if post inserted.
	 *
	 * @var string
	 */
	protected static $after_insert;


	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected static $object_type;

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected static $post_type;

	/**
	 * Stores service data.
	 *
	 * @var array
	 */
	protected static $data = array();


	/**
	 * Stores service data.
	 *
	 * @var array
	 */
	protected static $handlers = array();


    /**
     * get_plugin_dir_path
     * 
     * @return string    the path to the plugin directory
     */
	abstract protected static function get_plugin_dir_path(): string;


	/**
	 * get_plugin_url
	 * 
	 * @return string    the url to the plugin directory
	 */
	abstract protected static function get_plugin_url(): string;


	/**
	 * get_plugin_version
	 * 
	 * @return string    the version of the plugin
	 */
	abstract protected static function get_plugin_version(): string;
	

	/**
	 * create custom post types
	 */
	abstract public static function get_custom_post_type_definition();




	/**
	 * create custom post types
	 *
	 * @param string the value to set
	 *
	 * @since    0.1.0
	 */
	public static function create_custom_post_types()
	{
		$slug = static::get_post_type();
		register_post_type($slug, static::get_custom_post_type_definition());
	}


	/**
	 * create meta boxes
	 */
	abstract protected static function add_meta_boxes();

	/**
	 * create post content from meta values
	 *
	 * @param	string
	 * @param	array
	 * @return	string
	 */
	protected static function create_post_content(string $post_content, array $postarr): string
	{
		return $post_content;
	}

	/**
	 * create post excerpt from meta values
	 *
	 * @param	string
	 * @param	array
	 * @return	string
	 */
	protected static function create_post_excerpt(string $post_excerpt, array $postarr): string
	{
		return $post_excerpt;
	}


	/**
	 * create post title from meta values
	 *
	 * @param	string
	 * @param	array
	 * @return	string
	 */
	protected static function create_post_title(string $post_title, array $postarr): string
	{
		return $post_title;
	}


	/**
	 * create_view
	 * 
	 * creates the view for editing CPT
	 *
	 * @param	stdClass
	 * @since	0.1.0
	 */
	public static function create_view($post)
	{

		$meta_data = get_post_meta($post->ID);

		foreach ($meta_data as $key => $value) {
			$index = preg_replace('/^_/', '', $key);
			static::$data[$index] = $value[0];
		}

		$read_only	= ''; //current_user_can('edit_full_' . static::$object_type) ? '' : ' readonly="readonly" ';
		$data_details = apply_filters(static::$object_type . '_data_details', static::get_data_details());

?>
		<div class="kppf-meta-box">
			<?php
			foreach (static::$data as $key => $value) :
				if (array_key_exists($key, $data_details)) {
					$field_info = $data_details[$key];
					if (!in_array($field_info['type'], array('display', 'imagelist')) || $value) {

			?>
						<p class="post-attributes-label-wrapper <?php echo $key ?>-label-wrapper">
							<label class="post-attributes-label" for="<?php echo $key ?>">
								<?php echo $field_info['label'] ?>
							</label>
						</p>
						<?php
					}
					switch ($field_info['type']) {
						case 'date-input': {
						?>
								<input name="<?php echo $key ?>" <?php echo $read_only ?> type="date" id="<?php echo $key ?>" value="<?php echo $value ?>">
								<?php
								break;
							}
						case 'display': {
								if ($value) {
								?>
									<input name="<?php echo $key ?>" readonly="readonly" class="<?php echo static::$post_type; ?>_readonly" type="text" id="<?php echo $key ?>" value="<?php echo esc_attr($value) ?>">
								<?php
								} else {
								?>
									<input name="<?php echo $key ?>" readonly="readonly" class="<?php echo static::$post_type; ?>_readonly" type="hidden" id="<?php echo $key ?>" value="<?php echo esc_attr($value) ?>">
								<?php
								}
								break;
							}
						case 'imagelist': {
								$images	= unserialize($value);
								echo '<div class="' . static::$post_type . '_image_container"><div class="' . static::$post_type . '_image_page">';
								foreach ($images as $index => $image_id) {
									if ($index % 2) {
										echo '</div><div class="' . static::$post_type . '_image_page">';
									}
									echo wp_get_attachment_image($image_id);
								}
								echo '</div></div>';
								break;
							}
						case 'input': {
								?>
								<input name="<?php echo $key ?>" <?php echo $read_only ?> type="text" id="<?php echo $key ?>" value="<?php echo esc_attr($value) ?>">
							<?php
								break;
							}
						case 'number': {
							?>
								<input name="<?php echo $key ?>" <?php echo $read_only ?> type="number" id="<?php echo $key ?>" value="<?php echo esc_attr($value) ?>">
							<?php
								break;
							}
						case 'hidden': {
							?>
								<input name="<?php echo $key ?>" <?php echo $read_only ?> type="hidden" id="<?php echo $key ?>" value="<?php echo esc_attr($value) ?>">
							<?php
								break;
							}
						case 'checkbox': {
							?>
								<input name="<?php echo $key ?>" <?php echo $read_only ?> type="checkbox" id="<?php echo $key ?>" <?php echo esc_attr($value) ? 'checked' : '' ?>>
							<?php
								break;
							}
						case 'textarea': {
							?>
								<textarea rows="12" cols="40" <?php echo $read_only ?> name="<?php echo $key ?>" id="<?php echo $key ?>"><?php echo $value ?></textarea>
								<?php
								break;
							}
						default: {

								if (is_array($field_info['type']) && $field_info['type']) {

									switch (strtolower($field_info['type']['type'])) {
										case 'select': {
												$items = $field_info['type']['items'];
												$input_name = $key;
												if (isset($field_info['type']['attr'])) {
													$input_attr = $field_info['type']['attr'];
													if (0 <= strpos('multiple', $input_attr)) {
														$input_name = $key . '[]';
													}
												}
								?>
												<select <?php echo $input_attr ?> name="<?php echo $input_name ?>" <?php echo $read_only ?> type="text" id="<?php echo $key ?>" value="<?php echo $value ?>">
													<?php
													if (is_array($items)) {
														foreach ($items as $index => $option) {
													?>
															<option <?php echo ($value & $index ? 'selected' : '') ?> value="<?php echo $index ?>"><?php echo $option ?></option>
													<?php
														}
													}
													?>
												</select>
												<?php



												break;
											}
										case 'file': {
												$input_attr = $field_info['type']['attr'] ?? '';
												if ($value) {
												?>
													<input class="kppf-path-input" readonly="readonly" editable="false" type="text" name="<?php echo $key ?>" id="<?php echo $key ?>" value="<?php echo $value ?>" />
												<?php

												} else {
												?>
													<input class="kppf-file-input" type="file" <?php echo $input_attr ?> name="<?php echo $key ?>" id="<?php echo $key ?>" />
													<div class="kppf-scale">
														<div class="kppf-meter">
														</div>
														<div class="kppf-meter-content">
															<input class="kppf-path-input" readonly="readonly" editable="false" type="text" name="<?php echo $key ?>_path" id="<?php echo $key ?>_path" />
														</div>
													</div>
												<?php
												}
												break;
											}
										case 'textarea': {
												$input_attr = $field_info['type']['attr'];
												?>
												<textarea <?php echo $input_attr ?> name="<?php echo $key ?>" <?php echo $read_only ?> type="text" id="<?php echo $key ?>"><?php echo $value ?></textarea>

												<?php
												break;
											}
										case 'readonly/button': {
												if (!$value) {
												?>
													<input name="<?php echo $key ?>" <?php echo $read_only ?> type="text" class="kppf_medium" id="<?php echo $key ?>" value="">
													<div id="<?php echo $key ?>_form" style="display: inline;">
														<?php
														foreach ($field_info['type']['parameters'] as $entry) {
															if (is_array($entry)) {
																foreach ($entry as $index => $item)
														?>
																<input class="kppf_sub_form_field" name="subform_<?php echo $key ?>_<?php echo $index ?>" type="hidden" id="subform_<?php echo $key ?>_<?php echo $index ?>" value="<?php echo $item ?>">
															<?php
															} else {
															?>
																<input class="kppf_sub_form_field" name="subform_<?php echo $key ?>_<?php echo $entry ?>" type="hidden" id="subform_<?php echo $key ?>_<?php echo $entry ?>" value="">
														<?php
															}
														}
														?>
														<input name="subform_<?php echo $key ?>_target" type="hidden" id="subform_<?php echo $key ?>_target" value="<?php echo $field_info['type']['target']  ?>">
														<input name="subform_<?php echo $key ?>_method" type="hidden" id="subform_<?php echo $key ?>_method" value="<?php echo $field_info['type']['method'] ?>">
														<input type="button" class="kppf_form_submit_btn button" style="width: 24%; float: right;" value="<?php echo $field_info['type']['label'] ?>" />
													</div>
												<?php
												} else {
												?>
													<input name="<?php echo $key ?>" type="text" disabled="disabled" readonly="readonly" id="<?php echo $key ?>" value="<?php echo $value; ?>">
												<?php
												}
												break;
											}
										case 'wp_dropdown_pages': {
												$params = $field_info['type']['params'];
												if (!$params) {
													$params = [];
												}
												$defaults = ['id' => $key, 'name' => $key, 'selected' => empty($value) ? 0 : $value];
												$params = array_merge($defaults, $params);
												wp_dropdown_pages($params);
												break;
											}
										case 'kppf_image_selector': {
												$gallery	= array();
												$images 	= json_decode($value);
												foreach ($images as $image_id) {
													array_push($gallery, get_post($image_id));
												}
												?>
												<div class="kppf-image-gallery">
													<?php foreach ($gallery as $image) { ?>
														<img class="kppf-admin-image" src="<?php echo $image->guid; ?>" height="50" alt="<?php echo $image->post_title; ?>" />
													<?php } ?>
												</div>
												<input name="<?php echo $key ?>" type="hidden" id="<?php echo $key ?>" value="<?php echo $value; ?>" />
												<input id="add_image_button" type="button" class="button" value="<?php echo __('Add image', 'patient-file'); ?>" />
			<?php

												break;
											}

										default: {
											}
									}
								}
							}
					}
				}
			endforeach;
			?>
		</div>
<?php
	}



	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    0.1.0
	 */
	public static function enqueue_scripts()
	{
		global $post_type;
		$script_id	= static::$post_type . '_js';
		if (!wp_script_is($script_id, 'enqueued') && static::$post_type == $post_type) {
			$script_url =  static::get_plugin_url() . implode(DIRECTORY_SEPARATOR, array('admin', 'assets', 'js', static::$post_type . '-admin.js'));
			$script_file = static::get_plugin_dir_path() . implode(DIRECTORY_SEPARATOR, array('admin', 'assets', 'js', static::$post_type . '-admin.js'));

			if (file_exists($script_file)) {
				wp_enqueue_script(
					$script_id,
					$script_url,
					array('jquery'),
					static::get_plugin_version(),
					true
				);

				$params = static::get_ajax_params();

				wp_localize_script($script_id, static::$post_type . '_params', $params);
			}
		}
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    0.1.0
	 */
	public static function enqueue_styles()
	{
		global $post_type;
		$style_id	= static::$post_type . '_css';

		if (!wp_style_is($style_id, 'enqueued') && static::$post_type == $post_type) {
			$style_url =  static::get_plugin_url() . 'admin/css/' . static::$post_type . '-admin.css';
			$style_file = static::get_plugin_dir_path() . 'admin/css/' . static::$post_type . '-admin.css';

			if (file_exists($style_file)) {
				wp_enqueue_style($style_id, $style_url, array(), static::get_plugin_version(), 'all');
			}
		}
	}


	/**
	 * get_ajax_params
	 *
	 * @since    0.1.0
	 */
	public static function get_ajax_params()
	{
		return [
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce(static::NONCE),
			'target' => static::AJAX_TARGET,
		];
	}


	/**
	 * Stores service data.
	 *
	 * @return array
	 */
	abstract protected static function get_data_details();


	/**
	 * Gets the Post-Type of the class.
	 *
	 * @return array
	 */
	public static function get_field_names(): array
	{
		$fieldnames	= static::$data;

		foreach ($fieldnames as $key => $value) {
			$fieldnames[$key]	= '';
		}

		return $fieldnames;
	}


	/**
	 * Gets the Post-meta_data of the class.
	 *
	 * @param	integer
	 * @return	array
	 */
	public static function get_post_meta($post_id): array
	{
		$meta_data	= get_post_meta($post_id);

		foreach ($meta_data as $index => $value) {
			$key				= preg_replace('/^_/', '', $index);
			if (isset($key, static::$data)) {
				if (is_array($value)) {
					if (1 < count($value)) {
						static::$data[$key]	= $value;
					} elseif (1 === count($value)) {
						$entry				= reset($value);
						static::$data[$key]	= unserialize($entry);

						static::$data[$key]	= static::$data[$key] ? static::$data[$key] : $entry;
					}
				} else {
					static::$data[$key]	= unserialize($value);
				}
			}
		}
		return static::$data;
	}


	/**
	 * Gets the Post-Type of the class.
	 *
	 * @return	string
	 */
	public static function get_post_type(): string
	{
		return static::$post_type;
	}


	/**
	 * filter: post_type_template
	 *
	 * Liefert das angepasste Template für den Post-Type zurück,
	 * falls dieses existiert.
	 * 
	 * @param	string	Dateipfad zum Template
	 * @return	string	angepasster Dateipfad zum Template
	 * @since    0.1.0
	 */
	static function post_type_template($single)
	{

		global $post;

		/* Überprüft, ob der CPT angezeigt wird */
		if ($post->post_type === static::$post_type) {
			$plugin_dir_path    = static::get_plugin_dir_path();
			$plugin_url		    = static::get_plugin_url();
			$plugin_version     = static::get_plugin_version();

			$filename	=  $plugin_dir_path . implode(DIRECTORY_SEPARATOR, array('templates', static::$post_type . '-single.php'));

			if (file_exists($filename)) {
				// $style_id für Template festlegen
				$style_id	= static::$post_type . '_single_css';
				$script_id	= static::$post_type . '_single_js';
				// Wenn Style noch nicht enqeued wurde
				if (!wp_style_is($style_id, 'enqueued')) {
					$style_url	= $plugin_url . implode(DIRECTORY_SEPARATOR, array('public', 'css', static::$post_type . '-single.css'));
					$style_file = $plugin_dir_path . implode(DIRECTORY_SEPARATOR, array('public', 'css', static::$post_type . '-single.css'));

					// ... und CSS-Datei existiert
					if (file_exists($style_file)) {
						// Style einbinden
						wp_enqueue_style($style_id, $style_url, array(), $plugin_version, 'all');
					}
				}
				if (!wp_script_is($script_id, 'enqueued')) {
					$script_url	= $plugin_url . implode(DIRECTORY_SEPARATOR, array('public', 'js', static::$post_type . '-single.js'));
					$script_file = $plugin_dir_path . implode(DIRECTORY_SEPARATOR, array('public', 'js', static::$post_type . '-single.js'));

					// ... und JS-Datei existiert
					if (file_exists($script_file)) {
						// Style einbinden
						wp_enqueue_script($script_id, $script_url, array(), $plugin_version, 'all');
						$params = static::get_ajax_params();
					}
				}
				return $filename;
			}
		}

		return $single;
	}


	/**
	 * wp_insert_post_data
	 *
	 * @param	array
	 * @param	array
	 * @return	array
	 * @since	0.1.0
	 */
	public static function wp_insert_post_data(array $data, array $postarr, array $unsanitized_postarr, bool $update): array
	{
		if ($data['post_type'] === static::$post_type) {
			// after insert action only for new posts
			if (!$update) {
				static::$after_insert = true;
			} elseif (is_callable(array(static::class, 'wp_after_insert_new_post'))) {
				static::$after_insert = false;
				static::LOG_FLAGS & static::LOG_WP_INSERT_POST_DATA &&
					add_action('wp_insert_post', array(static::class, 'wp_after_insert_new_post'), 10, 3);
			}

			$data['post_title'] =  static::create_post_title($data['post_title'] ?? '', $postarr); //Updates the post title to your new title.
			$data['post_content'] =  static::create_post_content($data['post_content'] ?? '', $postarr);
			$data['post_excerpt'] =  static::create_post_excerpt($data['post_excerpt'] ?? '', $postarr);


			if (isset($postarr) && isset($postarr['ID'])) {
				$post_id = intval($postarr['ID']);

				foreach (static::$data as $key => $value) {
					if (isset($postarr[$key])) {
						if (0 <= strpos($key, static::$post_type)) {
							if (isset(static::$handlers[$key]) && is_callable(static::$handlers[$key])) {
								$new_value = call_user_func(static::$handlers[$key], $postarr[$key]);
							} else {
								$new_value = $postarr[$key];
							}
							$meta_id = update_post_meta(
								$post_id,
								'_' . $key,
								$new_value
							);
							static::$data[$key] = $new_value;
						} else {
							static::$data[$key] = '';
						}
					}
					// delete deactiveted checkboxes
					else {
						$meta_id = delete_post_meta(
							$post_id,
							'_' . $key,
							''
						);
					}
				}
			}
		}

		return $data; // Returns the modified data.
	}
}
