<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}

function _fw_ext_page_builder_autoload( $class ) {

	switch ( $class ) {
		case 'FW_Option_Type_Page_Builder' :
			require_once dirname( __FILE__ ) . '/page-builder/class-fw-option-type-page-builder.php';
			break;
		case 'FW_Option_Storage_Type_Post_Meta_Page_Builder' :
			require_once dirname( __FILE__ ) . '/page-builder/includes/option-storage/class-fw-option-storage-type-post-meta-page-builder.php';
			break;
		case 'Page_Builder_Item' :
			require_once dirname( __FILE__ ) . '/page-builder/includes/item-types/class-page-builder-item.php';
			break;
		case 'Page_Builder_Simple_Item' :
			require_once dirname( __FILE__ ) . '/page-builder/includes/item-types/simple/class-page-builder-simple-item.php';
			break;
		case '_Page_Builder_Items_Corrector' :
			require_once dirname( __FILE__ ) . '/page-builder/includes/items-corrector/class-page-builder-items-corrector.php';
			break;
		case '_Page_Builder_Notation_Generator' :
			require_once dirname( __FILE__ ) . '/page-builder/includes/class-page-builder-notation-generator.php';
			break;
		case '_Page_Builder_Items_Corrector_Row_Container' :
			require dirname( __FILE__ ) . '/page-builder/includes/items-corrector/class-page-builder-items-corrector-row-container.php';
			break;
		case '_Page_Builder_Items_Corrector_Fraction' :
			require dirname( __FILE__ ) . '/page-builder/includes/items-corrector/class-page-builder-items-corrector-fraction.php';
			break;
	}
}

spl_autoload_register( '_fw_ext_page_builder_autoload' );