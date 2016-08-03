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
        var item = element.closest('.builder-item');
        if ( allowed_width >= element.width() ) {
        item.addClass( responsive_class );
        } else {
        item.removeClass( responsive_class );
        item.removeClass( display_class );
        }
        
        var controls = element.find( '.controls:first' );
        controls.mouseenter( function() {
        item.addClass( display_class );
        } );
        
        controls.mouseleave( function() {
        item.removeClass( display_class );
        } );
        
        
    });
    }
    
    /**
     * Add icon to elements.
     * @param {Object} data
     */
    function addIcon( data ) {
    data.$controls.append(
        $('<i class="fw-responsive-button dashicons dashicons-menu"></i>')
        .attr('data-hover-tip', itemData.l10n.responsive)
        .on('click', function(e) {
        e.stopPropagation();
        e.preventDefault();
        })
    );
    }
    
    fwEvents.on('fw-builder:page-builder:items-loaded', function() {
    setTimeout( _.partial( calculateSize ), 250 );
    });
    
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