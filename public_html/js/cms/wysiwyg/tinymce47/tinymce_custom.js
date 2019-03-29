/**
 * Глобальные кастомные настройки tinyMCE 4.7.
 * @link https://www.tinymce.com/docs/configure/
 *
 * Эти настройки имеют больший приоритет, чем настройки по умолчанию
 * (@see WYSIWYG.prototype.tinymce47.settings), и будут применяться
 * при каждой инициализации визуального редактора (@see WYSIWYG.prototype.tinymce47.init).
 *
 * Чтобы кастомизировать каждую инициализацию отдельно, нужно использовать
 * локальные кастомные настройки. Для этого нужно кастомизировать шаблон,
 * в котором происходит инициализация визуального редактора.
 *
 * Алгоритм применения настроек при каждой инициализации:
 *   * Сначала применяются настройки по умолчанию
 *   * Потом применяются глобальные кастомные настройки
 *   * Потом применяются локальные кастомные настройки
 *
 * При совпадении ключей настроек, приоритет имеет более позднее значение.
 * Сравнение значений не глубокое, т.е. при совпадении ключей
 * будет перезаписано все значение, а не его часть.
 *
 * Пример локальных кастомных настроек из файла /styles/skins/_eip/js/popup.js :
 *
 * // Кастомизирует ширину и высоту html-редактора
 * // Остальные настройки такие же, как дефолтные,
 * // @see WYSIWYG.prototype.tinymce47.settings
 * uAdmin('settings', {
 * 	codemirror: {
 * 		indentOnInit: true,
 * 		path: 'codemirror',
 * 		width: 700,
 * 		height: 400,
 * 		config: {
 * 			lineNumbers: true,
 * 			lineWrapping: true,
 * 			autofocus: true,
 * 		}
 * 	}
 * },'wysiwyg');
 *
 * uAdmin('type', 'tinymce47', 'wysiwyg');
 *
 * @type {Object}
 */
window.mceCustomSettings = {

	// Файл с кастомным CSS
	// @link https://www.tinymce.com/docs/configure/content-appearance/#content_css
	content_css : '/js/cms/wysiwyg/tinymce47/tinymce_custom.css'
};
