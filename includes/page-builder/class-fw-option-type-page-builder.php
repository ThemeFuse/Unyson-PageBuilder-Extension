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
			'fixed_header'       => true,
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
		$static_uri = fw()->extensions->get('page-builder')->get_uri('/includes/page-builder/static');
		$version = fw()->extensions->get('page-builder')->manifest->get_version();

		wp_enqueue_style(
			'fw-option-type-' . $this->get_type(),
			$static_uri . '/css/styles.css',
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
			wp_enqueue_style(
				'fw-option-type-' . $this->get_type() . '-editor-integration',
				$static_uri . '/css/editor_integration.css',
				array(),
				$version
			);

			wp_enqueue_script(
				'fw-option-type-' . $this->get_type() . '-editor-integration',
				$static_uri . '/js/editor_integration.js',
				array('jquery', 'fw-events'),
				$version,
				true
			);

			{
				if (!$this->editor_integration_enabled) { // first time and only one time
					add_filter('tiny_mce_before_init', array($this, '_filter_disable_editor'), 10, 2);

					/**
					 * Hide the Publish button until the builder is not fully initialized in js
					 * Fixes https://github.com/ThemeFuse/Unyson/issues/1542#issuecomment-218094104
					 */
					wp_add_inline_style(
						'fw-option-type-' . $this->get_type() . '-editor-integration',
						'#publish { display: none; }'
					);
				}

				$this->editor_integration_enabled = true;
			}

			{
				$builder_templates = apply_filters('fw_ext_page_builder_templates', array(
					// 'template-file.php', 'dir/template-file.php' // these needs to match http://bit.ly/1LAMfjN
				));

				// remove not existing templates
				$builder_templates = array_intersect(
					array_keys(wp_get_theme()->get_page_templates()),
					$builder_templates
				);

				/**
				 * Make sure the array is not associative array('template.php', ...)
				 * instead of array(2 => 'template.php', ...)
				 * because the json needs to be ['template.php', ...] instead of {2: 'template.php', ...}
				 */
				$builder_templates = array_values($builder_templates);
			}

			wp_localize_script(
				'fw-option-type-' . $this->get_type() . '-editor-integration',
				'fw_option_type_' . str_replace('-', '_', $this->get_type()) . '_editor_integration_data',
				array(
					'l10n'                => array(
						'showButton' => __('Visual Page Builder', 'fw'),
						'hideButton' => __('Default Editor', 'fw'),
					),
					'optionId'            => $option['attr']['id'],
					'renderInBuilderMode' => isset($data['value']['builder_active'])
						? $data['value']['builder_active']
						: apply_filters( 'fw_page_builder_set_as_default', false ),
					'builderTemplates' => $builder_templates,
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

		if (!($items = json_decode($input_value, true))) {
			$items = array();
		}

		$value = array(
			'json' => json_encode($this->get_value_from_items($items)),
		);

		if ($option['editor_integration'] === true) {
			$value['builder_active'] = isset($_POST['page-builder-active']) && $_POST['page-builder-active'] === 'true';
		}

		return $value;
	}

	/**
	 * The correction should be done only when
	 * all the necessary item types exist and
	 * an explicit config disable setting isn't set
	 */
	private function needs_correction()
	{
		$item_types = $this->get_item_types();
		$page_builder_extension = fw_ext('page-builder');

		$disable_correction = (
				/**
				 * @since 1.3.9
				 */
				$page_builder_extension->get_config('disable_correction') === true
				||
				/**
				 * This has a typo 'disalbe'
				 * but it can't be removed because it was here for a very long time
				 * and some developers may have been using it.
				 */
				$page_builder_extension->get_config('disalbe_correction') === true
			)
			|| !isset($item_types['section'])
			|| !isset($item_types['row'])
			|| !isset($item_types['column']);

		return !$disable_correction;
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

	public function _get_shortcode_notation(FW_Access_Key $access_key, $json) {
		if ($access_key->get_key() !== 'fw:ext:page-builder') {
			trigger_error('Call denied', E_USER_ERROR);
		}

		if ( !is_array($json) && is_null($json = json_decode($json, true)) ) {
			return false;
		}

		$items_value = $this->get_value_from_items($json);

		/**
		 * Correction means that if someone drags a simple shortcode
		 * into the canvas area of the builder, it will be wrapped
		 * into a column, then a row and finally a section.
		 * This is done to ensure that a correct grid
		 * will be displayed on the frontend.
		 */
		if ($this->needs_correction()) {
			$corrector             = new _Page_Builder_Items_Corrector($this->get_item_types());
			$corrected_items_value = $corrector->correct($items_value);

			return $this->get_shortcode_notation($corrected_items_value);
		} else {
			return $this->get_shortcode_notation($items_value);
		}
	}

	/**
	 * Disable default editor init, it will be initialized manually from js
	 * @param array $mceInit
	 * @param string $editor_id
	 * @return array
	 * @internal
	 */
	public function _filter_disable_editor($mceInit, $editor_id){
		if ('content' === $editor_id) {
			$mceInit['wp_skip_init'] = true;
		}

		return $mceInit;
	}
}

$path = dirname(__FILE__);
require $path . '/includes/item-types/class-page-builder-item.php';
require $path . '/includes/item-types/simple/class-page-builder-simple-item.php';
require $path . '/includes/items-corrector/class-page-builder-items-corrector.php';
require $path . '/includes/class-page-builder-notation-generator.php';

FW_Option_Type::register('FW_Option_Type_Page_Builder');
