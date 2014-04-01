(function() {
	tinymce.create('tinymce.plugins.textbookbuttons', {
		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 *
		 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
		 * @param {string} url Absolute URL to where the plugin is located.
		 */
		init: function(ed, url) {
			ed.addButton('learningObjectives', {
				title: 'Learning Objectives',
				cmd: 'learningobjectives',
				image: url + '/learningobjectives.png'
			});

			ed.addCommand('learningobjectives', function() {
				var selected_text = ed.selection.getContent();
				var return_text = '';
				if(selected_text != ''){
				return_text = '<div class="bcc-box bcc-highlight" itemscope itemtype="http://schema.org/Text"><h3 itemprop="learningResourceType">Learning Objectives</h3>\n\
					' + selected_text + '</div><p></p>';
				} else {
					return_text = '<div class="bcc-box bcc-highlight" itemscope itemtype="http://schema.org/Text">\n\
					<h3 itemprop="learningResourceType">Learning Objectives</h3>\n\
					<p>Type your learning objectives here.</p>\n\
					<ul><li>First</li><li>Second</li></ul>\n\
					</div><p></p>';
				}
				ed.execCommand('mceInsertContent', 0, return_text);
			});
			ed.addButton('keyTakeaway', {
				title: 'Key Takeaway',
				cmd: 'keytakeaway',
				image: url + '/keytakeaway.png'
			});

			ed.addCommand('keytakeaway', function() {
				var selected_text = ed.selection.getContent();
				var return_text = '';
				if(selected_text != ''){
				return_text = '<div class="bcc-box bcc-success" itemscope itemtype="http://schema.org/Text"><h3 itemprop="learningResourceType">Key Takeaways</h3>\n\
					' + selected_text + '</div><p></p>';
				} else {
					return_text = '<div class="bcc-box bcc-success" itemscope itemtype="http://schema.org/Text">\n\
					<h3 itemprop="learningResourceType">Key Takeaways</h3>\n\
					<p>Type your key takeaways here.</p>\n\
					<ul><li>First</li><li>Second</li></ul>\n\
					</div><p></p>';
				}
				ed.execCommand('mceInsertContent', 0, return_text);
			});
			ed.addButton('exercises', {
				title: 'Exercises and Critical Thinking',
				cmd: 'exercises',
				image: url + '/exercise.png'
			});

			ed.addCommand('exercises', function() {
				var selected_text = ed.selection.getContent();
				var return_text = '';
				
				if(selected_text != ''){
				return_text = '<div class="bcc-box bcc-info" itemscope itemtype="http://schema.org/Text"><h3 itemprop="learningResourceType">Exercises</h3>\n\
					' + selected_text + '</div><p></p>';
				} else {
					return_text = '<div class="bcc-box bcc-info" itemscope itemtype="http://schema.org/Text">\n\
					<h3 itemprop="learningResourceType">Exercises</h3>\n\
					<p>Type your exercises here.</p>\n\
					<ol><li>First</li><li>Second</li></ol>\n\
					</div><p></p>';
				}
				ed.execCommand('mceInsertContent', 0, return_text);
			});
		},
		/**
		 * Creates control instances based in the incomming name. This method is normally not
		 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
		 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
		 * method can be used to create those.
		 *
		 * @param {String} n Name of the control to create.
		 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
		 * @return {tinymce.ui.Control} New control instance or null if no control was created.
		 */
		createControl: function(n, cm) {
			return null;
		}

	});

	// Register plugin
	tinymce.PluginManager.add('textbookbuttons', tinymce.plugins.textbookbuttons);
})();