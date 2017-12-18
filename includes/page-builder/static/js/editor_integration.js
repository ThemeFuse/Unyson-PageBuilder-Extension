(function ( $, fwe, data ) {
	var gui = {
		elements: {
			$useBuilderBtn: $( '<a href="#" class="button button-primary">' + data.l10n.showButton + '</a>' ),
			$useWpEditorBtn: $( '<a href="#" class="button button-primary page-builder-hide-button">' + data.l10n.hideButton + '</a>' ),
			$option: $( '#' + data.optionId ),
			$builderBox: null, // initialized later
			$builderInput: null, // initialized later
			$builderActiveHidden: $( '<input name="page-builder-active" type="hidden">' ),
			$wpPostBodyContent: $( '#post-body-content' ),
			$wpPostDivRich: $( '#postdivrich' ),
			$wpContentWrap: $( '#wp-content-wrap' ),
			$wpTemplatesSelect: $( 'select[name="page_template"]:first' ),
		},
		events: _.extend( {}, Backbone.Events ),
		editorId: 'content',
		getBuilderInputValue: function () {
			return this.elements.$builderInput.val()
		},
		getWPEditorContent: function () {
			/*
			 * WordPress works with tinyMCE for its WYSIWYG editor
			 * depending on the current editor tab (visual or text)
			 * we need to ask tinyMCE to get the content (in the case of visual tab)
			 * of get the value from the #content textarea (in the case of text tab)
			 */
			if ( this.elements.$wpContentWrap.hasClass( 'tmce-active' ) && tinyMCE.get( this.editorId ) ) {
				return tinyMCE.get( this.editorId ).getContent()
			} else {
				return this.elements.$wpContentWrap.find( '#content' ).val()
			}
		},
		clearWPEditorContent: function () {
			/*
			 * WordPress works with tinyMCE for its WYSIWYG editor
			 * depending on the current editor tab (visual or text)
			 * we need to clear tinyMCE instance (in the case of visual tab)
			 * of the value from the #content textarea (in the case of text tab)
			 */
			if ( this.elements.$wpContentWrap.hasClass( 'tmce-active' ) ) {
				return tinyMCE.get( this.editorId ).setContent( '' )
			} else {
				return this.elements.$wpContentWrap.find( '#content' ).val( '' )
			}
		},
		showBuilder: function () {

			this.elements.$useWpEditorBtn.show();
			this.elements.$wpPostBodyContent.addClass( 'page-builder-visible' );
			this.elements.$wpPostDivRich.addClass( 'fw-disable-editor' );
			this.elements.$builderBox.show().removeClass( 'closed' );

			// set the hidden to store that the builder is active
			this.elements.$builderActiveHidden.val( 'true' );

			this.events.trigger( 'show' );
		},
		hideBuilder: function () {

			this.elements.$wpPostBodyContent.removeClass( 'page-builder-visible' );
			this.elements.$useWpEditorBtn.hide();
			this.elements.$builderBox.hide();
			this.elements.$wpPostDivRich.removeClass( 'fw-disable-editor' );
			this.elements.$wpPostDivRich.trigger( 'resize' );
			// set the hidden to store that the builder is inactive
			this.elements.$builderActiveHidden.val( 'false' );
			//tinyMCE.get( gui.editorId ).execCommand("mceRepaint");
			this.events.trigger( 'hide' );
		},
		initButtons: function () {
			// insert the show button
			$( '#wp-content-media-buttons' ).prepend( this.elements.$useBuilderBtn );
			this.elements.$wpPostDivRich.before( this.elements.$useWpEditorBtn );

			if ( this.elements.$option.attr( 'data-builder-active' ) ) {
				this.showBuilder()
			} else {
				this.hideBuilder();
			}
		},
		insertHidden: function () {
			/**
			 * whether or not to display the builder at render depends
			 * on a value that is stored in the $builderActiveHidden hidden input
			 */
			this.elements.$builderBox.prepend( this.elements.$builderActiveHidden )
		},
		bindEvents: function () {
			var self = this;

			this.elements.$useBuilderBtn.on( 'click', function ( e ) {
				e.preventDefault();
				self.showBuilder();
			} );

			this.elements.$useWpEditorBtn.on( 'click', function ( e ) {
				e.preventDefault();
				self.hideBuilder();
			} );
		},
		removeScreenOptionsCheckbox: function () {
			$( 'label[for="fw-options-box-page-builder-box-hide"]' ).remove()
		},
		fixOnFirstShowOrHide: function ( isShow ) {
			var initialStateIsShow = ! ! this.elements.$option.attr( 'data-builder-active' );

			if ( initialStateIsShow === isShow ) {
				/**
				 * Do nothing, this happens when the same state is set again and again,
				 * for e.g. the builder is enabled/shown, but the this.showBuilder() is still called.
				 *
				 * We need to take an action when the state will be changed (shown->hidden or hidden->shown)
				 */
				return
			}

			if ( initialStateIsShow ) {
				/*
				 * If the page has to render with the builder being active,
				 * clear the wp editor textarea because the user wants to write the content from scratch
				 */
				this.clearWPEditorContent();
				this.elements.$wpContentWrap.find( '#content-tmce' ).trigger( 'click' );
			} else {
				/*
				 * If the page has to render with wp editor active
				 * get the content from the wp editor textarea
				 * and create a text_block in the builder that contains that content
				 */
				var wpEditorContent = this.getWPEditorContent();
				if ( wpEditorContent ) {
					window.optionTypePageBuilder.initWithTextBlock( wpEditorContent )
				}
			}

			/**
			 * This method must be called only once
			 * Prevent call again
			 */
			this.fixOnFirstShowOrHide = function () {}
		},
		initTemplatesSelectSync: function () {
			if ( ! data.builderTemplates.length ) {
				/**
				 * There are no templates that support page-builder.
				 * Do nothing, allow builder for all templates,
				 * there are themes that were created before this feature was added.
				 */
				return false
			}

			if ( ! this.elements.$wpTemplatesSelect.length ) {
				return false
			}

			/**
			 * On builder show, make sure that a template that supports builder is selected in wp templates select
			 */
			this.events.on( 'show', _.bind( function () {
				this.elements.$wpTemplatesSelect.find( '> option' ).each( function () {
					if ( $.inArray( $( this ).attr( 'value' ), data.builderTemplates ) !== - 1 ) {
						$( this ).prop( 'selected', true );
						return false
					}
				} )
			}, this ) );

			var onChange = _.bind( function () {
				if ( $.inArray( this.elements.$wpTemplatesSelect.val(), data.builderTemplates ) === - 1 ) {
					this.hideBuilder()
				} else {
					this.showBuilder()
				}
			}, this );

			this.elements.$wpTemplatesSelect.on( 'change', onChange );

			onChange();
		},
		/**
		 * Update post content on builder change to generate a new revision on post save
		 * and to update SEO Yoast stats https://github.com/Yoast/wordpress-seo/issues/6312
		 * @since 1.6.X
		 */
		initPostContentUpdate: function () {
			/**
			 * fixme: On PageBuilder change the json must be rendered in frontend (where is_admin() is false)
			 *        and the returned html must be sanitized/filtered and placed in post content editor.
			 *        I don't know how to implement this.
			 */
			return;

			var postContentBeforeBuilderActivate,
				eventsNamespace = '.fwEditorIntegrationPostContentUpdate',
				getEditor = function () {
					return tinyMCE.get( gui.editorId )
				},
				builderToEditorContent = function () {
					var editor = getEditor(),
						changeVisibility = editor.hidden;

					if ( changeVisibility ) {
						// editor.fire('change') works only when editor is visible
						editor.show();
					}

					editor.setContent( fw.md5( gui.getBuilderInputValue() ) );
					editor.fire( 'change' );

					if ( changeVisibility ) {
						editor.hide();
					}
				};

			this.events.on( 'show', _.bind( function () {
				// store post content and replace it with builder content
				postContentBeforeBuilderActivate = getEditor().getContent();
				builderToEditorContent();

				this.elements.$option.on( 'fw-builder:input:change' + eventsNamespace, function () {
					builderToEditorContent();
				} )
			}, this ) );

			this.events.on( 'hide', _.bind( function () {
				// restore post content
				getEditor().setContent( postContentBeforeBuilderActivate );
				postContentBeforeBuilderActivate = '';

				this.elements.$option.off( eventsNamespace )
			}, this ) );
		},
		init: function () {
			this.elements.$builderBox = this.elements.$option.closest( '.postbox' );
			this.elements.$builderInput = this.elements.$option.find( 'input[type="hidden"]:first' );
			var self = this;

			// fixes on firs show or hide
			this.events.once( 'show', _.bind( function () {
				this.fixOnFirstShowOrHide( true );
			}, this ) );

			this.events.once( 'hide', _.bind( function () {
				this.fixOnFirstShowOrHide( false );
			}, this ) );

			/**
			 * public events
			 * @since 1.6.8
			 */
			this.events.on( 'show', function () {
				fwEvents.trigger( 'fw:ext:page-builder:editor-integration:show' );
			} );

			this.events.on( 'hide', function () {
				fwEvents.trigger( 'fw:ext:page-builder:editor-integration:hide' );
			} );

			/**
			 * Init after PageBuilder was fully initialized
			 * because both operations consume a lot of CPU
			 * and I think it's faster if they are executed one by one (not in parallel)
			 */
			$( document.body ).on( 'fw:option-type:builder:init.fw_ext_page_builder_integration', function ( e, data ) {
				if ( 'page-builder' === data.builder.get( 'type' ) ) {

					self.insertHidden();
					self.bindEvents();
					self.initPostContentUpdate();
					self.removeScreenOptionsCheckbox();
					self.initTemplatesSelectSync();
					self.initButtons();

					$( document.body ).off( '.fw_ext_page_builder_integration' );

					/**
					 * Show the Update button after full builder init
					 * Fixes https://github.com/ThemeFuse/Unyson/issues/1542#issuecomment-218094104
					 */
					$( '#fw-option-type-page-builder-editor-integration-inline-css' ).remove();

					/** @since 1.6.8 */
					fwEvents.trigger( 'fw:ext:page-builder:editor-integration:init' )
				}
			}.bind( this ) );
		}
	};

	gui.init(); // call this right away, earlier than document ready, else there will be glitches

	/*
	 * The global variable optionTypePageBuilder is created intentionally
	 * to allow creating a text_block shortcode when switching from
	 * the default editor into the visual one for the first time
	 */
	fwe.on( 'fw-builder:' + 'page-builder' + ':register-items', function ( builder ) {
		window.optionTypePageBuilder = builder;
		window.optionTypePageBuilder.initWithTextBlock = function ( content ) {
			this.rootItems.reset( [
				{
					type: 'simple',
					shortcode: 'text_block',
					atts: {text: content}
				}
			] );
		};
	} );

	/**
	 * Add the 'fw-page-builder-editor-integration-active' body class when the PageBuilder is active
	 */
	var className = 'fw-page-builder-editor-integration-active';
	fwEvents.on( 'fw:ext:page-builder:editor-integration:show', function show() {
		$( 'body' ).addClass( className );
	} );

	fwEvents.on( 'fw:ext:page-builder:editor-integration:hide', function hide() {
		$( 'body' ).removeClass( className );
	} );
} )( jQuery, fwEvents, fw_option_type_page_builder_editor_integration_data );

/**
 * Update post content on builder change to trigger auto-save creation on post preview
 * because if no post field is changed WP will not create auto-save on preview and builder changes will not be visible
 * Fixes https://github.com/ThemeFuse/Unyson/issues/1304
 */
jQuery( function ( $ ) {
	var builderInput = document.getElementById( 'fw-option-input--page-builder' ),
		eventsNamespace = '.fw-ext-page-builder',
		originalContentValue,
		isBuilderActive = function () {
			return jQuery( '#fw-option-page-builder' ).is( ':visible' );
		};

	/**
	 * Use mouseup instead of click to be executed before
	 * https://github.com/WordPress/WordPress/blob/4.5/wp-admin/js/post.js#L295
	 */
	$( '#post-preview' ).on( 'mouseup' + eventsNamespace + ' touchend' + eventsNamespace, function () {
		if ( ! isBuilderActive() ) {
			return;
		}

		var $content = $( '#content' );

		originalContentValue = $content.val();

		/**
		 * Mimic $post_content from extension class
		 * But it will never be the same because on php side the json is fixed/changed
		 */
		$content.val( '<!-- ' + fw.md5( builderInput.value ) + ' -->' );
	} ).on( 'click' + eventsNamespace, function () {
		/**
		 * This for sure will be executed right after
		 * https://github.com/WordPress/WordPress/blob/4.5/wp-admin/js/post.js#L295
		 */
		if ( ! isBuilderActive() ) {
			return;
		}

		$( '#content' ).val( originalContentValue );

		originalContentValue = ''; // free memory
	} );
} );
