/** Инициализация сервиса yandex.share на странице редактирования страницы */
$(function() {
	if (!uAdmin || !uAdmin.data || !uAdmin.data.data || !uAdmin.data.data.page) {
		return;
	}

	$.getScript('//yandex.st/share/share.js', function() {
		var link = location.protocol + '//' + uAdmin.data.domain + '/~/' + uAdmin.data.data.page.id + '/';
		var title = uAdmin.data.data.page.name;

		new Ya.share({
			'element': 'ya_share1',
			'elementStyle': {
				'type': 'button',
				'linkIcon': true,
				'border': false,
				'quickServices': ['yaru', 'vkontakte', 'facebook', 'twitter', 'odnoklassniki', 'moimir', 'lj']
			},
			'link': link,
			'title': title,
			'popupStyle': {
				'copyPasteField': true
			}
		});
	});
});
