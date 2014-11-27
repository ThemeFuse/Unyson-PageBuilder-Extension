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
		$static_uri = fw()->extensions->get('page-builder')->locate_URI('/includes/fw-option-type-page-builder/includes/item-types/simple/static/');
		$version    = fw()->extensions->get('page-builder')->manifest->get_version();

		wp_enqueue_style(
			$this->get_builder_type() . '_item_type_' . $this->get_type(),
			$static_uri . 'css/styles.css',
			array('fw'),
			$version
		);
		wp_enqueue_script(
			$this->get_builder_type() . '_item_type_' . $this->get_type(),
			$static_uri . 'js/scripts.js',
			array('fw', 'fw-events', 'underscore'),
			$version,
			true
		);
		wp_localize_script(
			$this->get_builder_type() . '_item_type_' . $this->get_type(),
			str_replace('-', '_', $this->get_builder_type()) . '_item_type_' . $this->get_type() . '_data',
			$this->get_builder_data()
		);
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

			if (isset($item['image'])) {
				$thumb_data[$id]['image'] = $item['image'];
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
						),
						$config
					);

					// search for the thumb image (icon)
					$builder_icon_uri = $shortcode->locate_URI('/static/img/page_builder.png');
					if ($builder_icon_uri) {
						$item_data['image'] = $builder_icon_uri;
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
			$new_options[] = array($id => $option);
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

		/*
		 * when saving the modal, the options values go into the
		 * 'atts' key, if it is not present it could be
		 * because of two things:
		 * 1. The shortcode does not have options
		 * 2. The user did not open or save the modal (which will be more likely the case)
		 */
		if (!isset($attributes['atts'])) {
			$builder_data   = $this->get_builder_data();
			$shortcode_data = $builder_data[ $attributes['shortcode'] ];
			if (isset($shortcode_data['options'])) {
				$attributes['atts'] = fw_get_options_values_from_input($shortcode_data['options'], array());
			}
		}

		return $attributes;
	}

	public function get_shortcode_data($atts = array())
	{
		$return = array(
			'tag'  => $atts['shortcode'],
		);
		if (isset($atts['atts'])) {
			$return['atts'] = $atts['atts'];
		}
		return $return;
	}
}
FW_Option_Type_Builder::register_item_type('Page_Builder_Simple_Item');
