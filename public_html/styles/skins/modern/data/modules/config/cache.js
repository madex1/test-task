function setConfigCookie() {
	var date = new Date();
	date.setTime(date.getTime() + (60 * 60 * 1000));
	jQuery.cookie('umi_config_cookie', 'Y', {expires: date});
}

jQuery(document).ready(function() {
	var confirmId = Math.round(Math.random() * 100000);
	var skin =
		'<div class="eip_win_head popupHeader" onmousedown="jQuery(\'.eip_win\').draggable(c)">\n\
			<div class="eip_win_close popupClose" onclick="javascript:jQuery.closePopupLayer(\'macConfirm' + confirmId + '\'); setConfigCookie(); return false;">&#160;</div>\n\
								<div class="eip_win_title">' + getLabel('js-index-speedmark-message') + '</div>\n\
							</div>\n\
							<div class="eip_win_body popupBody" onmousedown="jQuery(\'.eip_win\').draggable().draggable(\'destroy\')">\n\
								<div class="popupText" style="zoom:1;">' + getLabel('js-index-speedmark-popup') + '</div>\n\
								<div class="eip_buttons">\n\
									<input type="button" class="back" value="Закрыть" onclick="confirmButtonCancelClick(\'macConfirm' + confirmId + '\', ' + confirmId + '); setConfigCookie(); return false;" />\n\
									<div style="clear: both;"/>\
								</div>\n\
							</div>';
	var param = {
		name: 'macConfirm' + confirmId,
		width: 300,
		data: skin,
		closeable: true
	};

	if (!jQuery.cookie('umi_config_cookie')) {
		jQuery.openPopupLayer(param);
	}
});

function SpeedMark(c) {
	this.iterations = c || 20;
	this.currentIteration = 0;

	this.error = false;

	this.blank_url = '/admin/config/speedtest/';

	this.started = null;

	var self = this;
	$(document).ajaxError(function(event, request, settings) {
		if (settings.url.indexOf(self.blank_url) == 0) {
			self.error = true;

			self.end();
		}
	});
}

SpeedMark.prototype.start = function() {
	var self = this;

	if (this.started) {
		return false;
	}

	jQuery('.speedmark').show();
	jQuery('#speedmark_avg').html(getLabel('js-index-speedmark-wait'));

	this.time = 0;
	this.error = false;
	this.finished = 0;
	this.started = true;
	this.authorized = true;

	if (!self.makeRequest()) {
		return false;
	}

	return false;
};

SpeedMark.prototype.makeRequest = function() {
	var self = this;

	jQuery.ajax({
		url: self.blank_url + '?random=' + Math.random(),
		dataType: 'text',
		success: function(data) {
			var time = parseFloat(data);
			if (!time) {
				self.authorized = false;
			}

			self.time += time;
			self.currentIteration++;

			if (self.currentIteration <= self.iterations) {
				self.makeRequest();
			} else {
				self.end();
			}
		}
	});

	if (!this.authorized) {
		location.reload();
		return false;
	}

	return true;
};

SpeedMark.prototype.end = function() {
	this.started = false;
	this.currentIteration = 0;

	this.time = parseFloat(this.time);

	var avg_time = this.time / this.iterations;

	var mark = Math.round(1 / avg_time * 100 / 45);
	var rate;
	if (mark < 10) {
		rate = getLabel('js-index-speedmark-less-10');
	} else if (mark < 20) {
		rate = getLabel('js-index-speedmark-less-20');
	} else if (mark < 30) {
		rate = getLabel('js-index-speedmark-less-30');
	} else if (mark < 40) {
		rate = getLabel('js-index-speedmark-less-40');
	} else if (mark >= 40) {
		rate = getLabel('js-index-speedmark-more-40');
	}

	var result = '<b>' + mark + '</b> - ' + rate;
	jQuery('#speedmark_avg').removeClass('error');

	if (!this.error) {
		jQuery('#speedmark_avg').html(result);
	}
	else {
		jQuery('#speedmark_avg').addClass('error').html(getLabel('js-index-speedmark-error'));
	}
};

var speedmark = new SpeedMark();
