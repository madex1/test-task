/** Функционал панели инструментов в форме редактирования сущности */
(function ($) {
	"use strict";

	/** Выполняется, когда все элементы DOM готовы */
	$(function () {
		buildSelects()
	});

	/** Формирует выпадающие списки */
	var buildSelects = function () {
		$('.form_modify').find('.select').each(function(){
			var current = $(this);
			buildSelect(current);
		});
	};

}(jQuery));