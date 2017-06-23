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
$manifest['github_repo'] = 'https://github.com/ThemeFuse/Unyson-PageBuilder-Extension';
$manifest['uri'] = 'http://manual.unyson.io/en/latest/extension/builder/index.html;';
$manifest['author'] = 'ThemeFuse';
$manifest['author_uri'] = 'http://themefuse.com/';
$manifest['version']       = '1.6.14';
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
		'shortcodes' => array(
			'min_version' => '1.3.21', // was added the get_builder_data() method
		),
	),
);

$manifest['github_update'] = 'ThemeFuse/Unyson-PageBuilder-Extension';
