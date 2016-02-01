<?php if (!defined('FW')) die('Forbidden');

class Page_Builder_Simple_Item extends Page_Builder_Item
{
	private $type = 'simple';
	private $builder_data = array();

	public function get_type()
	{
		return $this->type;
	}

	public function enqueue_static()
	{
		$static_uri = fw()->extensions->get('page-builder')->get_uri('/includes/page-builder/includes/item-types/simple/static');
		$version    = fw()->extensions->get('page-builder')->manifest->get_version();

		wp_enqueue_style(
			$this->get_builder_type() . '_item_type_' . $this->get_type(),
			$static_uri . '/css/styles.css',
			array('fw'),
			$version
		);
		wp_enqueue_script(
			$this->get_builder_type() . '_item_type_' . $this->get_type(),
			$static_uri . '/js/scripts.js',
			array('fw', 'fw-events', 'underscore'),
			$version,
			true
		);
		wp_localize_script(
			$this->get_builder_type() . '_item_type_' . $this->get_type(),
			str_replace('-', '_', $this->get_builder_type()) . '_item_type_' . $this->get_type() . '_data',
			$this->get_builder_data()
		);

		do_action('fw:ext:page-builder:item-type:simple:enqueue_static');
	}

	/**
	 * @return array(
	 *  array(
	 *      'tab'   => __('Tab 1', 'fw'),
	 *      'title' => __('thumb title 1', 'fw'),
	 *      'data'  => array( // optional
	 *          'key1'  => 'value1',
	 *          'key2'  => 'value2'
	 *      )
	 *  ),
	 *  array(
	 *      'tab'   => __('Tab 2', 'fw'),
	 *      'title' => __('thumb title 2', 'fw'),
	 *      'data'  => array( // optional
	 *          'key1'  => 'value1',
	 *      )
	 *  ),
	 *  ...
	 * )
	 */
	protected function get_thumbnails_data()
	{
		$data = $this->get_builder_data();
		$thumb_data = array();
		foreach ($data as $id => $item) {
			$thumb_data[$id] = array(
				'tab'           => $item['tab'],
				'title'         => $item['title'],
				'description'   => $item['description'],
				'data'          => array(
					'shortcode' => $id
				)
			);

			if (isset($item['icon'])) {
				$thumb_data[$id]['icon'] = $item['icon'];
			}
		}

		$this->sort_thumbnails($thumb_data);
		return $thumb_data;
	}

	private function get_builder_data()
	{
		if (empty($this->builder_data)) {
			$shortcodes = fw()->extensions->get('shortcodes')->get_shortcodes();
			foreach ($shortcodes as $tag => $shortcode) {
				$config = $shortcode->get_config('page_builder');
				if ($config) {

					// check if the shortcode type is valid
					$config = array_merge(array('type' => $this->type), $config);
					if ($config['type'] !== $this->get_type()) {
						continue;
					}

					if (!isset($config['tab'])) {
						trigger_error(
							sprintf(__("No Page Builder tab specified for shortcode: %s", 'fw'), $tag),
							E_USER_WARNING
						);
					}

					$item_data = array_merge(
						array(
							'tab'         => '~',
							'title'       => $tag,
							'description' => '',
							'localize' => array(
								'edit' => __( 'Edit', 'fw' ),
								'remove' => __( 'Remove', 'fw' ),
								'duplicate' => __( 'Duplicate', 'fw' ),
							),
							'icon' => null,
							'title_template' => null,
						),
						$config
					);

					if (
						!isset($item_data['icon'])
						&&
						($icon = $shortcode->locate_URI('/static/img/page_builder.png'))
					) {
						$item_data['icon'] = $icon;
					}

					// if the shortcode has options we store them and then they are passed to the modal
					$options = $shortcode->get_options();
					if ($options) {
						$item_data['options'] = $this->transform_options($options);
						fw()->backend->enqueue_options_static($options);
					}

					$this->builder_data[$tag] = $item_data;
				}
			}
		}

		return $this->builder_data;
	}

	/*
	 * Puts each option into a separate array
	 * to keep it's order inside the modal dialog
	 */
	private function transform_options($options)
	{
		$new_options = array();
		foreach ($options as $id => $option) {
			if (is_int($id)) {
				/**
				 * this happens when in options array are loaded external options using fw()->theme->get_options()
				 * and the array looks like this
				 * array(
				 *    'hello' => array('type' => 'text'), // this has string key
				 *    array('hi' => array('type' => 'text')) // this has int key
				 * )
				 */
				$new_options[] = $option;
			} else {
				$new_options[] = array($id => $option);
			}
		}
		return $new_options;
	}

	/*
	 * Sorts the thumbnails by their titles
	 */
	private function sort_thumbnails(&$thumbnails)
	{
		usort($thumbnails, array($this, 'sort_thumbnails_helper'));
		return $thumbnails;
	}

	private function sort_thumbnails_helper($thumbnail1, $thumbnail2)
	{
		return strcasecmp($thumbnail1['title'], $thumbnail2['title']);
	}

	public function get_value_from_attributes($attributes)
	{
		// simple items do not contain other items
		unset($attributes['_items']);

		if (
			($builder_data = $this->get_builder_data())
			&&
			isset($builder_data[ $attributes['shortcode'] ])
			&&
			($shortcode_data = $builder_data[ $attributes['shortcode'] ])
			&&
			isset($shortcode_data['options'])
		) {
			if (empty($attributes['atts'])) {
				/**
				 * The options popup was never opened and there are no attributes.
				 * Extract options default values.
				 */
				$attributes['atts'] = fw_get_options_values_from_input( $shortcode_data['options'], array() );
			} else {
				/**
				 * There are saved attributes.
				 * But we need to execute the _get_value_from_input() method for all options,
				 * because some of them may be (need to be) changed (auto-generated) https://github.com/ThemeFuse/Unyson/issues/275
				 * Add the values to $option['value']
				 */
				$options = fw_extract_only_options($shortcode_data['options']);

				foreach ($attributes['atts'] as $option_id => $option_value) {
					if (isset($options[$option_id])) {
						$options[$option_id]['value'] = $option_value;
					}
				}

				$attributes['atts'] = fw_get_options_values_from_input( $options, array() );
			}
		}

		return $attributes;
	}

	public function get_shortcode_data($atts = array())
	{
		$return = array(
			'tag' => $atts['shortcode'],
		);

		if (isset($atts['atts'])) {
			$return['atts'] = $atts['atts'];
		}

		return $return;
	}
}
FW_Option_Type_Builder::register_item_type('Page_Builder_Simple_Item');
