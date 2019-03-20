var pathArray = location.pathname.replace(/^\//, '').replace(/\/$/, '').split('/');
var sliceArray = [], i;

for (i = 0; i < pathArray.length; i++) {
	sliceArray.push(pathArray[i]);
	if (pathArray[i] === "emarket") {
		break;
	}
}

var prefix = '/' + sliceArray.join('/');

window.onload = function() {
	if (window.opener) {
		window.opener.document.location.href = prefix + '/purchase/result/successful/';
	}
};
