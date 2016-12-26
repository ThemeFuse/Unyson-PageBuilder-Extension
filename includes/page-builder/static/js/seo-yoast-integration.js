jQuery(window).on('YoastSEO:ready', function(){
	var ExamplePlugin = function() {
		YoastSEO.app.registerPlugin( 'examplePlugin', {status: 'ready'} );

		/**
		 * @param modification    {string}    The name of the filter
		 * @param callable        {function}  The callable
		 * @param pluginName      {string}    The plugin that is registering the modification.
		 * @param priority        {number}    (optional) Used to specify the order in which the callables
		 *                                    associated with a particular filter are called. Lower numbers
		 *                                    correspond with earlier execution.
		 */
		YoastSEO.app.registerModification( 'content', this.myContentModification, 'examplePlugin', 5 );
	}

	/**
	 * Adds some text to the data...
	 *
	 * @param data The data to modify
	 */
	ExamplePlugin.prototype.myContentModification = function(data) { console.log(data);
		return data + ' some text to add';
	};

	new ExamplePlugin();
});