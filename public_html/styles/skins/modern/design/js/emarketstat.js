function EmarketStat() {
	var self = this;
	var curInst = 0;

	this.start = function () {
		this.prepare();
		curInst++;
		this.runStat(jQuery("#statTable .stat").first(), curInst, true, true);
	};

	this.startOne = function(stat, flag) {
		jQuery(stat).removeClass('error');
		this.runStat(jQuery(stat.parent()), curInst, false, flag);
	}

	this.prepare = function () {
		jQuery("#statTable .stat .stat-value").empty();
		jQuery("#statTable .stat .stat-value-all").empty();
	};

	this.runStat = function (stat, inst, isNextNeeded, flag) {
		if ( !jQuery(stat).data('id') ) return;
		var fromDate = new Date(jQuery('#fromDate').val().replace(/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/, '$1-$2-$3'))
		var toDate = new Date(jQuery('#toDate').val().replace(/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/, '$1-$2-$3'))

		fromDate = ((fromDate/1 + fromDate.getTimezoneOffset() * 60000)/1000 - (fromDate%1))
		toDate = ((toDate/1 + toDate.getTimezoneOffset() * 60000)/1000 - (toDate%1))

		if (flag == 'one' || flag == true) {
			jQuery.ajax({
				url: "/admin/emarket/statRun/" + jQuery(stat).data('id') + '/' + fromDate + '/' + toDate + '/?random=' + Math.random(),
				async: true,
				dataType: 'json',
				timeout: 30000,

				beforeSend: function() {
					jQuery(".stat-value", stat).html('<img src="/images/cms/admin/mac/table/loading.gif" />');

					if(stat.next() && isNextNeeded) {
						self.runStat(stat.next(), inst, true, true);
					}
				},
				success: function(data) {
					if (curInst != inst) return;
					if (data.result.status) {
						jQuery(".stat-value", stat).text(data.result.value);
					} else {
						jQuery(".stat-value", stat).html('<span class="runOneStat">' + getLabel('js-index-stat-problem') + '</span>');
						jQuery(".stat-value", stat).addClass("error");
					}
				},

				error: function() {
					if (curInst != inst) return;
					jQuery(".stat-value", stat).html('<span class="runOneStat">' + getLabel('js-index-stat-problem') + '</span>');
					jQuery(".stat-value", stat).addClass("error");
				}
			});
		}

		if (flag == 'all' || flag == true) {
			jQuery.ajax({
				url: "/admin/emarket/statRun/" + jQuery(stat).data('id') + '/' + fromDate + '/' + toDate + '/all/' + '?random=' + Math.random(),
				async: true,
				dataType: 'json',
				timeout: 30000,

				beforeSend: function() {
					jQuery(".stat-value-all", stat).html('<img src="/images/cms/admin/mac/table/loading.gif" />');
				},
				success: function(data) {
					if (curInst != inst) return;
					if (data.result.status) {
						jQuery(".stat-value-all", stat).text(data.result.value);
					} else {
						jQuery(".stat-value-all", stat).html('<span class="runOneStatAll">' + getLabel('js-index-stat-problem') + '</span>');
						jQuery(".stat-value-all", stat).addClass("error");
					}
				},

				error: function() {
					if (curInst != inst) return;

					jQuery(".stat-value-all", stat).html('<span class="runOneStatAll">' + getLabel('js-index-stat-problem') + '</span>');
					jQuery(".stat-value-all", stat).addClass("error");
				}
			});
		}
	}

	this.topPopular = function(elem, sort) {
		if (sort == 'undefined') {
			sort = amount;
		}
		var fromDate = new Date(jQuery('#fromDate').val().replace(/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/, '$1-$2-$3'))
		var toDate = new Date(jQuery('#toDate').val().replace(/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/, '$1-$2-$3'))

		fromDate = ((fromDate/1 + fromDate.getTimezoneOffset() * 60000)/1000 - (fromDate%1))
		toDate = ((toDate/1 + toDate.getTimezoneOffset() * 60000)/1000 - (toDate%1))

		jQuery.ajax({
			url: "/admin/emarket/getMostPopularProduct/" + fromDate + '/' + toDate + '/?sort=' + sort + '&random=' + Math.random(),
			async: true,
			dataType: 'json',
			timeout: 30000,

			beforeSend: function() {
				jQuery(elem).find('tbody').find('tr').remove();
				jQuery(jQuery(elem).find('tbody')).append('<tr><td colspan="4" style="text-align:center"><img src="/images/cms/admin/mac/table/loading.gif" /></td></tr>');
			},
			success: function(data) {
				jQuery(elem).find('tbody').find('tr').remove();
				if (data.result.length < 1) {
					jQuery(jQuery(elem).find('tbody')).append('<tr><td colspan="4" style="text-align:center"><span class="runOneStat">Ничего не найдено</span></td></tr>');
				}
				for (var item in data.result) {
					var val = data.result[item];
					jQuery(elem).find('tbody').append('<tr>' +
						'<td>' + (parseInt(item)+1) + '</td><td><a href="/admin/catalog/edit/' + val.id + '">' + val.title + '</a></td>' + '<td>' + val.amount + '</td>' + '<td>' + val.total_price + '</td>' +
						'</tr>'
					)
				}
			},
			error: function() {
				jQuery(elem).find('tbody').find('tr').remove();
				jQuery(jQuery(elem).find('tbody')).append('<tr><td colspan="4" style="text-align:center"><span class="runOneStat">' + getLabel('js-index-stat-problem') + '</span></td></tr>');
			}
		});
	}
}

jQuery(function(){
	var statictic = new EmarketStat();
	var activeTab = 'ordersStats';
	var changedDate = false;
	var tabsActual = {'ordersStats':false,'commonStats':false,'topPopularProduct':false};

	var loadData = function() {
		if (tabsActual[activeTab]) {
			return false;
		}
		tabsActual[activeTab] = true;
		switch('#' + activeTab) {
			case '#ordersStats': {
				var fromDate = new Date(jQuery('#fromDate').val().replace(/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/, '$1-$2-$3'))
				var toDate = new Date(jQuery('#toDate').val().replace(/(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/, '$1-$2-$3'))
				fromDate = ((fromDate/1 + fromDate.getTimezoneOffset() * 60000)/1000 - (fromDate%1))
				toDate = ((toDate/1 + toDate.getTimezoneOffset() * 60000)/1000 - (toDate%1))

				jQuery.ajax({
					url: '/admin/emarket/setDateRange/' + fromDate + '/' + toDate + '.xml',
					success: function() {
						oFilterController.applyFilterAdvanced();
					}
				});
				break;
			}
			case '#commonStats': {
				statictic.start();
				break;
			}
			case '#topPopularProduct': {
				var elem = jQuery('#statTopPopular');
				statictic.topPopular(elem);
				break;
			}
		}
	}

	var goFilter = function() {
		tabsActual = {'ordersStats':false,'commonStats':false,'topPopularProduct':false};
		changedDate = false;
		loadData();
	};

	jQuery('#statdate_settings input[type="text"]').keypress(function(e) {
		if(e.which == 13) {
			goFilter();
		}
	});

	jQuery('#startEmarketStat').click(goFilter);

	jQuery(document).on('click', '.runOneStat', function() {
		var elem = jQuery(this).parent();
		statictic.startOne(elem, 'one');
	});

	jQuery(document).on('click', '.runOneStatAll', function() {
		var elem = jQuery(this).parent();
		statictic.startOne(elem, 'all');
	});

	jQuery('#statTopPopular th').click(function(e) {
		var elem = jQuery('#statTopPopular');
		if (this.getAttribute('name')) {
			statictic.topPopular(elem, this.getAttribute('name'));
		}
	})

	jQuery('#statdate_settings .datePicker input').change(function(e) {
		changedDate = true;
	});

	$('.tabs.editing a').on('click',function (){
		var el = $(this),
			id = el.attr('class'),
			oldTab = $('.tabs.editing .section.selected a'),
			oldTabId = oldTab.attr('class');

		$('#'+oldTabId).hide();
		oldTab.parent().removeClass('selected');

		$('#'+id).show();
		el.parent().addClass('selected');

		activeTab = id;
		if (changedDate) {
			tabsActual = {'ordersStats':false,'commonStats':false,'topPopularProduct':false};
			changedDate = false;
		}
		loadData();

	});


});
