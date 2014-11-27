<?php if (!defined('FW')) die('Forbidden');

class FW_Option_Type_Page_Builder extends FW_Option_Type_Builder
{
	private $editor_integration_enabled = false;

	public function get_type()
	{
		return 'page-builder';
	}

	/**
	 * @internal
	 */
	protected function _get_defaults()
	{
		return array(
			'editor_integration' => false,
			'value'              => array(
				'json' => '[]'
			)
		);
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _render($id, $option, $data)
	{
		$static_uri = fw()->extensions->get('page-builder')->locate_URI('/includes/fw-option-type-page-builder/static/');
		$version = fw()->extensions->get('page-builder')->manifest->get_version();

		wp_enqueue_style(
			'fw-option-type-' . $this->get_type(),
			$static_uri . 'css/styles.css',
			array(),
			$version
		);

		/*
		 * there should not be (and it does not make sens to be)
		 * more than one page builder per page that is integrated
		 * with the default post content editor
		 * integration in the sens of inserting the button to activate/deactivate
		 * the builder, to replace the post content with the shortcode notation
		 */
		if ($this->editor_integration_enabled && $option['editor_integration'] === true) {
			trigger_error(
				__('There must not be more than one page Editor integrated with the wp post editor per page', 'fw'),
				E_USER_ERROR
			);
		} elseif ($option['editor_integration'] === true) {
			$this->editor_integration_enabled = true;
			wp_enqueue_style(
				'fw-option-type-' . $this->get_type() . '-editor-integration',
				$static_uri . 'css/editor_integration.css',
				array(),
				$version
			);
			wp_enqueue_script(
				'fw-option-type-' . $this->get_type() . '-editor-integration',
				$static_uri . 'js/editor_integration.js',
				array('jquery', 'fw-events'),
				$version,
				true
			);
			wp_localize_script(
				'fw-option-type-' . $this->get_type() . '-editor-integration',
				'fw_option_type_' . str_replace('-', '_', $this->get_type()) . '_editor_integration_data',
				array(
					'l10n'                => array(
						'showButton' => __('Visual Layout Editor', 'fw'),
						'hideButton' => __('Default Editor', 'fw'),
					),
					'optionId'            => $option['attr']['id'],
					'renderInBuilderMode' => isset($data['value']['builder_active']) ? $data['value']['builder_active'] : false
				)
			);
		}

		return parent::_render($id, $option, $data);
	}

	protected function item_type_is_valid($item_type_instance)
	{
		return is_subclass_of($item_type_instance, 'Page_Builder_Item');
	}

	/*
	 * Sorts the tabs so that the layout tab comes first
	 */
	protected function sort_thumbnails(&$thumbnails)
	{
		uksort($thumbnails, array($this, 'sort_thumbnails_helper'));
	}

	private function sort_thumbnails_helper($tab1, $tab2)
	{
		$layout_tab = __('Layout Elements', 'fw');
		if ($tab1 === $layout_tab) {
			return -1;
		} elseif ($tab2 === $layout_tab) {
			return 1;
		}

		return strcasecmp($tab1, $tab2);
	}

	/**
	 * @internal
	 */
	protected function _get_value_from_input($option, $input_value)
	{
		if (empty($input_value)) {
			$input_value = $option['value']['json'];
		}
		$items = json_decode($input_value, true);
		if (!$items) {
			$items = array();
		}

		$items_value           = $this->get_value_from_items($items);
		$corrector             = new _Page_Builder_Items_Corrector($this->get_item_types());
		$corrected_items_value = $corrector->correct($items_value);

		$value = array(
			'json'               => json_encode($items_value),
			'corrected_json'     => json_encode($corrected_items_value),
			'shortcode_notation' => $this->get_shortcode_notation($corrected_items_value)
		);
		if($option['editor_integration'] === true) {
			$value['builder_active'] = isset($_POST['page-builder-active']) && $_POST['page-builder-active'] === 'true';
		}

		return $value;
	}

	private function get_shortcode_notation($items)
	{
		/**
		 * @var Page_Builder_Item[] $registered_items
		 */
		$registered_items   = $this->get_item_types();
		$generator          = new _Page_Builder_Notation_Generator();
		$shortcode_notation = '';
		foreach ($items as &$item_attributes) {
			$item_type  = $item_attributes['type'];
			$item_items = !empty($item_attributes['_items']) ? $item_attributes['_items'] : null;
			if (isset($registered_items[$item_type])) {
				unset($item_attributes['type'], $item_attributes['_items']);
				$shortcode_data = $registered_items[$item_type]->get_shortcode_data($item_attributes);
				$tag     = $shortcode_data['tag'];
				$atts    = isset($shortcode_data['atts']) ? $shortcode_data['atts'] : array();
				$content = $item_items ? $this->get_shortcode_notation($item_items) : null;

				$shortcode_notation .= $generator->generate_notation($tag, $atts, $content);
			}
		}

		return $shortcode_notation;
	}
}

$path = dirname(__FILE__);
require $path . '/includes/item-types/class-page-builder-item.php';
require $path . '/includes/item-types/simple/class-page-builder-simple-item.php';
require $path . '/includes/items-corrector/class-page-builder-items-corrector.php';
require $path . '/includes/class-page-builder-notation-generator.php';

FW_Option_Type::register('FW_Option_Type_Page_Builder');
