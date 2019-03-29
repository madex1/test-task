function SecurityTest() {
	var self = this;
	var curInst = 0;

	this.start = function () {
		this.prepare();
		curInst++;
		this.runTest(jQuery("#testsTable .test").first(), curInst, true);
	};

	this.startOne = function(test) {
		jQuery('td', test).removeClass('error');
		this.runTest(jQuery(test), curInst, false);
	};

	this.prepare = function () {
		jQuery("#testsTable .test .test-value").empty();
	};

	this.runTest = function (test, inst, isNextNeeded) {
		if ( !jQuery(test).data('id') ) return;
		jQuery.ajax({
			url: "/admin/config/securityRunTest/" + jQuery(test).data('id') + '/?random=' + Math.random(),
			async: true,
			dataType: 'json',
			timeout: 5000,

			beforeSend: function() {
				jQuery(".test-value", test).html('<img src="/styles/skins/modern/design/img/loading.gif" />');
			},

			success: function(data) {
				if (curInst != inst) return;
				if (data.result) {
					jQuery(".test-value", test).text(getLabel('js-index-security-fine'));
					jQuery(".test-value", test).addClass("ok");
				} else {
					jQuery(".test-value", test).html('<span class="runOneSecurityTest">' + getLabel('js-index-security-problem') + '</span>');
					jQuery(".test-value", test).addClass("error");
				}

				if(test.next() && isNextNeeded) {
					self.runTest(test.next(), inst, true);
				}
			},

			error: function() {
				if (curInst != inst) return;

				jQuery(".test-value", test).html('<span class="runOneSecurityTest">' + getLabel('js-index-security-problem') + '</span>');
				jQuery(".test-value", test).addClass("error");

				if(test.next() && isNextNeeded) {
					self.runTest(test.next(), inst, true);
				}
			}
		});
	}
}

jQuery(function() {
	var security = new SecurityTest();

	jQuery('#startSecurityTests').click(function() {
		security.start();
	});

	jQuery(document).on('click', '.runOneSecurityTest', function() {
		var elem = jQuery(this).parent().parent();
		security.startOne(elem);
	});
});
