<?php if (!defined('FW')) die('Forbidden');

/**
 * @deprecated Since Shortcodes 1.3.0
 */
class _FW_Ext_Page_Builder_Shortcode_Atts_Coder
{
	public function encode_atts($atts)
	{
		$encoded    = array();
		$array_keys = array();
		foreach ($atts as $key => $value) {

			// the WordPress shortcode parser doesn't work when
			// using attributes with dashes
			$transformed_key = str_replace('-', '_', $key);

			$value_to_encode = $value;
			if (is_array($value)) {
				$value_to_encode = json_encode($value);
				$array_keys[$transformed_key] = $transformed_key;
			}

			$encoded[$transformed_key] = $this->encode_value($value_to_encode);
		}

		if (!empty($array_keys)) {
			$encoded['_array_keys'] = $this->encode_value(json_encode($array_keys));
		}

		$encoded['_made_with_builder'] = 'true';

		return $encoded;
	}

	private function encode_value($value)
	{
		$str = htmlentities($value, ENT_QUOTES, 'UTF-8');

		// http://www.degraeve.com/reference/specialcharacters.php
		$special = array(
			// fixes http://bit.ly/1HoHVhl
			'['  => '&#91;',
			']'  => '&#93;',

			// fixes http://bit.ly/1J887Om
			"\r\n" => '&#010;',
		);
		return str_replace(array_keys($special), array_values($special), $str);
	}

	public function decode_atts($atts)
	{
		if (isset($atts['_made_with_builder'])) {

			unset($atts['_made_with_builder']);

			$array_keys = array();
			if (isset($atts['_array_keys'])) {
				$array_keys = json_decode($this->decode_value($atts['_array_keys']), true);
				unset($atts['_array_keys']);
			}

			$decoded = array();
			foreach ($atts as $key => $value) {
				$decoded[$key] = isset($array_keys[$key])
									? json_decode($this->decode_value($value), true)
									: $this->decode_value($value);
			}

			return $decoded;
		} else {
			return $atts;
		}
	}

	private function decode_value($encoded_value)
	{
		return html_entity_decode($encoded_value, ENT_QUOTES, 'UTF-8');
	}
}
