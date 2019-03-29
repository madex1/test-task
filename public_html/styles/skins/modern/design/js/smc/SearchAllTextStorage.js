/**
 * Класс хранилища значения для полнотекстового поиска в табличном контроле
 * @param {String} controlId идентификатор табличного контрола
 */
function SearchAllTextStorage(controlId) {

	/** @var {String} ключ для хранения значения полнотекстового поиска */
	this.index = 'search-string';

	/** @var {String} тэг для хранения значения полнотекстового поиска */
	this.filterTag = controlId;

	/**
	 * Сохраняет значения полнотекстового поиска
	 * @param {Array} valueList список значений
	 */
	this.save = function(valueList) {
		var settingStore =  SettingsStore.getInstance();
		var packedList = settingStore.packValueList(valueList);
		settingStore.set(this.index, packedList, this.filterTag);
	};

	/**
	 * Загружает значения полнотекстового поиска
	 * @returns {Array}
	 */
	this.load = function() {
		var settingStore =  SettingsStore.getInstance();
		var searchString = settingStore.get(this.index, this.filterTag);

		if (searchString === false) {
			return [];
		}

		return settingStore.unpackValueList(searchString);
	};
}