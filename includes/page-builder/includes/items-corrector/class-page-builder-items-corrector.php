<?php if (!defined('FW')) die('Forbidden');

class _Page_Builder_Items_Corrector
{
	private $row_container;

	private $column_wrap ;
	private $row_wrap;
	private $section_wrap;

	private $items;

	public function __construct($item_types)
	{
		$this->row_container = new _Page_Builder_Items_Corrector_Row_Container();

		$this->column_wrap  = $item_types['column']->get_value_from_attributes(array(
			'_items' => array()
		));
		$this->row_wrap     = $item_types['row']->get_value_from_attributes(array(
			'_items' => array()
		));
		$this->section_wrap = $item_types['section']->get_value_from_attributes(array(
			'_items' => array()
		));
	}

	public function wrap_into_column($items, $data = array())
	{
		$wrapper = $this->column_wrap;
		$wrapper['_items'] = $items;

		return $wrapper;
	}

	public function wrap_into_row($items, $data = array())
	{
		$wrapper = $this->row_wrap;
		$wrapper['_items'] = $items;
		return $wrapper;
	}

	public function wrap_into_section($items, $data = array())
	{
		$wrapper = $this->section_wrap;
		$wrapper['_items'] = $items;

		if (isset($data['atts']) && is_array($data['atts'])) {
			$wrapper['atts'] = array_merge($wrapper['atts'], $data['atts']);
		}

		return $wrapper;
	}

	public function correct($items)
	{
		$this->items = $items;
		$this->correct_sections();
		$this->correct_root_items();

		return apply_filters('fw_ext_page-builder_items_correction_complete', $this->items,
			$this,
			$items // @since 1.3.9
		);
	}

	private function correct_sections()
	{
		foreach ($this->items as $index => &$item) {
			if ($item['type'] === 'section') {
				$item['atts']['auto_generated'] = false;
				if ($index === 0) {
					$item['atts']['first_in_builder'] = true;
				}

				$item['_items'] = $this->correct_section($item['_items']);
			}
		}
	}

	public function correct_section($section)
	{
		/**
		 * @var FW_Extension_Shortcodes $shortcodes_extension
		 */
		$shortcodes_extension = fw_ext('shortcodes');

		$fixed_section = array();
		for ($i = 0, $count = count($section); $i < $count; $i++) {
			switch ($section[$i]['type']) {
				case 'column':
					if (
						($shortcode_instance = $shortcodes_extension->get_shortcode('column'))
						&&
						$shortcode_instance->get_config('page_builder/disable_correction')
					) {
						$columns = array( $section[ $i ] );
						while ( isset( $section[ $i + 1 ] ) && $section[ $i + 1 ]['type'] === 'column' ) {
							$columns[] = $section[ ++$i ];
						}
						$fixed_section[] = $this->wrap_into_row( $columns );
					} else {
						$this->row_container->empty_container();
						$columns = array();

						do {
							if ( $this->row_container->add_column(
								apply_filters('fw:ext:page-builder:item-corrector:column-width', $section[ $i ]['width'], $section[ $i ])
							) ) {
								$columns[] = $section[ $i ];
							} else {
								$fixed_section[] = $this->wrap_into_row( $columns );

								$columns = array( $section[ $i ] );
								$this->row_container->empty_container();
								$this->row_container->add_column( $section[ $i ]['width'] );
							}
						} while ( isset( $section[ $i + 1 ] ) && $section[ $i + 1 ]['type'] === 'column' && ++$i );

						$fixed_section[] = $this->wrap_into_row( $columns );
					}
					break;

				case 'simple':
					if (
						($shortcode_instance = $shortcodes_extension->get_shortcode($section[$i]['shortcode']))
						&&
						$shortcode_instance->get_config('page_builder/disable_correction')
					) {
						$fixed_section[] = $section[$i];
					} else {
						$fixed_section[] = $this->wrap_into_row(
							array(
								$this->wrap_into_column(
									array( $section[ $i ] )
								)
							)
						);
					}
					break;

				// Page Builder custom item types
				default:
					$fixed_section[] = $this->wrap_into_row(
						array(
							$this->wrap_into_column(
								array($section[$i])
							)
						)
					);
					break;
					// TODO: determine some good way to handle custom item types
					// $fixed_section[] = apply_filters('fw_ext_page-builder_custom_item_section_correction', $section[$i], $this, $fixed_section);
			}
		}

		return $fixed_section;
	}

	private function correct_root_items()
	{
		/**
		 * @var FW_Extension_Shortcodes $shortcodes_extension
		 */
		$shortcodes_extension = fw_ext('shortcodes');

		$items = $this->items;
		$fixed_items = array();

		$auto_generated_section = array();
		for ($i = 0, $count = count($items); $i < $count; $i++) {
			if ($items[$i]['type'] === 'section') {
				if (!empty($auto_generated_section)) {
					$fixed_items[] = $this->wrap_into_section($auto_generated_section, array(
						'atts' => array(
							'auto_generated' => true
						)
					));
					$auto_generated_section = array();
				}

				$fixed_items[] = $items[$i];
			} else {
				switch ($items[$i]['type']) {
					case 'column':
						$columns   = array($items[$i]);
						$this->row_container->empty_container();
						$this->row_container->add_column($items[$i]['width']);
						while (isset($items[$i+1]) && $items[$i+1]['type'] === 'column') {
							$i++;
							if ($this->row_container->add_column($items[$i]['width'])) {
								$columns[] = $items[$i];
							} else {
								$auto_generated_section[] = $this->wrap_into_row($columns);

								$columns = array($items[$i]);
								$this->row_container->empty_container();
								$this->row_container->add_column($items[$i]['width']);
							}
						}
						$auto_generated_section[] = $this->wrap_into_row($columns);
						break;

					case 'simple':
						if (
							($shortcode_instance = $shortcodes_extension->get_shortcode($items[$i]['shortcode']))
							&&
							$shortcode_instance->get_config('page_builder/disable_correction')
						) {
							$fixed_items[] = $items[$i];
						} else {
							$auto_generated_section[] = $this->wrap_into_row(
								array(
									$this->wrap_into_column(
										array( $items[ $i ] )
									)
								)
							);

							while ( isset( $items[ $i + 1 ] ) && $items[ $i + 1 ]['type'] === 'simple' ) {
								if (
									($shortcode_instance = $shortcodes_extension->get_shortcode($items[$i + 1]['shortcode']))
									&&
									$shortcode_instance->get_config('page_builder/disable_correction')
								) {
									$fixed_items[]          = $this->wrap_into_section( $auto_generated_section, array(
										'atts' => array(
											'auto_generated' => true
										)
									) );
									$auto_generated_section = array();

									break;
								}

								++$i;
								$auto_generated_section[] = $this->wrap_into_row(
									array(
										$this->wrap_into_column(
											array( $items[ $i ] )
										)
									)
								);
							}
						}
						break;

					default:
						if (
							/** @since 1.6.14 */
							apply_filters(
								'fw-ext:page-builder:disable-builder-item-correction:'. $items[$i]['type'],
								false
							)
						) {
							$fixed_items[] = $items[$i];
						} elseif (
							/** @since 1.6.14 */
							$manually_corrected_item = apply_filters(
								'fw-ext:page-builder:manual-builder-item-correction:'. $items[$i]['type'],
								false,
								$items[$i],
								array(
									'correct_section' => array($this, 'correct_section'),
									'wrap_into_section' => array($this, 'wrap_into_section'),
									'wrap_into_row' => array($this, 'wrap_into_row'),
									'wrap_into_column' => array($this, 'wrap_into_column'),
								)
							)
						) {
							$fixed_items[] = $manually_corrected_item;
						} else {
							$auto_generated_section[] = $this->wrap_into_row(
								array(
									$this->wrap_into_column(
										array($items[$i])
									)
								)
							);
							while (isset($items[$i + 1]) && $items[$i + 1]['type'] === 'simple') {
								$i++;
								$auto_generated_section[] = $this->wrap_into_row(
									array(
										$this->wrap_into_column(
											array($items[$i])
										)
									)
								);
							}
						}
				}
			}
		}

		if (!empty($auto_generated_section)) {
			$fixed_items[] = $this->wrap_into_section($auto_generated_section, array(
				'atts' => array(
					'auto_generated' => true
				)
			));
		}

		$this->items = $fixed_items;
	}
}
