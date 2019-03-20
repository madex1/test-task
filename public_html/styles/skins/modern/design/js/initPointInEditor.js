/** Инициализирует поле редактирования точки отгрузки товара */


jQuery(document).ready(function () {
	var orderId = uAdmin.data.data.object.id,
		deliveryId,
		providerId,
		city = '',
		fieldGroups = _.where(uAdmin.data.data.object.properties.group, {name: "order_delivery_props"}),
		$pointInFieldInput = $('input[name="data[' + orderId + '][delivery_point_in]"]'),
		pointInfoUrl = '//' + window.location.host + '/admin/emarket/getApiShipDeliveryPointById/',
		storeInfoUrl = '//' + window.location.host + '/admin/emarket/flushDefaultStoreAttributes/.json',
		pointInId = $pointInFieldInput.val(),
		pointInfoTpl=null,
		$infoPanel = $('#pointInInfo');

	$.get('/styles/skins/modern/design/js/pointInfoTpl.html').done(function (response) {
		pointInfoTpl = _.template(response);
	});

	$.ajax({
		url: storeInfoUrl,
		dataType: 'json'
	}).done(function (result) {
		if (!_.isUndefined(result.city)) {
			city = result.city;
		} else {
			setDataError(getLabel('js-error-city-out-info-error'));
		}
	}).fail(function () {
		setDataError(getLabel('js-error-city-out-info-error'));
	});


	deliveryId = fieldGroups[0].field[0].values.item[0].id;
	providerId = $('input[name="data[' + orderId + '][delivery_provider]"]').val();

	$('#showPointInEditor').on('click', function () {
		openDialog('', getLabel('js-title-pointIn-editor'), {
			stdButtons: false,
			html: '<div id="apiShipWidget" class="fixfilters"></div>',
			width: 730,
			openCallback: function () {
				new widgetApiShipDelivery({
					el: '#apiShipWidget',
					orderId: orderId,
					deliveryId: deliveryId,
					city: city,
					providerId: providerId,
					selectPointMode: true,
					onSelect: updatePointInInfo,
					onCancel: function () {
						closeDialog();
					}
				});
			},
			confirmCallback: function () {
			}
		});
	});

	if (pointInId) {
		pointInfoUrl += deliveryId + '/' + pointInId + '/.json';

		$.ajax({
			url: pointInfoUrl,
			dataType: 'json'
		}).done(function (result) {
			var data = result.rows[0];
			data['address'] = data.streetType + ' ' + data.street + ', ' + data.house;
			$infoPanel.html(pointInfoTpl(data));
		});
	}

	/**
	 * Обновляет информацию о точке приема товара
	 * @param data
	 */
	function updatePointInInfo(data) {
		closeDialog();
		if (_.isUndefined(data.error)) {
			$pointInFieldInput.val(data.delivery_point_out);
			$infoPanel.html(pointInfoTpl(data.delivery_point_out_info));
		} else {
			$pointInFieldInput.val('');
			$infoPanel.text(data.error);
		}
	}

	function setDataError(error){
		if (!pointInId) {
			$infoPanel.html(error);
		}
		$('#showPointInEditor').remove();
	}

});