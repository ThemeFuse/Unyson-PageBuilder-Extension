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
					$data_str .= "data-$key='$value' ";
				}
				$data_str = substr($data_str, 0, -1);
			}

			$hover_tooltip = $item['description'] ? "data-hover-tip='{$item['description']}'" : '';
			$inner_classes = 'no-image';
			$image_html    = '';
			if (isset($item['image'])) {
				$inner_classes = '';
				$image_html    = "<img src='{$item['image']}' />";
			}
			$thumbs[] = array(
				'tab'  => $item['tab'],
				'html' => "<div class='inner {$inner_classes}' {$hover_tooltip}>" .
								$image_html .
								"<p><span>{$item['title']}</span></p>" .
								"<span class='item-data' {$data_str}></span>" .
							'</div>'
			);
		}
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
}
