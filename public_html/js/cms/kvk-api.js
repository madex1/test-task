function kvkAPI(id) {
	this.id = id;

	this.loadingMessage = function(lifetime) {
		var jGrowlProps = {
			'header': 'UMI.CMS - ' + getLabel('js-label-paymenttype-kvk')
		};

		if(!lifetime) {
			jGrowlProps.dont_close = 1;
		} else {
			jGrowlProps.life = lifetime * 1000;
		}

		jQuery.jGrowl(getLabel('js-label-kvk-in-progress'), jGrowlProps);
	}

	/** Обновить данные используя запрос API */
	this.refresh = function() {
		this.loadingMessage();
		window.location.assign("/admin/emarket/order_payment/"+this.id+"/refresh");
	}

	/** Скачать оферту */
	this.contract = function() {
		this.loadingMessage(5);
		window.location.assign("/admin/emarket/order_payment/"+this.id+"/getContract");
	}

	this.takeover = function() {
		this.loadingMessage(5);
		window.location.assign("/admin/emarket/order_payment/"+this.id+"/takeoverDocuments");
	}

	this.goods_form = function() {
		var self = this;
		openDialog({
			title: getLabel('js-label-goods_form-title'),
			text: getLabel('js-label-goods_form') + "<p></p>" +
				"<form id='goodsFormConfirm' method='get' action='/admin/emarket/order_payment/"+self.id+"/goodsForm/'>" +
				getLabel('js-label-goods_form-field-amount') + ": <input name='amount' type='text' value='0'/><br/>" +
				getLabel('js-label-goods_form-field-cashReturned') + ": <input name='cashReturned' type='text' value='0' disabled='disabled' />" +
				"</form>",
			OKText: getLabel('js-label-goods_form-OK'),
			OKCallback: function() {
				self.loadingMessage(5);
				jQuery("#goodsFormConfirm").submit();
			}
		});
	}

	/** Завершить */
	this.complete = function() {
		var self = this;
		openDialog({
			title: getLabel('js-label-complete_form-title'),
			text:  getLabel('js-label-complete_form'),
			OKText: getLabel('js-label-complete_form-OK'),
			cancelText: getLabel('js-label-complete_form-cancel'),
			OKCallback: function() {
				self.loadingMessage();
				window.location.assign("/admin/emarket/order_payment/"+self.id+"/complete");
			}
		});
	}

	/** Подтвердить */
	this.confirm = function() {
		var self = this;
		openDialog({
			title: getLabel('js-label-confirm_form-title'),
			text: getLabel('js-label-confirm_form') + "<p></p>" +
				"<form action='/admin/emarket/order_payment/"+self.id+"/confirm/' method='post' id='payConfirm'>" +
				"<input type='radio' name='type' value='bank' checked='checked' />" + getLabel('js-label-confirm_form-bank') + "<br/>" +
				"<input type='radio' name='type' value='partner' />" + getLabel('js-label-confirm_form-partner') +
				"</form>",
			OKText: getLabel('js-label-confirm_form-OK'),
			OKCallback: function() {
				self.loadingMessage();
				jQuery("#payConfirm").submit();
			}
		});
	}

	/** Отменить */
	this.cancel = function() {
		var self = this;
		openDialog({
			title: getLabel('js-label-cancel_form-title'),
			text: getLabel('js-label-cancel_form') + "<p></p>" +
				"<form action='/admin/emarket/order_payment/"+self.id+"/cancel/' id='payCancel'>" +
				"<input type='radio' name='reason' value='" + getLabel('js-label-cancel_form-client') + "' checked='checked' /> " + getLabel('js-label-cancel_form-client') + "<br/>" +
				"<input type='radio' name='reason' value='" + getLabel('js-label-cancel_form-partner') + "' /> " + getLabel('js-label-cancel_form-partner') + "<br/>" +
				"<input type='radio' name='reason' value='" + getLabel('js-label-cancel_form-good-missed') + "' /> " + getLabel('js-label-cancel_form-good-missed') + "<br/>" +
				"<input type='radio' name='reason' value='" + getLabel('js-label-cancel_form-wrong') + "' /> " + getLabel('js-label-cancel_form-wrong') + "<br/>" +
				"<input type='radio' name='reason' value='" + getLabel('js-label-cancel_form-no-contact') + "' /> " + getLabel('js-label-cancel_form-no-contact') + "<br/>" +
				"<input type='radio' name='reason' value='" + getLabel('js-label-cancel_form-fail-offer') + "' /> " + getLabel('js-label-cancel_form-fail-offer') +
				"</form>",
			OKCallback: function() {
				self.loadingMessage();
				jQuery("#payCancel").submit();
			}
		});
	}

	/**
	 * Загрузить доп. поля
	 *
	 * @param el loading-place
	 */
	this.loadMoreFields = function (el) {
		var container = jQuery(el).parent();
		jQuery(container).html('<img src="/images/cms/admin/mac/table/loading.gif" />');
		jQuery.get("/admin/emarket/order_payment/" + id + "/moreInfo.xml", null, function(response){
			container.remove();
			if(jQuery("status", response).text() == "OK") {
				jQuery("field", response).each(function(i, field){
					jQuery("#kvkInfo").append(
						jQuery('<tr><td class="eq-col">' + jQuery(field).attr("title") + '</td><td>' + jQuery(field).text() + '</td></tr>')
					);
				});
			}
		}, "xml");
	}
}