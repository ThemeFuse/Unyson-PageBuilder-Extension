<?php if (!defined('FW')) die('Forbidden');

$cfg = array();

/**
 * Disable grid shortcodes correction
 *
 * - shortcode is auto wrapped in: section > column > shortcode
 * - column is auto wrapped in: section > column
 *
 * true: To disable correction for all shortcodes
 * array('shortcode_name', ...): To disable correction for specific shortcodes // @since 1.4.2
 */
$cfg['disable_correction'] = false;
