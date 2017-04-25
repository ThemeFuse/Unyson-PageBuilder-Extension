(function($, l10n){
	var inst = {
		btnClass: 'fw-pb-save-all',
		modalChain: [],
		resetChain: function (modal) {
			_.each(this.modalChain, this.removeButton, this);

			this.modalChain = [modal];

			this.detachEvents();

			fwEvents.on(
				'fw:options-modal:open',
				this.modalOpenListener
			);

			fwEvents.on(
				'fw:options-modal:close',
				this.modalCloseListener
			);
		},

		detachEvents: function () {
			fwEvents.off(
				'fw:options-modal:open',
				this.modalOpenListener
			);

			fwEvents.off(
				'fw:options-modal:close',
				this.modalCloseListener
			);
		},

		pushChain: function (modal) {
			if (!this.modalChain.length) {
				return;
			} else {
				this.modalChain.push(modal);
			}

			this.addButton(modal);
		},
		popChain: function () {
			var modal = this.modalChain.pop();

			if (!modal) {
				return console.warn('Logic error');
			} else if (! this.modalChain.length) {
				this.detachEvents();
			}

			this.removeButton(modal);
		},
		saveChain: function(){
			var modal = this.modalChain.pop();
			if (modal) {
				fw.loading.show(this.btnClass);
				modal.once('close', function(){
					fw.loading.hide(inst.btnClass);
					inst.saveChain();
				});
				modal.content.$el.find('input[type="submit"]').focus().trigger('click');
			}
		},
		$getToolbar: function (modal) {
			return modal.frame.views.get(modal.frame.toolbar.selector)[0].$el.find('.media-toolbar-primary:first');
		},
		addButton: function (modal) {
			this.removeButton(modal);

			var $toolbar = this.$getToolbar(modal);

			$toolbar.append(
				$('<button type="button" class="button media-button button-large"></button>')
					.addClass(this.btnClass)
					.text($toolbar.find('.button-primary:first').text() + l10n.btn_text_suffix)
					.on('click', _.bind(function (e) {
						e.preventDefault();
						this.detachEvents();
						this.saveChain();
					}, this))
			);
		},
		removeButton: function (modal) {
			this.$getToolbar(modal).find('.'+ this.btnClass).remove();
		}
	};

	inst.modalOpenListener = _.bind(function (data) {
		inst.pushChain(data.modal);
	}, inst);

	inst.modalCloseListener = _.bind(function (data) {
		inst.popChain();
	}, inst);

	fwEvents.on(
		['simple', 'column', 'section']
			.map(function(item){
				return 'fw:builder-type:page-builder:item-type:'+ item +':options-modal:open';
			})
			.join(' '),
		function (data) { inst.resetChain(data.modal); }
	);
})(jQuery, _fw_page_builder_modal_save_all);
