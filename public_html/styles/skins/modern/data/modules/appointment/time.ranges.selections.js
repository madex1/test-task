$(document).ready(
	function() {
		$('.work_time_range').each(function() {
			var selectFrom = $('.work_time_from', this).selectize({
				plugins: ['remove_button', 'clear_selection'],
				allowEmptyOption: false,
				create: false
			});

			var selectTo = $('.work_time_to', this).selectize({
				plugins: ['remove_button', 'clear_selection'],
				allowEmptyOption: false,
				create: false
			});

			var selectizeFrom = selectFrom[0].selectize;
			var selectizeTo = selectTo[0].selectize;
			var savedOptions = selectizeFrom.options;
			var disabledOptionValue = -1;
			var from = $(this).data('from');
			var to = $(this).data('to');
			var lock = false;

			$('.work_time_clear', this).click(function(){
				selectizeFrom.clear(true);
				selectizeFrom.addOption({
					value: disabledOptionValue,
					text: from
				});
				selectizeFrom.addItem(disabledOptionValue, true);

				selectizeTo.clear(true);
				selectizeTo.addOption({
					value: disabledOptionValue,
					text: to
				});
				selectizeTo.addItem(disabledOptionValue, true);
			});

			var optionsLocker = function(select, value, mode, name) {
				if (value == disabledOptionValue || lock == true) {
					return;
				}

				lock = true;
				selectize = select[0].selectize;
				var selectedItem = selectize.items;
				selectize.clearOptions();

				for (key in savedOptions) {
					var savedOption = savedOptions[key];
					selectize.addOption({
						value: savedOption.value,
						text: savedOption.text
					});
				}

				var options = selectize.options;
				var selectedOption = options[value];
				var selectedOptionOrder = selectedOption.$order;

				for (key in options) {
					var option = options[key];
					if (mode == 'more' && selectedOptionOrder <= option.$order) {
						selectize.removeOption(option.value);
					}

					if (mode == 'less' && selectedOptionOrder >= option.$order) {
						selectize.removeOption(option.value);
					}
				}

				selectedItem.map(function(value) {
					selectize.addItem(value);
				});

				addDefaultOption(selectize, name);

				lock = false;
			}

			var clearDefaultOption  = function(selectize) {
				selectize.removeOption(disabledOptionValue)
			}

			var addDefaultOption = function(selectize, name) {
				if (selectize.items.length == 0) {
					selectize.addOption({
						value: disabledOptionValue,
						text: name
					});
					selectize.addItem(disabledOptionValue, true);
				}
			}

			selectizeFrom.on('item_add', function(value) {
				optionsLocker(selectTo, value, 'less', to);
			});

			selectizeFrom.on('focus', function() {
				clearDefaultOption(selectizeFrom);
			});

			selectizeFrom.on('blur', function() {
				lock = true;
				addDefaultOption(selectizeFrom, from);
				lock = false;
			});

			selectizeTo.on('item_add', function(value) {
				optionsLocker(selectFrom, value, 'more', from);
			});

			selectizeTo.on('focus', function() {
				clearDefaultOption(selectizeTo);
			});

			selectizeTo.on('blur', function() {
				lock = true;
				addDefaultOption(selectizeTo, to);
				lock = false;
			});
		});
	}
);