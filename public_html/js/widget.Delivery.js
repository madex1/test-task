/** Виджет выбора провайдера, тарифа и пункта выдачи доставки ApiShip */
var widgetApiShipDelivery = function (options) {
	var $el = options.el || null, // Элемент внутри которого будет отрисовываться виджет
		onSelect = options.onSelect || null, // Фукнция обратного вызова которая вызывается при нажатии на кнопку выбора
		orderId = options.orderId || 0, // ИД заказа который редактируется
		deliveryId = options.deliveryId || 0, // ИД типа доставки
		city = options.city || '', // Город куда будет осуществляться доставка
		host = window.location.host, // Хост текущей страницы
		hasDeliveryToDoor = false, // Получены ли данные о доставке до двери
		hasDeliveryToPoint = false, // Получены ли данные о доставке в пункт выдачи
		$filterLayout, // Область с фильтрами по типу пункта выдачи
		myMap = {}, // объект яндекс карты
		balloonTemplate = {}, // Шаблон для метки с информацией о точке выдачи
		markers = {}, // структура типа objectManager с массивом маркеров на яндекс карте
		mapZoom = 11, // Масштаб карты по умолчанию
		delivery_provider = options.providerId || '', // выбранный провайдер доставки id
		delivery_tariff = '', // выбранный тариф доставки id
		delivery_type = '', // выбранный тип доставки
		delivery_point_out = '', // выбранная точка доставки
		delivery_price = '', // выбранная цена доставки зависит от тарифа
		delivery_point_out_info = '', // информация о выбранной точке доставки
		delivery_tariff_info = '', // информация о выбранной точке доставки
		deliveryTypeToPoint = 2, // Тип доставки в пункт выдачи
		deliveryTypeToDoor = 1, // Тип доставки до двери
		selectPointMode = options.selectPointMode || false, //Переключает виджет в режим выбора точки доставки. Необходимы параметры city и delivery_provider
		disableCloseButton = options.disableCloseButton || false, // Спрятать кнопку закрыть для диалогов
		tariffsInfo = {}, //Сохраненная информация о тарифах
		disableToDoorSlider = options.disableToDoorSlider || false; //Отключение слайдера в разделе доставка до двери
	/**
	 * Выполняет запрос к api бекенда apiship
	 * @param query
	 * @returns {*}
	 */
	function query(query) {
		return $.ajax({
			url: '//' + host + '/' + query,
			dataType: 'json'
		})
	}

	/**
	 * Строит верстку самого виджета
	 * @param data - данные о тарифах полученные от api
	 */
	function buildDom(data) {
		var html = ['<div class="d_tab row">'],
			toDoor = '', toPoint = '';

		if (!_.isUndefined(data['deliveryToDoor']) && data['deliveryToDoor'].length > 0) {
			toDoor = '<div id="toDoor" class="d_tab_panel">';

			_.each(data['deliveryToDoor'], function (item) {
				if (item['tariffs'].length == 0) {
					return false;
				}

				hasDeliveryToDoor = true;
				toDoor += [
					'<div class="accordion">',
					'<img src="/styles/skins/modern/design/img/delivery_providers/' + item['providerKey'] + '.png" alt="' + item['providerKey'] + '"/>',
					'</div>'
				].join('');
				toDoor += '<div class="accordion-panel">';

				_.each(item['tariffs'], function (tariff) {
					toDoor += [
						'<p><label>',
						'<input type="radio" name="tariff" value="' + tariff['tariffId'] + '" dp-price="',
						tariff['deliveryCost'] + '" dp-date="' + tariff['daysMax'] + '" dp-name="'+tariff['tariffName']+'" />',
						tariff['tariffName'] + ' - ' + tariff['deliveryCost'] + ' руб.',
						'</label></p>'
					].join('');
					saveTariffInfo(item['providerKey'], tariff);
				});

				toDoor += '<p><a class="btn color-blue btn-small door_save" data_provider="' +
					item['providerKey'] + '">' + getLabel('js-asw-select-button') + '</a></p>';
				toDoor += '</div>';
			});

			toDoor += '</div>';
		}

		if (!_.isUndefined(data['deliveryToPoint']) && data['deliveryToPoint'].length > 0) {
			toPoint += '<div id="toPoint" class="d_tab_panel" style="display:none;">';
			toPoint += '<div class="d_tp_wrapper"><div class="map" id="DMap"></div>';
			toPoint += '<div class="list mCustomScrollbar">';
			toPoint += '<div class="filters"></div>';

			_.each(data['deliveryToPoint'], function (item) {
				if (item['tariffs'].length == 0 && !selectPointMode) {
					return false;
				}

				hasDeliveryToPoint = true;
				toPoint += [
					'<div class="accordion" dp-provider="' + item['providerKey'] + '">',
					'<img src="/styles/skins/modern/design/img/delivery_providers/' + item['providerKey'] + '.png" alt="' + item['providerKey'] + '"/>',
					'</div>'
				].join('');
				toPoint += '<div class="accordion-panel" dp-provider="' + item['providerKey'] + '">';

				_.each(item['tariffs'], function (tariff) {
					toPoint += [
						'<p><label>',
						'<input type="radio" name="tariff" dp-name="'+tariff['tariffName']+'" value="' + tariff['tariffId'] + '" dp-price="' + tariff['deliveryCost'] + '" dp-date="' + tariff['daysMax'] + '" />',
						tariff['tariffName'] + ' - ' + tariff['deliveryCost'] + ' руб.',
						'</label></p>'

					].join('');
					saveTariffInfo(item['providerKey'], tariff);
				});

				toPoint += '<div><ul class="dp_pointlist"></ul></div>';
				toPoint += '</div>';
			});

			toPoint += '</div></div></div>';
		}

		if (!hasDeliveryToDoor && !hasDeliveryToPoint) {
			alert(getLabel('js-choose-error-no-eligible-providers'));
			return;
		}

		if (hasDeliveryToDoor && hasDeliveryToPoint) {
			html.push('<div class="d_tab_btn active" wdata="toDoor">' + getLabel('js-asw-tab-label-to-door') + '</div>');
			html.push('<div class="d_tab_btn" wdata="toPoint">' + getLabel('js-asw-tab-label-to-point') + '</div>');
		}

		html.push('</div>');

		if (hasDeliveryToDoor) {
			html.push(toDoor);
		}

		if (hasDeliveryToPoint) {
			html.push(toPoint);
		}

		if (!disableCloseButton) {
			html.push([
				'<div class="row">',
				'<a class="btn color-blue btn-small pull-right" style="margin: 0 20px ;" id="wdCloseDialog">' + getLabel('js-asw-cancel-button') + '</a>',
				'</div>'
			].join(''));
		}

		$el.html(html.join(''));

		$(".d_tab_btn", $el).on('click', function (e) {
			var parent = $el,
				$el = $(e.target),
				tab_id = $el.attr('wdata');
			$('.d_tab_panel', parent).hide();
			$(".d_tab_btn", parent).removeClass('active');
			$('#' + tab_id, parent).show();
			$el.addClass('active');
		});

		$('.accordion', $el).on('click', function (e) {
			$(e.currentTarget).toggleClass("active");
			e.currentTarget.nextElementSibling.classList.toggle("show");
		});

		$('.list.mCustomScrollbar', $el).mCustomScrollbar({
			scrollbarPosition: 'outside'
		});

		if (!disableToDoorSlider) {
			$('#toDoor', $el).mCustomScrollbar();
		}

		$('#wdCloseDialog').on('click', function () {
			closeDialog();
		});

		if (hasDeliveryToPoint) {
			initMap();

			if (!selectPointMode) {
				initFilters();
			}
		}

		if (hasDeliveryToDoor) {
			$('a.btn.door_save', $el).on('click', function (e) {
				var $el = $(e.currentTarget);
				selectTariff($el.attr('data_provider'));
			});
		}
	}

	/**
	 * Инициализируем Яндекс.Карту для точек выдачи
	 * @returns {boolean}
	 */
	function initMap() {
		if (!hasDeliveryToPoint) {
			return false;
		}
		if (!hasDeliveryToDoor && hasDeliveryToPoint) {
			$('#toPoint', $el).show();
		}
		ymaps.ready(function () {
			balloonTemplate = ymaps.templateLayoutFactory.createClass([
				'<p>' + getLabel('js-asw-info-provider') + ' {{ properties.provider }}</p>' +
				'<p><strong>' + getLabel('js-asw-info-address') + '</strong>: {{ properties.address }}</p>' +
				'<p><strong>' + getLabel('js-asw-info-timetable') + '</strong>: {{ properties.timetable }}</p>'
			].join(''));
			markers = new ymaps.ObjectManager({clusterize: false});
			markers.objects.options.set({
				preset: 'islands#blueClusterIcons',
				balloonContentLayout: balloonTemplate
			});

			markers.objects.options.set({
				preset: 'islands#blueClusterIcons',
				balloonContentLayout: balloonTemplate
			});


			ymaps.geocode(city).then(function (res) {
				myMap = new ymaps.Map("DMap", {
					center: res.geoObjects.get(0).geometry.getCoordinates(),
					zoom: mapZoom,
					controls: ["geolocationControl", "zoomControl"]
				});
				loadPoints();
			}, function (err) {
				alert(getLabel('js-asw-error-town-detect'));
			}).then(function (res) {

			})
		});
	}

	/**
	 * Сохраняет информацию о тарифе для последующей передачи в результате
	 * @param provider - id провайдера
	 * @param tariffInfo - массив информации о тарифе
	 */
	function saveTariffInfo(provider, tariffInfo) {
		if (_.isUndefined(tariffsInfo[provider])) {
			tariffInfo[provider] = {};
		}
		tariffInfo[provider][tariffInfo['tariffId']] = tariffInfo;
	}

	/** Отрисовываем фильтры и инициализируем события изменения фильтров */
	function initFilters() {
		$filterLayout = $('#toPoint .filters');
		query('udata/emarket/getApiShipDeliveryPointsTypes/.json').done(function (response) {
			if (_.isArray(response)) {
				_.each(response, function (item) {
					$filterLayout.append($([
						'<p><input type="checkbox" id="cbFilter' + item.id + '" data="' + item.id + '" />',
						'<label for="cbFilter' + item.id + '"><span></span>' + item.name + '</label></p>'
					].join('')))
				});
				$('input[type=checkbox]', $filterLayout).change(function () {
					filterChangeHandler();
				})
			}
		});
	}

	/** Обработчик события изменения фильтра */
	function filterChangeHandler() {
		var $checkedFilters = $('input[type=checkbox]:checked', $filterLayout),
			filter = [];
		if ($checkedFilters.length > 0) {
			_.each($checkedFilters, function (item) {
				item = $(item);
				filter.push('properties.type == ' + item.attr('data'));
			});
			markers.setFilter(filter.join(' || '));
		} else {
			markers.setFilter('');
		}
	}

	/**
	 * Загружает точки выдачи для переданных провайдеров доставки, помещает маркеры на карту
	 * и инициализирует события взаимодействия с картой
	 */
	function loadPoints() {
		var elementsList = $('#toPoint .accordion[dp-provider]');
		if (elementsList.length == 0) {
			return false;
		}

		_.each(elementsList, function (element) {
			var provider = $(element).attr('dp-provider'),
				pointTypes = [3];
			if (selectPointMode){
				pointTypes.push(1)
			} else {
				pointTypes.push(2)
			}

			$.when(
				query('udata/emarket/getApiShipPointsByProviderAndCity/' + deliveryId + '/' + city + '/' + provider + '/'+pointTypes[0]+'.json'),
				query('udata/emarket/getApiShipPointsByProviderAndCity/' + deliveryId + '/' + city + '/' + provider + '/'+pointTypes[1]+'.json')
			).done(function (response1, response2) {
				var points = [];
				if (!_.isUndefined(response1[0].rows)){
					points = points.concat(response1[0].rows)
				}
				if (!_.isUndefined(response2[0].rows)){
					points = points.concat(response2[0].rows)
				}
				if (points.length > 0) {
					_.each(points, function (point) {
						initPoint(point);
					});
					myMap.geoObjects.add(markers);
				} else if (selectPointMode && elementsList.length == 1) {
					if (_.isFunction(onSelect)){
						onSelect({
							error:getLabel('js-error-no-point')
						})
					} else {
						$el.html($(document.createElement('h2')).text(getLabel('js-error-no-point')));
					}
				}

			})
		});
	}

	/**
	 * Добавляем загруженные точки на карту и инициализируем связанные события
	 * @param point
	 */
	function initPoint(point) {
		var $point,
			address = getAddress(point),
			$pointList = $('ul.dp_pointlist', $('div[dp-provider="' + point.providerKey + '"]'));

		markers.add({
			type: 'Feature',
			id: point.id,
			geometry: {
				type: 'Point',
				coordinates: [point.lat, point.lng]
			},
			properties: {
				address: address,
				timetable: point.timetable,
				provider: point.providerKey,
				id: point.id,
				balloonContent: point.name,
				name: point.name,
				phone: point.phone,
				type: point.type
			}
		});

		$point = $([
			'<li dp_id="' + point.id + '" class="dp_point">',
			'<span class="dp_point_label"  dp_id="' + point.id + '" >' + point.name + '</span>',
			'<span class="dp_point_info">',
			'<span>',
			'<p><strong>' + getLabel('js-asw-info-address') + ': </strong>' + address + '</p>',
			'<p><strong>' + getLabel('js-asw-info-phone') + ': </strong>' + point.phone + '</p>',
			'<p><strong>' + getLabel('js-asw-info-timetable') + ': </strong>' + point.timetable + '</p>',
			'</span>',
			'<p><a class="btn color-blue btn-small" data_pointId="' + point.id + '"',
			' data_provider="' + point.providerKey + '">' + getLabel('js-asw-select-button') + '</a></p>',
			'</span>',
			'</li>'
		].join(''));

		$('span.dp_point_label', $point).on('click', function (e) {
			var $el = $(e.currentTarget),
				id = $el.attr('dp_id'),
				parent = $el.closest('ul.dp_pointlist'),
				mark = markers.objects.getById(id);
			if (!$el.hasClass('active')) {
				$('span.active', parent).removeClass('active');
				$('span.show', parent).removeClass('show');
			}
			$el.toggleClass("active");
			$el[0].nextElementSibling.classList.toggle("show");
			if (mark !== undefined) {
				var state = markers.getObjectState(id);
				if (state.isClustered) {
					markers.clusters.state.set('activeObject', markers.objects.getById(id));
					markers.clusters.balloon.open(state.cluster.id);
				} else {
					if (markers.objects.balloon.isOpen(id)) {
						markers.objects.balloon.close(id);
					} else {
						markers.objects.balloon.open(id);
					}
				}
			}
		});

		$('a.btn', $point).on('click', function (e) {
			var $el = $(e.currentTarget);
			selectPoint($el.attr('data_pointId'), $el.attr('data_provider'));
		});

		$pointList.append($point);

		markers.objects.events.add('click', function (e) {
			var marker = markers.objects.getById(e.get('objectId'));
			activatePoint(marker.properties.provider, marker.properties.id);

		});
	}

	/**
	 * Возвращает строку адреса для точки выдачи
	 * @param data
	 * @returns {string}
	 */
	function getAddress(data) {
		return data.streetType + '. ' + data.street + ', ' + data.house;
	}

	/**
	 * Активирует пункт пункта выдачи в списке тарифов и пунктов при выборе маркера на карте
	 * @param provider - ид провайдера доставки
	 * @param id - ид точки
	 */
	function activatePoint(provider, id) {
		var $parent = $('div#toPoint'),
			label = $('.accordion[dp-provider="' + provider + '"]', $parent),
			point;
		if (!label.hasClass('active')) {
			label.toggleClass("active");
			showNextSibling(label);
		}
		point = $('.dp_point_label[dp_id=' + id + ']', $parent);
		if (!point.hasClass('active')) {
			$('span.active', $parent).removeClass('active');
			$('span.show', $parent).removeClass('show');
			point.toggleClass("active");
			showNextSibling(point);

			$('div#toPoint div.list').mCustomScrollbar('scrollTo', point);
		}
	}

	/**
	 * Показывает панель следующую за заголовком аккордеона
	 * @param element
	 */
	function showNextSibling(element) {
		element[0].nextElementSibling.classList.toggle("show");
	}

	/**
	 * Обработчик выбора тарифа и точки выдачи доставки до точки выдачи
	 * @param id  - ид точки выдачи
	 * @param provider - ид провайдера доставки
	 */
	function selectPoint(id, provider) {
		var $tariff = $('#toPoint div[dp-provider="' + provider + '"] input[type="radio"]:checked');
		if ($tariff.length == 1 || selectPointMode) {
			delivery_type = deliveryTypeToPoint;
			delivery_tariff = $tariff.val();
			delivery_point_out = id;
			delivery_provider = provider;
			delivery_price = parseFloat($tariff.attr('dp-price'));
			delivery_point_out_info = markers.objects.getById(id);

			if (!_.isEmpty(delivery_point_out_info)) {
				delivery_point_out_info = delivery_point_out_info.properties;
			}

			delivery_tariff_info = {
				name: $tariff.attr('dp-name')
			};

			if (_.isFunction(onSelect)) {
				onSelect(getValue())
			}
		} else {
			alert(getLabel('js-asw-select-tariff-error'));
		}
	}

	/**
	 * Обработчик выбора тарифа доставки до двери
	 * @param provider - ид провайдера доставки
	 */
	function selectTariff(provider) {
		var $tarif = $('#toDoor input[type="radio"]:checked');
		if ($tarif.length == 1) {
			delivery_type = deliveryTypeToDoor;
			delivery_tariff = $tarif.val();
			delivery_point_out = '';
			delivery_provider = provider;
			delivery_price = parseFloat($tarif.attr('dp-price'));
			delivery_point_out_info = '';

			delivery_tariff_info = {
				name: $tarif.attr('dp-name')
			};

			if (_.isFunction(onSelect)) {
				onSelect(getValue())
			}
		} else {
			alert(getLabel('js-asw-select-tariff-error'));
		}
	}

	/**
	 * Возвращает объект с данными о выбранном тарифе и точке выдачи (если необходимо)
	 * @returns {{delivery_type: string, delivery_tariff: string, delivery_point_out: string, delivery_provider: string, delivery_price: string}}
	 */
	function getValue() {
		var output = {
			delivery_type: delivery_type,
			delivery_tariff: delivery_tariff,
			delivery_point_out: delivery_point_out,
			delivery_provider: delivery_provider,
			delivery_price: delivery_price,
			delivery_tariff_info: delivery_tariff_info
		};

		var tariffsDefined = !_.isUndefined(tariffsInfo[delivery_provider]);
		var selectedTariffDefined = (tariffsDefined && !_.isUndefined(tariffsInfo[delivery_provider][delivery_tariff]));

		if (tariffsDefined && selectedTariffDefined) {
			output['delivery_tariff_info'] = tariffsInfo[delivery_provider][delivery_tariff];
		}

		if (!_.isEmpty(delivery_point_out_info)) {
			output['delivery_point_out_info'] = delivery_point_out_info;
		}

		return output
	}

	/** Проверяет корректны ли данные выбранной доставки */
	function validate() {
		if (!_.isNumber(delivery_type)) {
			return false;
		}

		if (delivery_provider === '' || !_.isString(delivery_provider)) {
			return false;
		}

		if (!_.isNumber(delivery_tariff) && !_.isNumber(delivery_price)) {
			return false;
		}

		if (delivery_type === deliveryTypeToPoint) {
			if (delivery_point_out === '' || _.isUndefined(delivery_point_out)) {
				return false;
			}
		}

		return true;
	}

	/** Инициализация виджета. Загрузка данных о доступных тарифов доставки */
	function init() {
		$el = $($el);
		if (selectPointMode) {
			buildDom({
				deliveryToPoint: [
					{
						providerKey: delivery_provider,
						tariffs: []
					}

				]
			})
		} else {
			query('udata/emarket/getApiShipDeliveryOptions/' + deliveryId + '/' + orderId + '/.json').done(function (response) {
				$el.removeClass('apishipWidgetLoader');

				if (!_.isNull(response) && !_.isUndefined(response.data)) {
					alert(getLabel('js-choose-error-config'));
				} else {
					buildDom(response);
				}
			})
		}
	}

	init();
	return {
		val: getValue,

		validate: validate
	}
};

/**
 * Класс генерирующий информацию о доставке для отображения на странице настроек
 * @param options
 * @returns {{getInfoHtml: updateInfo}}
 */
var prettyInfoAboutDelivery = function (options) {
	var deliveryId = options.deliveryId || 0,
		onBuildDom = options.onBuildDom || null,
		showCost = options.showCost || false,
		deliveryTypeToPoint = 2,
		deliveryTypeToDoor = 1;

	/**
	 * Выполняет запрос к api бекенда apiship
	 * @param query
	 * @returns {*}
	 */
	function query(query) {
		return $.ajax({
			url: '//' + window.location.host + '/' + query,
			dataType: 'json'
		})
	}

	/**
	 * Возвращает информацию о доставке загружая недостающие данные
	 * @param params - настройки доствки
	 */
	function updateInfo(params) {
		if (areParamsEmpty(params)) {
			return '<p>' + getLabel('js-asw-empty-data') + '</p>';
		} else {
			if (_.isUndefined(params['delivery_tariff_info'])) {
				query('udata/emarket/getApiShipProviderTariffById/' +
					deliveryId + '/' + params['delivery_provider'] + '/' + params['delivery_tariff'] + '/.json'
				).done(function (result) {

					params['delivery_tariff_info'] = result;
					if (params['delivery_type'] == deliveryTypeToPoint && _.isUndefined(params['delivery_point_out_info'])) {

						query('udata/emarket/getApiShipDeliveryPointById/' +
							deliveryId + '/' + params['delivery_point_out'] + '/.json'
						).done(function (response) {
							response = response.rows[0];
							var address = response.streetType + '. ' + response.street + ', ' + response.house;
							params['delivery_point_out_info'] = {
								address: address,
								timetable: response.timetable,
								provider: response.providerKey,
								id: response.id,
								name: response.name,
								phone: response.phone,
								type: response.type
							};
							buildDom(params)
						})

					} else {
						buildDom(params)
					}

				})
			} else {
				buildDom(params)
			}
		}

	}

	/**
	 * Создает разметку информации о доставке
	 * @param params - настройки доставки
	 * @returns {string}
	 */
	function buildDom(params) {
		var html = '';

		if (params['delivery_type'] == deliveryTypeToDoor) {
			html += [
				'<p>' + getLabel('js-asw-delivery-type-label') + ' ' + getLabel('js-asw-label-delivery-to-door') + '.</p>',
				'<p><strong>' + getLabel('js-asw-info-provider') + ':</strong> ' + params['delivery_provider'] + '</p>',
				'<p><strong>' + getLabel('js-asw-info-tariff') + ':</strong> ' + getTariffName(params['delivery_tariff_info']),
				(showCost ? ' - ' + params['delivery_price'] + ' руб. ' : '') + '</p>'
			].join('');

		} else if (params['delivery_type'] == deliveryTypeToPoint) {
			html += [
				'<p><strong>' + getLabel('js-asw-delivery-type-label') + ' ' + getLabel('js-asw-label-delivery-to-point') + '.</strong></p>',
				'<p><strong>' + getLabel('js-asw-info-provider') + ':</strong> ' + params['delivery_provider'] + '</p>',
				'<p><strong>' + getLabel('js-asw-info-tariff') + ':</strong> ' + getTariffName(params['delivery_tariff_info']),
				(showCost ? ' - ' + params['delivery_price'] + ' руб. ' : '') + '</p>',
				'<p><strong>' + getLabel('js-asw-info-point') + ':</strong> ' + params['delivery_point_out_info'].name + '</p>',
				'<p><strong>' + getLabel('js-asw-info-address') + ':</strong> ' + params['delivery_point_out_info'].address + '</p>',
				'<p><strong>' + getLabel('js-asw-info-phone') + ':</strong> ' + params['delivery_point_out_info'].phone + '</p>',
				'<p><strong>' + getLabel('js-asw-info-timetable') + ':</strong> ' + params['delivery_point_out_info'].timetable + '</p>'

			].join('');
		}
		if (_.isFunction(onBuildDom)) {
			onBuildDom(html);
		}

	}

	/**
	 * Возвращает имя тариффа
	 * @param data - информация о тарифе
	 * @returns {*}
	 */
	function getTariffName(data) {
		return data['name'] || data['tariffName'];
	}

	/**
	 * Проверяет заполненны ли все поля параметров
	 * @param params - переданный массив параметров
	 * @returns {*}
	 */
	function areParamsEmpty(params) {
		var result = null;
		_.each(params, function (val) {
			result = _.isNull(result) ? _.isEmpty(val) : result && _.isEmpty(val);
		});
		return result;
	}

	return {
		updateInfo: updateInfo
	}
};

