(function ($) {

	$('table.posts #the-list, table.pages #the-list').sortable({
		'items': 'tr',
		'axis': 'y',
		'helper': fixHelper,
		'update': function (e, ui) {
			$.post(ajaxurl, {
				action: 'update-menu-order',
				order: $('#the-list').sortable('serialize'),
			});
			//console.log(e, ui);
		}
	});

	$('table.tags #the-list').sortable({
		'items': 'tr',
		'axis': 'y',
		'helper': fixHelper,
		'update': function (e, ui) {
			$.post(ajaxurl, {
				action: 'update-menu-order-tags',
				order: $('#the-list').sortable('serialize'),
			});
			//console.log(e, ui);
		}
	});

	var fixHelper = function (e, ui) {
		ui.children().children().each(function () {
			$(this).width($(this).width());
		});
		//console.log(e, ui);
		return ui;
	};

})(jQuery);
