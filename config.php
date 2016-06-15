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

/**
 * By default Page Builder parses all columns from section and determine how many columns
 * will fit on one row and wraps them in one row, remaining columns will be parsed too and wrapped
 * in another rows. This system was made to be able to determine the first and last column in row,
 * useful thing for HTML grids also this boxes the row columns so next row columns will not ge stuck on
 * previous row columns.
 *
 * CSS framework like Bootstrap 3 doesn't need this feature, more then that it breaks the bootstrap functionality
 * when use such rules on columns <div class="col-md-4 col-sm-6"></div>
 *
 *
 */
$cfg['disable_columns_auto_wrap'] = false;