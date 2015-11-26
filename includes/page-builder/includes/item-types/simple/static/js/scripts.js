(function(fwe, _, builderData) {
	fwe.on('fw-builder:' + 'page-builder' + ':register-items', function(builder) {
		var PageBuilderSimpleItem,
			PageBuilderSimpleItemView,
			triggerEvent = function(itemModel, event, eventdata) {
				event = 'fw:builder-type:{builder-type}:item-type:{item-type}:'
					.replace('{builder-type}', builder.get('type'))
					.replace('{item-type}', itemModel.get('type'))
					+ event;

				var data = {
					modal: itemModel.view.modal,
					item: itemModel,
					itemView: itemModel.view,
					shortcode: itemModel.get('shortcode')
				};

				fwEvents.trigger(event, eventdata
					? _.extend(eventdata, data)
					: data
				);
			};

		PageBuilderSimpleItemView = builder.classes.ItemView.extend({
			initialize: function(options) {
				this.defaultInitialize();

				this.templateData = options.templateData || {};
				if (options.modalOptions) {
					this.modal = new fw.OptionsModal({
						title: options.templateData.title,
						options: options.modalOptions,
						values: this.model.get('atts'),
						size: options.modalSize,
						headerElements: builderData[ this.model.get('shortcode') ].popup_header_elements
					});

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
				}
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
					var title = this.templateData.title,
						titleTemplate = builderData[ this.model.get('shortcode') ].title_template;

					if (titleTemplate && this.model.get('atts')) {
						try {
							title = _.template(
								jQuery.trim(titleTemplate),
								{
									o: this.model.get('atts'),
									title: title
								},
								{
									evaluate: /\{\{([\s\S]+?)\}\}/g,
									interpolate: /\{\{=([\s\S]+?)\}\}/g,
									escape: /\{\{-([\s\S]+?)\}\}/g
								}
							);
						} catch (e) {
							console.error('$cfg["page_builder"]["title_template"]', e.message);

							title = _.template('<%- title %>', {title: title});
						}
					} else {
						title = _.template('<%- title %>', {title: title});
					}
				}

				this.defaultRender(
					jQuery.extend({}, this.templateData, {title: title})
				);

				/**
				 * Other scripts can append/prepend other control $elements
				 */
				triggerEvent(this.model, 'controls', {
					$controls: this.$('.controls:first')
				});
			},
			events: {
				'click': 'editOptions',
				'click .edit-options': 'editOptions',
				'click .item-clone': 'cloneItem',
				'click .item-delete': 'removeItem'
			},
			editOptions: function(e) {
				e.stopPropagation();
				if (!this.modal) {
					return;
				}
				this.modal.open();
			},
			cloneItem: function(e) {
				e.stopPropagation();
				var index = this.model.collection.indexOf(this.model),
					attributes = this.model.toJSON();
				this.model.collection.add(new PageBuilderSimpleItem(attributes), {at: index + 1})
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

				if (!builderData[shortcode]) {
					this.view = new builder.classes.ItemView({
						id: 'fw-builder-item-'+ this.cid,
						model: this
					});

					fw.soleModal.show(
						'fw-page-builder-shortcode-not-found:'+ shortcode,
						'<p class="fw-text-danger">The shortcode <code>' + shortcode + '</code> not found.<p>'
					);
				} else {
					shortcodeData = builderData[shortcode];
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
})(fwEvents, _, page_builder_item_type_simple_data);
