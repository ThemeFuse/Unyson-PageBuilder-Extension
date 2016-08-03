(function($, itemData) {
    var responsive_class = 'fw-responsive-controls',
	display_class = 'fw-display-controls',
	allowed_width = 280;
    
    $('.fw-option-type-page-builder input[type=hidden]:first').on('change', function() {
	calculateSize();
    });

    $(window).resize(function() {
	calculateSize();
    });

    /**
     * Add responsive-class if width is less than allowed.
     */
    function calculateSize() {
	$('.fw-option-type-page-builder .builder-root-items .pb-item').each(function() {
	    var element = jQuery(this);

	    if ( allowed_width >= element.width() ) {
		element.addClass( responsive_class );
	    } else {
		element.removeClass( responsive_class );
		element.removeClass( display_class );
	    }
	});
    }
	
    /**
     * Add icon to elements.
     * @param {Object} data
     */
    function addIcon( data ) {
	data.$controls.prepend(
	    $('<i class="fw-responsive-button dashicons dashicons-menu"></i>')
	    .attr('data-hover-tip', itemData.l10n.responsive)
	    .on('click', function(e) {
		e.stopPropagation();
		e.preventDefault();
		
		var parent = $(this).closest('.pb-item');
		if ( parent.hasClass( responsive_class ) ) {
		    if ( parent.hasClass( display_class ) ) {
			parent.removeClass( display_class );
		    } else {
			parent.addClass( display_class );
		    }
		}
	    })
	);
    }
    
    // Add controls for simple shortcodes.
    fwEvents.on('fw:page-builder:shortcode:item-simple:controls', function( data ) {
	addIcon( data );
    });
    
    // Add controls for sections.
    fwEvents.on('fw:page-builder:shortcode:section:controls', function(data) {
	addIcon( data );
    });

    // Add controls for columns.
    fwEvents.on('fw:page-builder:shortcode:column:controls', function(data) {
	addIcon( data );
    });
    
    // Add controls for contact form.
    fwEvents.on('fw:page-builder:shortcode:contact-form:controls', function(data) {
	addIcon( data );
    });
	
})(jQuery, _fw_option_type_page_builder_shortcodes_controls);