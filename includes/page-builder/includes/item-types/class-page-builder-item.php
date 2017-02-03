<?php if (!defined('FW')) die('Forbidden');

abstract class Page_Builder_Item extends FW_Option_Type_Builder_Item
{
	private $builder_type = 'page-builder';

	final public function get_builder_type()
	{
		return $this->builder_type;
	}

	final public function get_thumbnails()
	{
		$data = $this->get_thumbnails_data();
		$thumbs = array();

		foreach ($data as $item) {
			$item = array_merge(
				array(
					'tab'           => '~',
					'title'         => '',
					'description'   => '',
				),
				$item
			);
			$data_str = '';
			if (!empty($item['data']) && is_array($item['data'])) {
				foreach ($item['data'] as $key => $value) {
					$data_str .= 'data-'. esc_attr($key) .'="'. esc_attr($value) .'" ';
				}
				$data_str = substr($data_str, 0, -1);
			}

			$hover_tooltip = $item['description'] ? 'data-hover-tip="'. esc_attr($item['description']) .'"' : '';
			$inner_classes = 'no-image';
			$image_html    = '';

			if (isset($item['image'])) {
				// convert old key to new
				$item['icon'] = $item['image'];
				unset($item['image']);
			}

			if (isset($item['icon'])) {
				$inner_classes = '';
				if (version_compare(fw_ext('builder')->manifest->get_version(), '1.1.12', '<')) {
					$image_html = fw_html_tag('img', array('src' => $item['icon']));
				} else {
					$image_html = fw_ext_builder_string_to_icon_html($item['icon']);
				}
			}

			if (isset($item['data']['shortcode'])) {
				$inner_classes .= ' fw-page-builder-thumb-shortcode--'. esc_attr($item['data']['shortcode']);
			}
			if (isset($item['data']['width'])) { // allows to style a specific column width
				$inner_classes .= ' fw-page-builder-thumb-width--'. esc_attr($item['data']['width']);
			}

			if ( ! isset( $thumbs[ $item['title'] ] ) ) {
				$thumbs[$item['title']] = array(
					'tab'  => $item['tab'],
					'html' => "<div class='inner {$inner_classes}' {$hover_tooltip}>" .
					          $image_html .
					          "<p><span>{$item['title']}</span></p>" .
					          "<span class='item-data' {$data_str}></span>" .
					          '</div>'
				);
			} else {
				$thumbs[] = array(
					'tab'  => $item['tab'],
					'html' => "<div class='inner {$inner_classes}' {$hover_tooltip}>" .
					          $image_html .
					          "<p><span>{$item['title']}</span></p>" .
					          "<span class='item-data' {$data_str}></span>" .
					          '</div>'
				);
			}
		}
		
		/**
		 * @since 1.6.8
		 */
		$thumbs = apply_filters( 'fw_page_builder_thumbs_before_display', $thumbs );

		return $thumbs;
	}

	/**
	 * Returns data from which one or more builder thumbnails will be built
	 *
	 * @return array(
	 *
	 *  // each array represents one builder thumbnail
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
	 *
	 * )
	 */
	abstract protected function get_thumbnails_data();

	/**
	 * Returns an array of data from which the shortcode notation will be built
	 *
	 * The array must have a 'tag' key that will serve as the shortcode tag
	 * and an 'atts' key from which will be generated the shortcode attributes
	 *
	 * @param $atts array The attributes from the builder
	 * @return array An well structured array like:
	 * array(
	 *    'tag'  => 'button'
	 *    'atts' => array(
	 *          'size' => 'large',
	 *          'type' => 'primary'
	 *     )
	 * )
	 */
	public function get_shortcode_data($atts = array())
	{
		unset($atts['type'], $atts['_items']);
		return array(
			'tag'  => $this->get_type(),
			'atts' => $atts
		);
	}

	/**
	 * {@inheritdoc}
	 * @since 1.5.1
	 */
	protected function _storage_save(array $item, array $params) {
		/** @var FW_Shortcode $shortcode */
		if (
			($shortcode = fw_ext('shortcodes')->get_shortcode(
				isset($item['shortcode']) ? $item['shortcode'] : str_replace('-', '_', $item['type'])
			))
			&&
			($shortcode_options = $shortcode->get_options())
		) {
			foreach (fw_extract_only_options($shortcode_options) as $id => $option) {
				$item['atts'][ $id ] = fw()->backend->option_type($option['type'])->storage_save(
					$id, $option, $item['atts'][ $id ], $params
				);
			}
		}

		return $item;
	}

	/**
	 * {@inheritdoc}
	 * @since 1.5.1
	 */
	protected function _storage_load(array $item, array $params) {
		/** @var FW_Shortcode $shortcode */
		if (
			($shortcode = fw_ext('shortcodes')->get_shortcode(
				isset($item['shortcode']) ? $item['shortcode'] : str_replace('-', '_', $item['type'])
			))
			&&
			($shortcode_options = $shortcode->get_options())
		) {
			foreach (fw_extract_only_options($shortcode_options) as $id => $option) {
				$item['atts'][ $id ] = fw()->backend->option_type($option['type'])->storage_load(
					$id, $option, isset($item['atts'][ $id ]) ? $item['atts'][ $id ] : null, $params
				);
			}
		}

		return $item;
	}
}
