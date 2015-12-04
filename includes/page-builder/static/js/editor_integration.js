(function($, fwe, data) {
	var gui = {
		elements: {
			$showButton: $('<a href="#" class="button button-primary">' + data.l10n.showButton + '</a>'),
			$hideButton: $('<a href="#" class="button button-primary page-builder-hide-button">' + data.l10n.hideButton + '</a>'),
			$builderBox: $('#' + data.optionId).closest('.postbox'),
			$builderActiveHidden: $('<input name="page-builder-active" type="hidden">'),
			$wpPostBodyContent: $('#post-body-content'),
			$wpPostDivRich: $('#postdivrich'),
			$wpContentWrap: $('#wp-content-wrap'),
			$wpTemplatesSelect: $('select[name="page_template"]:first'),
		},
		events: _.extend({}, Backbone.Events),
		builderIsActive: function() {
			return this.elements.$builderActiveHidden.val() === 'true';
		},
		getWPEditorContent: function() {
			/*
			 * WordPress works with tinyMCE for its WYSIWYG editor
			 * depending on the current editor tab (visual or text)
			 * we need to ask tinyMCE to get the content (in the case of visual tab)
			 * of get the value from the #content textarea (in the case of text tab)
			 */
			if (this.elements.$wpContentWrap.hasClass('tmce-active') && tinyMCE.get('content')) {
				return tinyMCE.get('content').getContent();
			} else {
				return this.elements.$wpContentWrap.find('#content').val();
			}
		},
		clearWPEditorContent: function() {
			/*
			 * WordPress works with tinyMCE for its WYSIWYG editor
			 * depending on the current editor tab (visual or text)
			 * we need to clear tinyMCE instance (in the case of visual tab)
			 * of the value from the #content textarea (in the case of text tab)
			 */
			if (this.elements.$wpContentWrap.hasClass('tmce-active')) {
				return tinyMCE.get('content').setContent('');
			} else {
				return this.elements.$wpContentWrap.find('#content').val('');
			}
		},
		showBuilder: function() {
			this.elements.$wpPostBodyContent.addClass('page-builder-visible');

			this.elements.$wpPostDivRich.hide();
			this.elements.$hideButton.show();
			this.elements.$builderBox.show();

			window.editorExpand && window.editorExpand.off && window.editorExpand.off();

			// set the hidden to store that the builder is active
			this.elements.$builderActiveHidden.val('true');

			this.events.trigger('show');
		},
		hideBuilder: function() {
			this.elements.$wpPostBodyContent.removeClass('page-builder-visible');

			this.elements.$hideButton.hide();
			this.elements.$builderBox.hide();
			this.elements.$wpPostDivRich.show();

			window.editorExpand && window.editorExpand.on && window.editorExpand.on();

			// set the hidden to store that the builder is inactive
			this.elements.$builderActiveHidden.val('false');

			this.events.trigger('hide');
		},
		initButtons: function() {
			// insert the show button
			$('#wp-content-media-buttons').prepend(this.elements.$showButton);

			// insert the hide button
			this.elements.$wpPostDivRich.before(this.elements.$hideButton);

			if (data.renderInBuilderMode) {
				this.showBuilder();
			} else {
				this.hideBuilder();
			}
		},
		insertHidden: function() {
			/*
			 * whether or not to display the builder at render depends
			 * on a value that is stored in the $builderActiveHidden hidden input
			 */
			this.elements.$builderBox.prepend(this.elements.$builderActiveHidden);
		},
		bindEvents: function() {
			var self = this;

			this.elements.$showButton.on('click', function(e) {
				self.showBuilder();
				e.preventDefault();
			});
			this.elements.$hideButton.on('click', function(e) {
				self.hideBuilder();
				e.preventDefault();
			});
		},
		removeScreenOptionsCheckbox: function() {
			$('label[for="fw-options-box-page-builder-box-hide"]').remove();
		},
		fixOnFirstShowOrHide: function(isShow) {
			var initialStateIsShow = data.renderInBuilderMode;

			if (initialStateIsShow == isShow) {
				/**
				 * Do nothing, this happens when the same state is set again and again,
				 * for e.g. the builder is enabled/shown, but the this.showBuilder() is still called.
				 *
				 * We need to take an action when the state will be changed (shown->hidden or hidden->shown)
				 */
				return;
			}

			if (initialStateIsShow) {
				/*
				 * If the page has to render with the builder being active,
				 * clear the wp editor textarea because the user wants to write the content from scratch
				 */
				this.clearWPEditorContent();
				this.elements.$wpContentWrap.find('#content-tmce').trigger('click');
			} else {
				/*
				 * If the page has to render with wp editor active
				 * get the content from the wp editor textarea
				 * and create a text_block in the builder that contains that content
				 */
				var wpEditorContent = this.getWPEditorContent();
				if (wpEditorContent) {
					optionTypePageBuilder.initWithTextBlock(wpEditorContent);
				}
			}

			/**
			 * This method must be called only once
			 * Prevent call again
			 */
			this.fixOnFirstShowOrHide = function(){};
		},
		initTemplatesSelectSync: function() {
			if (!data.builderTemplates.length) {
				/**
				 * There are no templates that support page-builder.
				 * Do nothing, allow builder for all templates,
				 * there are themes that were created before this feature was added.
				 */
				return false;
			}

			if (!this.elements.$wpTemplatesSelect.length) {
				return false;
			}

			var onChange = _.bind(function(){
				if ($.inArray(this.elements.$wpTemplatesSelect.val(), data.builderTemplates) === -1) {
					this.hideBuilder();
				} else {
					this.showBuilder();
				}
			}, this);

			this.elements.$wpTemplatesSelect.on('change', onChange);

			fwe.one('fw-builder:' + 'page-builder' + ':register-items', function(){
				/**
				 * I don't know an event when tinyMCE.get('content') (used above) is available,
				 * calling it earlier will throw an error,
				 * calling it on a fixed timeout may be too early for slow browsers or internet connection when page is loaded slow.
				 * So check for its availability on an interval of time.
				 */
				var intervalId = setInterval(function(){
					if (
						typeof tinyMCE != 'undefined'
						&&
						tinyMCE.get('content')
					) {
						clearInterval(intervalId);
						onChange();
					}
				}, 30);
			});

			/**
			 * On builder show, make sure that a template that supports builder is selected in wp templates select
			 */
			this.events.on('show', _.bind(function () {
				this.elements.$wpTemplatesSelect.find('> option').each(function(){
					if ($.inArray($(this).attr('value'), data.builderTemplates) !== -1) {
						$(this).prop('selected', true);
						return false;
					}
				});
			}, this));
		},
		init: function() {
			this.initButtons();
			this.insertHidden();
			this.bindEvents();
			this.removeScreenOptionsCheckbox();
			this.initTemplatesSelectSync();

			// fixes on firs show or hide
			{
				this.events.once('show', _.bind(function(){
					this.fixOnFirstShowOrHide(true);
				}, this));

				this.events.once('hide', _.bind(function(){
					this.fixOnFirstShowOrHide(false);
				}, this));
			}
		}
	};

	gui.init(); // call this right away, earlier than document ready, else there will be glitches

	/*
	 * The global variable optionTypePageBuilder is created intentionally
	 * to allow creating a text_block shortcode when switching from
	 * the default editor into the visual one for the first time
	 */
	fwe.on('fw-builder:' + 'page-builder' + ':register-items', function(builder) {
		optionTypePageBuilder = builder;
		optionTypePageBuilder.initWithTextBlock = function(content) {
			this.rootItems.reset([
				{
					type: 'simple',
					shortcode: 'text_block',
					atts: {text: content}
				}
			]);
		}
	});
})(jQuery, fwEvents, fw_option_type_page_builder_editor_integration_data);
