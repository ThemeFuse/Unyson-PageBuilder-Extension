<?php if (!defined('FW')) die('Forbidden');

class _Page_Builder_Items_Corrector
{
	private $row_container;

	private $column_wrap ;
	private $row_wrap;
	private $section_wrap;

	public function __construct($item_types)
	{
		$this->row_container = new _Page_Builder_Items_Corrector_Row_Container();

		$this->column_wrap  = $item_types['column']->get_value_from_attributes(array(
			'width'  => '1_1',
			'_items' => array()
		));
		$this->row_wrap     = $item_types['row']->get_value_from_attributes(array(
			'_items' => array()
		));
		$this->section_wrap = $item_types['section']->get_value_from_attributes(array(
			'_items' => array()
		));
	}

	private function wrap_into_column($items, $data = array())
	{
		$wrapper = $this->column_wrap;
		$wrapper['_items'] = $items;
		return $wrapper;
	}

	private function wrap_into_row($items, $data = array())
	{
		$wrapper = $this->row_wrap;
		$wrapper['_items'] = $items;
		return $wrapper;
	}

	private function wrap_into_section($items, $data = array())
	{
		$wrapper = $this->section_wrap;
		$wrapper['_items'] = $items;

		if (isset($data['fullwidth']) && $data['fullwidth']) {
			$wrapper['atts']['fullwidth'] = true;
		}

		return $wrapper;
	}

	private function wrap_into_column_and_row($items, $data = array())
	{
		$column = $this->wrap_into_column($items, $data);
		$row    = $this->wrap_into_row(array($column), $data);
		return $row;
	}

	private function wrap_into_column_and_row_and_section($items, $data = array())
	{
		$column  = $this->wrap_into_column($items, $data);
		$row     = $this->wrap_into_row(array($column), $data);
		$section = $this->wrap_into_section(array($row), $data);
		return $section;
	}

	private function wrap_into_row_and_section($items, $data = array())
	{
		$row     = $this->wrap_into_row($items, $data);
		$section = $this->wrap_into_section(array($row), $data);
		return $section;
	}

	private $items;

	public function correct($items)
	{
		$this->items = $items;
		$this->correct_sections();
		$this->correct_root_items();

		return $this->items;
	}

	private function correct_sections()
	{
		foreach ($this->items as &$item) {
			if ($item['type'] === 'section') {
				$item['_items'] = $this->correct_section($item['_items']);
			}
		}
	}

	private function correct_section($section)
	{
		$fixed_section = array();
		for ($i = 0, $count = count($section); $i < $count; $i++) {
			if ($section[$i]['type'] === 'column') {
				$rows    = array();
				$columns = array($section[$i]);
				$this->row_container->empty_container();
				$this->row_container->add_column($section[$i]['width']);
				while (isset($section[$i+1]) && $section[$i+1]['type'] === 'column') {
					$i++;
					if ($this->row_container->add_column($section[$i]['width'])) {
						$columns[] = $section[$i];
					} else {
						$rows[]  = $columns;
						$columns = array($section[$i]);
						$this->row_container->empty_container();
						$this->row_container->add_column($section[$i]['width']);
					}
				}
				$rows[] = $columns;

				foreach ($rows as $row) {
					$fixed_section[] = $this->wrap_into_row($row);
				}
			} else if ($section[$i]['type'] === 'simple') {
				$fixed_section[] = $this->wrap_into_column_and_row(array($section[$i]));
			} else {

			}
		}

		return $fixed_section;
	}

	private function correct_root_items()
	{
		$shortcodes_extension = fw_ext('shortcodes');
		$items = $this->items;
		$fixed_items = array();
		for ($i = 0, $count = count($items); $i < $count; $i++) {
			if ($items[$i]['type'] === 'section') {
				$fixed_items[] = $items[$i];
			} else if ($items[$i]['type'] === 'column') {
				$rows      = array();
				$columns   = array($items[$i]);
				$this->row_container->empty_container();
				$this->row_container->add_column($items[$i]['width']);
				while (isset($items[$i+1]) && $items[$i+1]['type'] === 'column') {
					$i++;
					if ($this->row_container->add_column($items[$i]['width'])) {
						$columns[] = $items[$i];
					} else {
						$rows[]  = $columns;
						$columns = array($items[$i]);
						$this->row_container->empty_container();
						$this->row_container->add_column($items[$i]['width']);
					}
				}
				$rows[] = $columns;

				$wrapped_rows = array();
				foreach ($rows as $row) {
					$wrapped_rows[] = $this->wrap_into_row($row);
				}
				$fixed_items[] = $this->wrap_into_section($wrapped_rows);
			} else if ($items[$i]['type'] === 'simple') {

				/*
				 * We need to determine if the simple shortcode is
				 * fullwidth, and generate a section with a
				 * corresponding flag if so (this may be used for a fluid container)
				 */
				$section_data = array();
				$shortcode    = $shortcodes_extension->get_shortcode($items[$i]['shortcode']);
				$is_fullwidth = $shortcode->get_config('page_builder/fullwidth');
				if ($is_fullwidth) {
					$section_data['fullwidth'] = true;
				}

				$fixed_items[] = $this->wrap_into_column_and_row_and_section(array($items[$i]), $section_data);
			} else {

			}
		}

		$this->items = $fixed_items;
	}
}

require dirname(__FILE__) . '/class-page-builder-items-corrector-row-container.php';