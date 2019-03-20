/** Инициализация виджета выбора доставки на странице редактирования заказа */

jQuery(document).ready(function () {
	var orderId = uAdmin.data.data.object.id,
		deliveryId,
		city = $('td[field-name="city"]').text().trim(),
		fieldGroups = _.where(uAdmin.data.data.object.properties.group, {name: "order_delivery_props"}),
		infoBuilder,
		deliverySettings = {},
		$pointOutInfoPanel = $('#pointInInfo'),
		isApiShipOrderCreated = false,
		$sendToApiShip = $('#sendApiShipOrderRequest');

	if (fieldGroups.length > 0) {
		deliveryId = fieldGroups[0].field[0].values.item[0].id;
		infoBuilder = new prettyInfoAboutDelivery({
			el: '#order_delivery_apiship_type',
			deliveryId: deliveryId,
			onBuildDom: function (result) {
				$('#order_delivery_apiship_type').html(result);
			}
		});
		_.each($('#order_delivery_apiship input[type=hidden]'), function (item) {
			var $item = $(item);
			var fieldID = getFieldId($item);
			if (fieldID) {
				deliverySettings[fieldID] = $item.val();
			}
		});

		$.ajax({
			url: '//' + window.location.host + '/admin/emarket/isOrderSentToApiShip/' + deliveryId + '/' + orderId + '/.json',
			dataType: 'json'
		}).done(function (response) {
			if (_.isUndefined(response.data)) {
				isApiShipOrderCreated = !!response.result;
				if (isApiShipOrderCreated) {
					$sendToApiShip.text(getLabel('js-api-ship-label-update-order'));
				}
			}
		});

		infoBuilder.updateInfo(deliverySettings);

		$('#showWidgetOrderDelivery').on('click', function () {
			openDialog('', getLabel('js-asw-dialog-title'), {
				stdButtons: false,
				html: '<div id="apiShipWidget"></div>',
				width: 730,
				openCallback: function () {
					new widgetApiShipDelivery({
						el: '#apiShipWidget',
						orderId: orderId,
						deliveryId: deliveryId,
						city: city,
						onSelect: updateDeliveryInfo,
						onCancel: function () {
							closeDialog();
						}
					});
				},
				confirmCallback: function () {
				}
			});
		});

		$sendToApiShip.click(function () {
			var deliveryDate = new Date($('input[name$="[delivery_date]"]').val()),
				shipmentDate = new Date($('input[name$="[pickup_date]').val()),
				apiFunction = 'sendApiShipOrderRequest';


			if (_.isNaN(deliveryDate.getDate()) || _.isNaN(shipmentDate.getDate())) {
				openDialog(getLabel('js-delivery-date-no-filled-error'), getLabel('js-asw-label-error'));
				return false;
			}

			if (deliveryDate <= shipmentDate) {
				openDialog(getLabel('js-delivery-date-no-valid-error'), getLabel('js-asw-label-error'));
				return false;
			}

			if (isApiShipOrderCreated){
				apiFunction = 'sendApiShipUpdateOrderRequest'
			}

			$.ajax({
				url: '//' + window.location.host + '/admin/emarket/'+apiFunction+'/' + deliveryId + '/' + orderId + '/.json',
				dataType: 'json'
			}).done(function (result) {
				if (!_.isUndefined(result.data) && !_.isUndefined(result.data.error)) {
					alert(getLabel('js-asw-label-error') + ': ' + result.data.error);
				} else {
					alert(getLabel('js-asw-label-success'));
				}
			});
		});
	}

	/**
	 * Обновляет информацию о выбранной доставке ApiShip
	 * @param params
	 */
	function updateDeliveryInfo(params) {
		closeDialog();
		$pointOutInfoPanel.html(getLabel('js-point-not-select'));
		infoBuilder.updateInfo(params);
		_.each(params, function (val, key) {
			var excludedFields = ['delivery_point_out_info', 'delivery_tariff_info'];
			if (_.indexOf(key, excludedFields) > -1) {
				return false;
			}
			$('input[name="data[' + orderId + '][' + key + ']"]').val(val);
		});
		deliverySettings = params;
	}

	/**
	 * Получает id поля настроек из имени контрола
	 * @param fieldObject
	 * @returns {*}
	 */
	function getFieldId(fieldObject) {
		var regex = /^data\[[\d]+\]\[([\S]+)\]$/,
			name = fieldObject.attr('name'),
			fieldName = regex.exec(name);
		if (fieldName.length > 1) {
			return fieldName[1];
		}
		return false;
	}


});
