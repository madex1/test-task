(function() {
	var checkPrivateMessages = function() {
		$.get('/umess/inbox/?mark-as-opened=1', function(xml) {
			$('message', xml).each(function(index, node) {
				var title = $(node).attr('title');
				var content = $('content', node).text();
				var date = $('date', node).text();
				var sender = $('sender', node).attr('name');

				content = '<p>' + content + '</p><div class="header">' + date + ', ' + sender + '</div>';
				$.jGrowl(content, {
					'header': title,
					'life': 10000
				});
			});
		});

		setTimeout(checkPrivateMessages, 15000);
	};
})();

/** Обработчик нажатия на кнопку "Обратиться за помощью" */
var askSupport = function() {
	$.get('/styles/skins/modern/design/js/common/html/supportRequestPopup.html', function(html) {
		openSupportRequestPopup(html);
		checkLicenseKey();
	});

	/**
	 * Открывает всплывающее окно "Запрос в Службу Заботы"
	 * @param {String} html HTML-код всплывающего окна
	 */
	var openSupportRequestPopup = function(html) {
		openDialog('', getLabel('js-ask-support'), {
			stdButtons: false,
			html: html,
			width: 700,
			openCallback: function() {
				$('#stop_btn').bind('click', function() {
					closeDialog();
				});
			},
			confirmCallback: function() {
			}
		});
	};

	/**
	 * Запускает проверку доменного ключа пользователя,
	 * и в случае успеха загружает поля для формы запроса в Службу Заботы.
	 */
	var checkLicenseKey = function() {
		$.ajax({
			type: 'POST',
			url: '/udata/system/checkLicenseKey/',
			dataType: 'xml',

			success: function(doc) {
				$('#form_body').html('');
				$('#loading').html('');
				var message = '';

				var errors = doc.getElementsByTagName('error');
				if (errors.length) {
					$('#ask_support_form').remove();
					message = errors[0].firstChild.nodeValue;
				}

				var notes = doc.getElementsByTagName('notes');

				if (notes.length) {
					message += notes[0].firstChild.nodeValue;
				}

				$('#license_message').html(message);

				var forms = doc.getElementsByTagName('form');
				if (!forms.length) {
					return;
				}

				var user = doc.getElementsByTagName('user');

				$('#form_body').html('<form id="support_request" action="" method="post" enctype="multipart/form-data">' + forms[0].firstChild.nodeValue + '</form>');

				$('input[name="data[fio_frm]"]').val(user[0].getAttribute('name'));
				$('#email_frm').val(user[0].getAttribute('email'));
				$('#server_credentials').val(localStorage.getItem('serverCredentials'));


				var parent = $('input[name="data[cms_domain]"]').parent();
				$('input[name="data[cms_domain]"]').remove();
				var select = document.createElement('select');
				select.name = 'data[cms_domain]';

				var domains = doc.getElementsByTagName('domains');

				for (var i = 0; i < domains[0].getElementsByTagName('domain').length; i++) {
					var domain = domains[0].getElementsByTagName('domain');
					domain = domain[i];
					var option = document.createElement('option');
					option.value = domain.getAttribute('host');

					if (domain.getAttribute('host') == user[0].getAttribute('domain')) {
						option.selected = true;
					}

					option.appendChild(document.createTextNode(domain.getAttribute('host')));
					select.appendChild(option);
				}

				parent.append(select);
				$('#attach_file').parent('div');
				$('.button_1').remove();
				$('#checkLicenseKey').attr('style', '');

				$('#show_info').attr('style', 'display:inline-block;');
				$('#show_info').click(function() {
					$('#info_support').slideToggle('slow');
				});

				$.centerPopupLayer();

				var $wrapper = $('#license_wrapper');
				var lastFormBlock = $('#support_request > div', $wrapper).addClass('col-md-6').last();
				lastFormBlock.removeClass('col-md-6').addClass('col-md-12');
				$('input[type=text]', $wrapper).addClass('default');
				$('.asterisk', $wrapper).parent(':contains(обязательные)').remove();
				$('select', $wrapper).selectize();
				$('#server_credentials').focusout(function(){
					localStorage.setItem('serverCredentials', $(this).val());
				});

				$('#checkLicenseKey').bind('click', sendSupportRequest);
			},

			error: function(jqXHR, textStatus, errorThrown) {
				if (window.session) {
					window.session.stopAutoActions();
				}
			}
		});
	};

	/** Отправляет запрос в Службу Заботы */
	var sendSupportRequest = function() {
		$.ajax({
			type: 'POST',
			url: '/udata/system/sendSupportRequest/',
			data: new FormData($('#support_request')[0]),

			cache: false,
			contentType: false,
			processData: false,

			success: function(data) {
				var message;
				$('#loading').html('');

				var error = data.getElementsByTagName('error');
				if (error.length) {
					message = '<span style="color:red;">' + error[0].firstChild.nodeValue + '</span>';
				}

				var success = data.getElementsByTagName('success');
				if (success.length) {
					message = success[0].firstChild.nodeValue;
					$('#ask_support_form').remove();
					$('#checkLicenseKey').remove();
				}

				$('#license_message').html(message);
				$.centerPopupLayer();
			}
		});
	};
};
