(function(fwe, _, builderData) {
	fwe.one('fw-builder:' + 'page-builder' + ':register-items', function(builder) {
		var PageBuilderSimpleItem,
			PageBuilderSimpleItemView;

		PageBuilderSimpleItemView = builder.classes.ItemView.extend({
			initialize: function(options) {
				this.defaultInitialize();

				this.templateData = options.templateData || {};
				if (options.modalOptions) {
					this.modal = new fw.OptionsModal({
						title: options.templateData.title,
						options: options.modalOptions,
						values: this.model.get('atts'),
						size: options.modalSize
					});

					this.listenTo(this.modal, 'change:values', function(modal, values) {
						this.model.set('atts', values);
					});
				}
			},
			template: _.template(
				'<div class="pb-item-type-simple <% if (hasOptions) { %>has-options <% } %>pb-item fw-row">' +
					'<% if (image) { %>' +
					'<img src="<%- image %>" />' +
					'<%  } %>' +

					'<%- title %>' + // TODO: see if needs to bee escaped or not
					'<div class="controls">' +

						'<% if (hasOptions) { %>' +
						'<i class="dashicons dashicons-welcome-write-blog edit-options"></i>' +
						'<%  } %>' +

						'<i class="dashicons dashicons-admin-page item-clone"></i>' +
						'<i class="dashicons dashicons-no item-delete"></i>' +
					'</div>' +
				'</div>'
			),
			render: function() {
				this.defaultRender(this.templateData);
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

					alert('The shortcode: "' + shortcode +  '" not found, it was probably deleted!');
					console.error('The requested shortcode: "%s" not found, , it was probably deleted!', shortcode);
				} else {
					shortcodeData = builderData[shortcode];
					modalOptions = shortcodeData.options;

					if (!this.get('shortcode')) {
						this.set('shortcode', shortcode);
					}

					var templateData = {
						title: shortcodeData.title,
						image: shortcodeData.image,
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
