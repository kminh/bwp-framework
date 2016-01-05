/*global jQuery,anchors,bwp_common*/
jQuery(function($) {
	'use strict';

	// do these once on document ready
	bwp_common.enhance_form_fields();

	// allow easy referencing inside admin pages
	anchors.add('.bwp-option-page h3');

	// load feeds
	function load_feed(url, container) {
		// don't bother loading the feed if the container is not there
		if (container.length === 0) {
			return;
		}

		$.get(url, function(posts) {
			$.each(posts, function(i, post) {
				var $li = $('<li>');

				$('<a>', { text: post.title, })
					.attr('href', post.permalink
						+ '?utm_source=' + container.data('pluginKey')
						+ '&utm_medium=feed'
						+ '&utm_campaign=sidebar-2015'
					)
					.appendTo($li);

				$li.appendTo(container.find('.bwp-feed'));
			});

			container.find('.bwp-loader').hide();
		});
	}

	load_feed('http://betterwp.net/wp-json/bwp/v1/news', $('#bwp-news'));
	load_feed('http://betterwp.net/wp-json/bwp/v1/gems', $('#bwp-gems'));
});
