(function(fwe) {
	fwe.on('fw-builder:' + 'page-builder' + ':register-items', function(builder) {
		var PageBuilderSimpleItem,
			PageBuilderSimpleItemView,
			triggerEvent = function(itemModel, event, eventData) {
				event = 'fw:builder-type:{builder-type}:item-type:{item-type}:'
					.replace('{builder-type}', builder.get('type'))
					.replace('{item-type}', itemModel.get('type'))
					+ event;

				var data = {
					modal: itemModel.view ? itemModel.view.modal : null,
					item: itemModel,
					itemView: itemModel.view,
					shortcode: itemModel.get('shortcode'),
					builder: builder
				};

				fwEvents.trigger(
					event,
					eventData ? _.extend(eventData, data) : data
				);
			};

		PageBuilderSimpleItemView = builder.classes.ItemView.extend({
			initialize: function(options) {
				this.defaultInitialize();

				this.initOptions = options;
				this.initOptions.templateData = this.initOptions.templateData || {};
				this.initOptions.modalOptions = this.initOptions.modalOptions || {};
			},
			template: _.template(
				'<div class="pb-item-type-simple <% if (hasOptions) { %>has-options <% } %>pb-item fw-row">' +
					'<% if (icon) { %>' +
						'<% if (typeof FwBuilderComponents.ItemView.iconToHtml == "undefined") { %>' +
							'<img src="<%- icon %>" alt="Icon" />' +
						'<% } else { %>' +
							'<%= FwBuilderComponents.ItemView.iconToHtml(icon) %>' +
						'<% } %>' +
					'<% } %>' +

					'<%= title %>' +
					'<div class="controls">' +

						'<% if (hasOptions) { %>' +
						'<i class="dashicons dashicons-admin-generic edit-options" data-hover-tip="<%- edit %>"></i>' +
						'<%  } %>' +

						'<i class="dashicons dashicons-admin-page item-clone" data-hover-tip="<%- duplicate %>"></i>' +
						'<i class="dashicons dashicons-no item-delete" data-hover-tip="<%- remove %>"></i>' +
					'</div>' +
				'</div>'
			),
			render: function() {
				{
					var title = this.initOptions.templateData.title,
						titleTemplate = itemData( this.model.get('shortcode') ).title_template;

					if (titleTemplate && this.model.get('atts')) {
						try {
							title = _.template(
								jQuery.trim(titleTemplate),
								undefined,
								{
									evaluate: /\{\{([\s\S]+?)\}\}/g,
									interpolate: /\{\{=([\s\S]+?)\}\}/g,
									escape: /\{\{-([\s\S]+?)\}\}/g
								}
							)({
								o: this.model.get('atts'),
								title: title
							});
						} catch (e) {
							console.error('$cfg["page_builder"]["title_template"]', e.message);

							title = _.template('<%= title %>')({title: title});
						}
					} else {
						title = _.template('<%= title %>')({title: title});
					}
				}

				this.defaultRender(
					jQuery.extend({}, this.initOptions.templateData, {title: title})
				);

				/**
				 * Other scripts can append/prepend other control $elements
				 */
				triggerEvent(this.model, 'controls', {
					$controls: this.$('.controls:first')
				});

				/**
				 * You can handle all shortcodes that are of type simple
				 * at once * with * this event.
				 *
				 * Getting the shortcode name is as simple as:
				 *
				 * fwEvents.on(
				 *   'fw:page-builder:shortcode:item-simple:controls',
				 *   function (data) {
				 *     console.log(data.model.get('shortcode'));
				 *   }
				 * );
				 *
				 * You can make changes to controls to a single shortcode by
				 * checking the value of data.model.get('shortcode').
				 *
				 * fwEvents.on(
				 *   'fw:page-builder:shortcode:item-simple:controls',
				 *   function (data) {
				 *     if (data.model.get('shortcode') !== 'my_desired_shortcode') {
				 *       return;
				 *     }
				 *
				 *     // Change controls
				 *   }
				 * );
				 */
				fwEvents.trigger('fw:page-builder:shortcode:item-simple:controls', {
					$controls: this.$('.controls:first'),
					model: this.model,
					builder: builder
				});
			},
			events: {
				'click': 'editOptions',
				'click .edit-options': 'editOptions',
				'click .item-clone': 'cloneItem',
				'click .item-delete': 'removeItem'
			},
			lazyInitModal: function() {
				this.lazyInitModal = function(){}; // must be called only once

				if (_.isEmpty(this.initOptions.modalOptions)) {
					return;
				}

				var itmData = itemData(this.model.get('shortcode')),
					eventData = {
						modalSettings: {
							buttons: [],
							disableResetButton: itmData.disable_modal_reset_btn
						}
					};

				/**
				 * eventData.modalSettings can be changed by reference
				 */
				triggerEvent(this.model, 'options-modal:settings', eventData);

				this.modal = new fw.OptionsModal({
					title: this.initOptions.templateData.title,
					options: this.initOptions.modalOptions,
					values: this.model.get('atts'),
					size: this.initOptions.modalSize,
					headerElements: itmData.popup_header_elements
				}, eventData.modalSettings);

				this.listenTo(this.modal, 'change:values', function(modal, values) {
					this.model.set('atts', values);
				});

				this.listenTo(this.modal, {
					'open': function(){
						triggerEvent(this.model, 'options-modal:open');
					},
					'render': function(){
						triggerEvent(this.model, 'options-modal:render');
					},
					'close': function(){
						triggerEvent(this.model, 'options-modal:close');
					},
					'change:values': function(){
						triggerEvent(this.model, 'options-modal:change:values');
					}
				});
			},
			editOptions: function(e) {
				e.stopPropagation();

				this.lazyInitModal();

				if (!this.modal) {
					return;
				}

				/**
				 * Create initial flow settings for modal openning.
				 * TODO: Maybe move this in fw.OptionsModal.
				 */
				var flow = {cancelModalOpening: false};

				/**
				 * In case you want to do some changes or processing before
				 * shortcode modal openning that's the event you want to use.
				 *
				 * flow option is here because you may want to perform some
				 * asynchronous operation (like an AJAX request) and you
				 * need the modal to open after your operation is completed.
				 *
				 * fwEvents.on(
				 *   'fw:page-builder:shortcode:item-simple:modal:before-open',
				 *   function (data) {
				 *     // In case you need to perform some async action,
				 *     // you need to cancel modal opening first.
				 *     //
				 *     // In this you are responsible for further opening,
				 *     // when you finish your business.
				 *     flow.cancelModalOpening = true
				 *
				 *     // start your ajax request, do some actual async work...
				 *     var promise = $.ajax({ ... });
				 *
				 *     promise.done(function (yourBusinessData) {
				 *       // do actual change using yourBusinessData
				 *       //
				 *       // ... and don't forget to open modal when your done
				 *       data.modal.open();
				 *     }
				 *   }
				 * );
				 */
				fwEvents.trigger('fw:page-builder:shortcode:item-simple:modal:before-open', {
					modal: this.modal,
					model: this.model,
					builder: builder,
					flow: flow
				});

				/**
				 * Skip modal opening, if the user wants to.
				 */
				if (! flow.cancelModalOpening) {
					this.modal.open();
				}
			},
			cloneItem: function(e) {
				e.stopPropagation();
				var index = this.model.collection.indexOf(this.model),
					attributes = this.model.toJSON();

				var clonedItem = new PageBuilderSimpleItem(attributes);

				triggerEvent(clonedItem, 'clone-item:before');

				this.model.collection.add(clonedItem, {at: index + 1})
			},
			removeItem: function(e) {
				e.stopPropagation();
				this.remove();
				this.model.collection.remove(this.model);
			}
		});

		PageBuilderSimpleItem = builder.classes.Item.extend({
			defaults: {
				type: 'simple'
			},
			initialize: function(atts, opts) {
				var shortcode = this.get('shortcode') || opts.$thumb.find('.item-data').attr('data-shortcode'),
					shortcodeData,
					modalOptions;

				this.defaultInitialize();

				if (!itemData(shortcode)) {
					this.view = new builder.classes.ItemView({
						id: 'fw-builder-item-'+ this.cid,
						model: this
					});

					fw.soleModal.show(
						'fw-page-builder-shortcode-not-found:'+ shortcode,
						'<p class="fw-text-danger">The shortcode <code>' + shortcode + '</code> not found.<p>'
					);
				} else {
					shortcodeData = itemData(shortcode);
					modalOptions = shortcodeData.options;

					if (!this.get('shortcode')) {
						this.set('shortcode', shortcode);
					}

					var templateData = {
						title: shortcodeData.title,
						icon: shortcodeData.icon,
						edit: shortcodeData.localize.edit,
						remove: shortcodeData.localize.remove,
						duplicate: shortcodeData.localize.duplicate,
						hasOptions: !!modalOptions
					};

					this.view = new PageBuilderSimpleItemView({
						id: 'page-builder-item-'+ this.cid,
						model: this,
						modalOptions: modalOptions,
						modalSize: shortcodeData.popup_size,
						templateData: templateData
					});
				}
			},
			allowIncomingType: function() {
				return false;
			}
		});

		builder.registerItemClass(PageBuilderSimpleItem);
	});

	function itemData (shortcode) {
		return page_builder_item_type_simple_data[shortcode];
	}
})(fwEvents);

