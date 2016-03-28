<?php if (!defined('FW')) die('Forbidden');

$cfg = array();

/**
 * Disable grid shortcodes correction
 *
 * - shortcode is auto wrapped in: section > column > shortcode
 * - column is auto wrapped in: section > column
 *
 * Set `true` To disable correction for all shortcodes
 *
 * To disable correction for specific shortcode,
 * set in `extensions/shortcodes/shortcodes/{shortcode_name}/config.php`
 *
 * $cfg['page_builder'] = array(
 *     'disable_correction' => true,
 *     ...
 * );
 */
$cfg['disable_correction'] = false;
