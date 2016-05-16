<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

$manifest = array();

$manifest['name']          = __( 'Page Builder', 'fw' );
$manifest['description']   = __(
	'Lets you easily build countless pages with the help of the drag and drop visual page builder'
	.' that comes with a lot of already created shortcodes.',
	'fw'
);
$manifest['version']       = '1.5.4';
$manifest['display']       = true;
$manifest['standalone']    = true;
$manifest['requirements']  = array(
	'framework' => array(
		/**
		 * In that version was solved the bug with children extension requirements when activate an extension
		 */
		'min_version' => '2.1.18',
	),
	'extensions' => array(
		'builder' => array(),
		'forms' => array(),
	),
);

$manifest['github_update'] = 'ThemeFuse/Unyson-PageBuilder-Extension';
