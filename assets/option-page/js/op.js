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
					.attr('target', '_blank')
					.attr('href', post.permalink
						+ '?utm_source=' + container.data('pluginKey')
						+ '&utm_medium=feed'
						+ '&utm_campaign=sidebar-2016'
						+ (post.just_in ? '&utm_content=justin' : '')
					)
					.appendTo($li);

				// indicate fresh articles
				if (post.just_in) {
					$('<span />', {
						'class': 'bwp-justin',
						text: ' Just in!'
					})
					.appendTo($li)
				}

				$('<br />').appendTo($li);

				if (post.views) {
					var span_tpl = post.comment_count
						? ''
							+ parseInt(post.comment_count, 10)
							+ ' '
							+ '<span class="bwp-meta">comments</span>'
							+ '<span class="bwp-meta"> / </span>'
						: '';

					span_tpl = span_tpl
						+ parseInt(post.views / 1000, 10)
						+ 'k+ <span class="bwp-meta">views</span>';

					$('<span />', {
						html: span_tpl
					})
					.appendTo($li);
				} else {
					$('<span />', {
						'class': 'bwp-meta',
						text: post.post_date_gmt
					})
					.appendTo($li);
				}

				$li.appendTo(container.find('.bwp-feed'));
			});

			container.find('.bwp-loader').hide();
		});
	}

	load_feed('http://betterwp.net/wp-json/bwp/v1/news', $('#bwp-news'));
	load_feed('http://betterwp.net/wp-json/bwp/v1/gems', $('#bwp-gems'));
});
