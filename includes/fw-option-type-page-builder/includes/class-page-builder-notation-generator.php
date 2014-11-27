<?php if (!defined('FW')) die('Forbidden');

class _Page_Builder_Notation_Generator
{
	public function generate_notation($tag, $atts = array(), $content = null)
	{
		$atts_string = '';
		if (is_array($atts) && !empty($atts)) {
			$atts_string = ' ' . $this->get_atts_string($atts);
		}

		if ($content) {
			return "[{$tag}{$atts_string}]{$content}[/{$tag}]";
		} else {
			return "[{$tag}{$atts_string}]";
		}
	}

	private function get_atts_string($atts)
	{
		$atts_string  = '';
		$coder        = new _FW_Ext_Page_Builder_Shortcode_Atts_Coder();
		$encoded_atts = $coder->encode_atts($atts);

		foreach ($encoded_atts as $key => $value) {
			$atts_string .= $key . '="' . $value . '" ';
		}

		return substr($atts_string, 0, -1);
	}
}
