(function ($, itemData) {
	var visibleIcon = 'dashicons-visibility',
		hiddenIcon = 'dashicons-hidden';

	/**
	 * Change model visibility attribute and shortcode class.
	 * @param {Object} model
	 */
	function visibilitySettings(model) {
		var visibility = model.get(itemData.key);

		if (visibility || 'undefined' === typeof( visibility )) {
			model.view.$el.addClass('fw-visibility-off');
			model.set(itemData.key, false);
		} else {
			model.view.$el.removeClass('fw-visibility-off');
			model.set(itemData.key, true);
		}
	}

	/**
	 * Change dashicons and shortcode visibility class.
	 * @param {Object} model
	 */
	function displayIcon(model) {
		var visibility = model.get(itemData.key);
		var icon = model.view.$el.find('.fw-shortcode-visibility:first');

		if (visibility || 'undefined' === typeof( visibility )) {
			model.view.$el.removeClass('fw-visibility-off');
			icon.removeClass(hiddenIcon);
			icon.addClass(visibleIcon);
		} else {
			model.view.$el.addClass('fw-visibility-off');
			icon.removeClass(visibleIcon);
			icon.addClass(hiddenIcon);
		}
	}

	/**
	 * Add eye icon to elements.
	 * @param {Object} data
	 */
	function addIcon(data) {
		data.$controls.prepend(
			$('<i class="fw-shortcode-visibility dashicons dashicons-visibility"></i>')
				.attr('data-hover-tip', itemData.l10n.eye)
				.on('click', function (e) {
					e.stopPropagation();
					e.preventDefault();

					visibilitySettings(data.model);
				})
		);

		displayIcon(data.model)
	}

	// Add controls for simple shortcodes.
	fwEvents.on('fw:page-builder:shortcode:item-simple:controls', function (data) {
		addIcon(data);
	});

	// Add controls for sections.
	fwEvents.on('fw:page-builder:shortcode:section:controls', function (data) {
		addIcon(data);
	});

	// Add controls for columns.
	fwEvents.on('fw:page-builder:shortcode:column:controls', function (data) {
		addIcon(data);
	});

	// Add controls for contact form.
	fwEvents.on('fw:page-builder:shortcode:contact-form:controls', function (data) {
		addIcon(data);
	});

})(jQuery, _fw_option_type_page_builder_shortcodes_controls);
