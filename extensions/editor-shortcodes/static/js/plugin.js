(function ($) {
	"use strict";

	var plugin_name = fw_option_shortcode_globals.plugin_name,
		selector = fw_option_shortcode_globals.storage_selector,
		shortcodeList = fw_option_shortcode_globals.shortcode_list,
		shortcodeTags = [];

	for (var i in shortcodeList) {
		if (shortcodeList.hasOwnProperty(i)) {
			shortcodeTags.push(i);
		}
	}

	//todo: test regexp
	/**
	 * tag_regex - used for parsing text string and extract shortcode's tag & id
	 * html_regex - used for parsing html string extract (shortcodes tag & id)
	 */
	var tag_regex = new RegExp("\\[(" + shortcodeTags.join("|") + ")(?:\\s+[^\\[\\]]*)fw_shortcode_id=[\"\\']([A-Za-z0-9]+)[\"\\'](?:\\s?[^\\[\\]]*)\\]", "g"),
		html_regex = new RegExp('<span[^>]+?data-shortcode-tag="(' + shortcodeTags.join("|") + ')"\\s+data-id="([A-Za-z0-9]+)"+?[\\s\\S]*?3Nd0fL1N3Sh0rtC0d3<\\/span><\\/span><\\/span>', 'g');

	tinymce.create('tinymce.plugins.' + plugin_name,
		{
			shortcodeValuesStock: {
				$element: $(selector),
				get: function (tag, id) {
					var inputValues = this.$element.val(),
						values;

					if (!_.isEmpty(inputValues)) {
						try {
							values = JSON.parse(inputValues);
							if (typeof values[tag] !== 'undefined' && typeof values[tag][id] !== 'undefined') {
								return values[tag][id];
							}
						} catch (e) {
							//when cannot parse json
							return {};
						}
					}
					return {};
				},
				add: function (tag) {
					var id = this.generateIdForTag(tag),
						values = {};
					this.set(tag, id, values);
					return id;
				},
				set: function (tag, id, values) {
					var inputValues = {}, defaults = {};

					try {
						defaults = JSON.parse(this.$element.val());
					}
					catch (e) {
						defaults = {};
					}

					if (_.isEmpty(defaults)) {
						inputValues[tag] = {};
						inputValues[tag][id] = values;
					} else {
						if (defaults.hasOwnProperty(tag)) {
							inputValues = defaults;
							inputValues[tag][id] = values;
						} else {
							inputValues = defaults;
							inputValues[tag] = {};
							inputValues[tag][id] = values;
						}
					}

					this.$element.val(JSON.stringify(inputValues));
				},

				generateIdForTag: function (tag) {
					var inputValues = this.$element.val(),
						values, max = 1;

					if (!_.isEmpty(inputValues)) {
						try {
							values = JSON.parse(inputValues);
							if (typeof values[tag] !== 'undefined') {
								for (var i in values[tag]) {
									if (values[tag].hasOwnProperty(i)) {
										if ( parseInt(i) > parseInt(max) ) {
											max = i;
										}
									}
								}
								return +max + 1;
							}
						} catch (e) {
							return max;
						}
					}
					return max;
				}
			},

			//fix menu position on center
			fixPosition: function(e) {
				try {
					var id = e.control.panel._id,
						$panel = $('#' + id + '.mce-fw-shortcodes-container'),
						oldPos = $panel.data('left');
					if (typeof oldPos === 'undefined' ) {
						oldPos = parseInt($panel.css('left'));
						$panel.data('left', oldPos);
					}

					$panel.css('left',(oldPos - 216)+'px');
					$panel.css('height', '');
				} catch (e) {
					//sometime _id is undefined
					return false;
				}
			},

			getMenuHtml: function () {
				var shortcodeMenuHtml = '';

				tinymce.each(shortcodeList, function (shortcode, key) {
					{
						var iconHtml = '';

						if (shortcode.icon) {
							if (typeof FwBuilderComponents.ItemView.iconToHtml == "undefined") {
								iconHtml = '<img src="' + shortcode.icon + '"/>';
							} else {
								iconHtml = FwBuilderComponents.ItemView.iconToHtml(shortcode.icon);
							}
						}
					}

					shortcodeMenuHtml += '' +
						'<div class="fw-shortcode-item" data-shortcode-tag="' + key + '">' +
							'<div class="inner">' +
								iconHtml +
								'<p><span>' + shortcode.title + '</span></p>' +
							'</div>' +
						'</div>';
				});

				return shortcodeMenuHtml;
			},

			init: function (editor) {
				if (editor.id != 'content') {
					return; // add button only to post content wp-editor
				}

				var _self = this;

				editor.addButton(plugin_name, {
					type: 'panelbutton',
					icon: 'fw-button-icon',
					panel: {
						style: 'max-width: 450px;',
						role: 'application',
						classes: 'fw-shortcodes-container',
						autohide: true,
						html: _self.getMenuHtml,
						onclick: function (e) {
							var tag;

							if ($(e.target).hasClass('fw-shortcode-item')) {
								tag = $(e.target).data('shortcode-tag');
							} else if (editor.dom.getParent(e.target, '.fw-shortcode-item')) {
								tag = $(editor.dom.getParent(e.target, '.fw-shortcode-item')).data('shortcode-tag');
							} else {
								return false;
							}

							if (tag) {
								tinyMCE.activeEditor.execCommand("insertShortcode", false, {tag: tag});
							}

							this.hide();
						}
					},
					onclick: _self.fixPosition,
					tooltip: 'Editor shortcodes'
				});

				editor.addCommand('insertShortcode', function (ui, params) {
					var node,
						p,
						content = _self.getElementHTML(params.tag, _self.shortcodeValuesStock.add(params.tag));

					if (node = editor.dom.getParent(editor.selection.getNode())) {
						p = editor.dom.create('p');
						editor.dom.insertAfter(p, node);
						editor.selection.setCursorLocation(p, 0);
						editor.nodeChanged();
					}

					editor.execCommand("mceInsertContent", false, content);
				});

				//add open modal listner
				editor.addCommand("openFwModal", function (ui, params) {
					if (typeof params.item.options === 'undefined') {
						return false
					}

					var values = _self.shortcodeValuesStock.get(params.tag, params.id),

						modal = new fw.OptionsModal({
							title: params.item.title,
							options: params.item.options,
							size: params.item.popup_size,
							values: values
						});

					modal.open();
					modal.on('change:values', function (modal, values) {
						_self.shortcodeValuesStock.set(params.tag, params.id, values);
					});

					return false;
				});

				//disable drag&drop in firefox
				editor.on('mousedown', function(e){
					if ( $(e.target).hasClass('unselectable') ) {
						e.stopPropagation();
						return false;
					}
				});

				editor.on('keydown', function (e) {
					if (e.which === 13 || e.keyCode === 13) {
						//todo: detect when need to insert before

						if (editor.dom.getAttrib(editor.selection.getStart(), 'class').indexOf('fw-shortcode') !== -1) {
							var P = editor.dom.create('p', null, '<br data-mce-bogus="1" />');
							editor.dom.insertAfter(P, editor.selection.getNode());
							editor.selection.setCursorLocation(P);

							editor.nodeChanged();
							e.stopPropagation();
							return e.preventDefault();
						}
					}
				});

				//replace tags with html block
				editor.on('BeforeSetContent', function (event) {
					if (event.content.match(tag_regex)) {
						event.content = _self.getHTML(event.content);
					}
				});

				//add listners for content item
				editor.on('click', function (e) {
					var currentElement = e.target;


					//delete item
					if ($(currentElement).hasClass('fw-item-delete')) {
						$(currentElement).parents('.fw-shortcode').remove();
						return false;
					}

					//clone item
					if ($(currentElement).hasClass('fw-item-clone')) {
						//todo: change cursor position ??

						var tag = $(currentElement).parents('[data-shortcode-tag]').data('shortcode-tag');
						editor.execCommand("insertShortcode", false, {tag: tag});
						return false;
					}

					//default is edit item
					 if ($(currentElement).hasClass('fw-shortcode')) {
						var id = $(currentElement).data('id'),
							tag = $(currentElement).data('shortcode-tag');

						if (typeof shortcodeList[tag] !== 'undefined') {
							editor.execCommand("openFwModal", false, {item: shortcodeList[tag], tag: tag, id: id});
						}
					} else if ($(currentElement).parents('[data-shortcode-tag]').length) {

						 var id = $(currentElement).parents('[data-shortcode-tag]').data('id'),
							 tag = $(currentElement).parents('[data-shortcode-tag]').data('shortcode-tag');

						 if (typeof shortcodeList[tag] !== 'undefined') {
							 editor.execCommand("openFwModal", false, {item: shortcodeList[tag], tag: tag, id: id});
						 }
					}

				});

				//replace all html content with tags
				editor.on('PostProcess', function (event) {
					if (event.get) {
						event.content = _self.getTags(event.content);
					}
				});
			},

			getHTML: function (string) {
				var _self = this;

				return string.replace(tag_regex, function (match, tag, id) {
					if (typeof shortcodeList[tag] !== 'undefined') {
						return _self.getElementHTML(tag, id);
					}

					return match;
				});
			},

			getElementHTML: function (tag, id) {
				var shortcode = shortcodeList[tag];

				{
					var icon = '';

					if (shortcode.icon) {
						if (typeof FwBuilderComponents.ItemView.iconToHtml == "undefined") {
							icon = '<img class="icon" src="' + shortcode.icon + '"/>';
						} else {
							icon = FwBuilderComponents.ItemView.iconToHtml(shortcode.icon);
						}
					}

					icon = jQuery(
						'<div>' + icon + '</div>'
					);
					icon.children()
						.addClass('mceItem mceNonEditable unselectable')
						.attr('contenteditable', 'false')
						.filter('span,i,em').html('&nbsp;');
					icon = icon.html();
				}

				return '' +
				'<span data-shortcode-tag="' + tag + '" data-id="' + id + '" class="mceNonEditable mceItem fw-shortcode unselectable" contenteditable="false">' +
					'<span class="mceItem fw-component-bar mceNonEditable unselectable" contenteditable="false">' +
						icon +
						'<span class="mceItem mceNonEditable unselectable" contenteditable="false">' + shortcode.title + '</span>' +
						'<span class="fw-item-buttons mceItem fw-component-controls mceNonEditable unselectable">' +
							'<i class="mceItem mceNonEditable unselectable dashicons dashicons-admin-generic fw-item-edit">&nbsp;</i>' +
							'<i class="mceItem mceNonEditable unselectable dashicons dashicons-admin-page fw-item-clone">&nbsp;</i>' +
							'<i class="mceItem mceNonEditable unselectable dashicons dashicons-no fw-item-delete">&nbsp;</i>' +
						'</span>' +
						'<span class="mceItem mceNonEditable fw-component-title unselectable fw-hide" style="display: none">3Nd0fL1N3Sh0rtC0d3</span>' +
					'</span>' +
				'</span>';
			},

			getTags: function (htmlString) {
				var shortcodeString = htmlString.replace(html_regex, function (match, tag, id) {
					return '[' + tag + ' fw_shortcode_id="' + id + '"]';
				});

				return shortcodeString;
			}

		});


	tinymce.PluginManager.add(plugin_name, tinymce.plugins[plugin_name]);

})(jQuery);