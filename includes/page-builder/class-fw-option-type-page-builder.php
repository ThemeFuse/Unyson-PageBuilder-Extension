<?php if (!defined('FW')) die('Forbidden');

class FW_Option_Type_Page_Builder extends FW_Option_Type_Builder
{
	private $editor_integration_enabled = false;

	private $shortcode_visibility_property = 'fw-visibility';

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
			'editor_integration'  => false,
			'fixed_header'        => true,
			'compress_form_value' => true,
			'value'               => array(
				'json' => '[]',
				'builder_active' => false
			),
		);
	}

	/**
	 * @internal
	 * {@inheritdoc}
	 */
	protected function _enqueue_static( $id, $option, $data ) {
		parent::_enqueue_static($id, $option, $data);

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

			wp_enqueue_script(
				'fw-option-type-' . $this->get_type() . '-visual-elements',
				$static_uri . '/js/visual-elements.js',
				array(
					'jquery', 'fw-events',
					'fw-option-type-' . $this->get_type() . '-editor-integration'
				),
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
					'l10n' => array(
						'showButton' => __('Visual Page Builder', 'fw'),
						'hideButton' => __('Default Editor', 'fw'),
						'eye' => __('Hide / Show', 'fw'),
						'responsive' => __( 'Display Controls', 'fw' )
					),
					'visibility_key' => $this->shortcode_visibility_property,
					'optionId'            => $option['attr']['id'],
					'builderTemplates' => $builder_templates,
				)
			);
		}

		if ( apply_filters(
			'fw:ext:page-builder:modal-save-all',
			version_compare(fw()->manifest->get_version(), '2.6.14', '>=')
		) ) {
			wp_enqueue_script(
				$script_handle = 'fw-page-builder-modal-save-all',
				$static_uri .'/js/modal-save-all.js',
				array('fw'),
				fw()->manifest->get_version(),
				true
			);
			wp_localize_script(
				$script_handle,
				'_fw_page_builder_modal_save_all',
				array(
					'btn_text_suffix' => __(' All', 'fw'),
				)
			);
		}
	}

	public function _render($id, $option, $data)
	{
		if ($option['editor_integration'] === true) {
			if (isset($data['value']['builder_active'])
				? $data['value']['builder_active']
				: apply_filters('fw_page_builder_set_as_default', false)
			) {
				$option['attr']['data-builder-active'] = '~';
			}
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
		$value = parent::_get_value_from_input($option, $input_value);

		if (!is_null($input_value) && $_POST) {
			$value['builder_active'] = (
				$option['editor_integration'] === true &&
				$_POST &&
				isset($_POST['page-builder-active']) &&
				$_POST['page-builder-active'] === 'true'
			);
		} else {
			$value['builder_active'] = $option['value']['builder_active'];
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

	/**
	 * @param $json
	 * @return bool|string
	 * @since 1.6.2
	 */
	public function json_to_shortcodes($json) {
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
		if (
			/**
			 * @since 1.6.14
			 */
			apply_filters(
				'fw:ext:page-builder:json-structure-needs-correction',
				$this->needs_correction(),
				$this->get_item_types()
			)
		) {
			$corrector             = new _Page_Builder_Items_Corrector($this->get_item_types());
			$corrected_items_value = $corrector->correct($items_value);

			/**
			 * @since 1.6.14
			 */
			$corrected_items_value = apply_filters(
				'fw:ext:page-builder:json-structure-correction',
				$corrected_items_value,
				$this->get_item_types()
			);

			/**
			 * @since 1.6.14
			 */
			$corrected_items_value = apply_filters(
				'fw:ext:page-builder:json-structure-correction:complete',
				$corrected_items_value,
				$this->get_item_types()
			);

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

	/**
	 * https://github.com/ThemeFuse/Unyson-Builder-Extension/blob/v1.2.3/includes/option-types/builder/extends/class-fw-option-type-builder.php#L597
	 */
	protected function storage_load_recursive(array $items, array $params) {
		$item_types = $this->get_item_types();

		foreach ($items as &$atts) {
			if ( ! fw_akg( $this->shortcode_visibility_property, $atts, true ) && ! is_admin() ) {
				// Hide shortcode JSON only in front-end.
				$atts = array();
				continue;
			}

			if (!isset($atts['type']) || !isset($item_types[ $atts['type'] ])) {
				continue; // invalid item
			}

			$atts = $item_types[ $atts['type'] ]->storage_load($atts, $params);

			if (isset($atts['_items'])) {
				$atts['_items'] = $this->storage_load_recursive($atts['_items'], $params);
			}
		}

		return $items;
	}
}
