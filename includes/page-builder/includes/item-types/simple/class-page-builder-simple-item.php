<?php if (!defined('FW')) die('Forbidden');

class Page_Builder_Simple_Item extends Page_Builder_Item
{
	private $type = 'simple';

	public function get_type()
	{
		return $this->type;
	}

	public function enqueue_static()
	{
		$static_uri = fw()->extensions->get('page-builder')->get_uri(
			'/includes/page-builder/includes/item-types/simple/static'
		);

		$version = fw()->extensions->get('page-builder')->manifest->get_version();

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

		{
			wp_localize_script(
				$this->get_builder_type() . '_item_type_' . $this->get_type(),
				str_replace('-', '_', $this->get_builder_type()) . '_item_type_' . $this->get_type() . '_data',
				$builder_data = fw_ext('shortcodes')->get_builder_data()
			);

			foreach ($builder_data as $tag => $item_data) {
				if (!empty($item_data['options'])) {
					fw()->backend->enqueue_options_static($item_data['options']);
				}
			}

			unset($builder_data);
		}

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
		$data = fw_ext('shortcodes')->get_builder_data();
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

		/**
		 * @var FW_Extension_Shortcodes $shortcodes_ext
		 */
		$shortcodes_ext = fw_ext('shortcodes');

		if (
			($shortcode_data = $shortcodes_ext->get_shortcode_builder_data($attributes['shortcode']))
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
