(function ($, itemData) {
	var visibleIcon = 'dashicons-visibility',
		hiddenIcon = 'dashicons-hidden';

	var responsive_class = 'fw-responsive-controls',
		display_class = 'fw-display-controls',
		allowed_width = 280;

	$('.fw-option-type-page-builder input[type=hidden]:first').on(
		'change',
		calculateSize
	);

	$(window).resize(calculateSize);

	/**
	 * Add responsive-class if width is less than allowed.
	 */
	function calculateSize() {
		$('.fw-option-type-page-builder .builder-root-items .pb-item').each(function () {
			var element = jQuery(this);
			var item = element.closest('.builder-item');
			if (allowed_width >= element.width()) {
				item.addClass(responsive_class);
			} else {
				item.removeClass(responsive_class);
				item.removeClass(display_class);
			}

			var controls = element.find('.controls:first');

			controls.mouseenter(function () {
				item.addClass(display_class);
			});

			controls.mouseleave(function () {
				item.removeClass(display_class);
			});
		});
	}

	/**
	 * Change model visibility attribute and shortcode class.
	 * @param {Object} model
	 */
	function visibilitySettings(model) {
		var visibility = model.get(itemData.visibility_key);

		if (visibility || 'undefined' === typeof( visibility )) {
			model.view.$el.addClass('fw-visibility-off');
			model.set(itemData.visibility_key, false);
		} else {
			model.view.$el.removeClass('fw-visibility-off');
			model.set(itemData.visibility_key, true);
		}
	}

	/**
	 * Change dashicons and shortcode visibility class.
	 * @param {Object} model
	 */
	function displayIcon(model) {
		var visibility = model.get(itemData.visibility_key);
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

		data.$controls.append(
			$('<i class="fw-responsive-button dashicons dashicons-menu"></i>')
				.attr('data-hover-tip', itemData.l10n.responsive)
				.on('click', function (e) {
					e.stopPropagation();
					e.preventDefault();
				})
		);

		displayIcon(data.model)
	}

	fwEvents.on('fw-builder:page-builder:items-loaded', function () {
		setTimeout(_.partial(calculateSize), 250);
	});

	fwEvents.on([
		'fw:page-builder:shortcode:item-simple:controls',
		'fw:page-builder:shortcode:section:controls',
		'fw:page-builder:shortcode:column:controls',
		'fw:page-builder:shortcode:innercolumn:controls',
		'fw:page-builder:shortcode:contact-form:controls'
	].join(' '), function (data) {
		addIcon(data);
	})

})(jQuery, fw_option_type_page_builder_editor_integration_data);
