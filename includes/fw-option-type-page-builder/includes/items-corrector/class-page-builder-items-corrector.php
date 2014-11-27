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

	private function wrap_into_column($items)
	{
		$wrapper = $this->column_wrap;
		$wrapper['_items'] = $items;
		return $wrapper;
	}

	private function wrap_into_row($items)
	{
		$wrapper = $this->row_wrap;
		$wrapper['_items'] = $items;
		return $wrapper;
	}

	private function wrap_into_section($items)
	{
		$wrapper = $this->section_wrap;
		$wrapper['_items'] = $items;
		return $wrapper;
	}

	private function wrap_into_column_and_row($items)
	{
		$column = $this->wrap_into_column($items);
		$row    = $this->wrap_into_row(array($column));
		return $row;
	}

	private function wrap_into_column_and_row_and_section($items)
	{
		$column  = $this->wrap_into_column($items);
		$row     = $this->wrap_into_row(array($column));
		$section = $this->wrap_into_section(array($row));
		return $section;
	}

	private function wrap_into_row_and_section($items)
	{
		$row     = $this->wrap_into_row($items);
		$section = $this->wrap_into_section(array($row));
		return $section;
	}

	private $items;

	public function correct($items)
	{
		// TODO: think of some kind of filter to pass the corrected values through
		// TODO: think of the other item type, what to do with them
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
				$fixed_items[] = $this->wrap_into_column_and_row_and_section(array($items[$i]));
			} else {

			}
		}

		$this->items = $fixed_items;
	}
}

require dirname(__FILE__) . '/class-page-builder-items-corrector-row-container.php';