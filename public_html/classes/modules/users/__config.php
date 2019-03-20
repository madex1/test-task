<?php
	/** Класс реализует работу с настройками модуля "Пользователи" */
	abstract class __config_users extends baseModuleAdmin {

		/**
		 * Возвращает настройки модуля "Пользователи".
		 * Если передано ключевое слово "do" в $_REQUEST['param0'],
		 * то сохраняет переданные настройки.
		 * @return void
		 */
		public function config() {
			$umiRegistry = regedit::getInstance();
			$objectTypesColl = umiObjectTypesCollection::getInstance();

			$params = [
				'config' => [
					'guide:def_group' => [
						'type-id' => $objectTypesColl->getTypeIdByGUID('users-users'),
						'value' => null
					],
					'boolean:without_act' => null,
					'boolean:check_csrf_on_user_update' => null,
					'boolean:pages_permissions_changing_enabled_on_add' => null,
					'boolean:pages_permissions_changing_enabled_on_edit' => null
				]
			];

			$mode = getRequest('param0');

			if ($mode == 'do') {
				$params = $this->expectParams($params);
				$umiRegistry->setVar('//modules/users/def_group', $params['config']['guide:def_group']);
				$umiRegistry->setVar('//modules/users/without_act', $params['config']['boolean:without_act']);
				$umiRegistry->setVar(
					'//modules/users/check_csrf_on_user_update',
					$params['config']['boolean:check_csrf_on_user_update']
				);
				$umiRegistry->setVar(
					'//modules/users/pages_permissions_changing_enabled_on_add',
					$params['config']['boolean:pages_permissions_changing_enabled_on_add']
				);
				$umiRegistry->setVar(
					'//modules/users/pages_permissions_changing_enabled_on_edit',
					$params['config']['boolean:pages_permissions_changing_enabled_on_edit']
				);
				$this->chooseRedirect();
			}

			$params['config']['guide:def_group']['value'] = $umiRegistry->getVal('//modules/users/def_group');
			$params['config']['boolean:without_act'] = $umiRegistry->getVal('//modules/users/without_act');
			$params['config']['boolean:check_csrf_on_user_update'] =
				$umiRegistry->getVal('//modules/users/check_csrf_on_user_update');
			$params['config']['boolean:pages_permissions_changing_enabled_on_add'] =
				$umiRegistry->getVal('//modules/users/pages_permissions_changing_enabled_on_add');
			$params['config']['boolean:pages_permissions_changing_enabled_on_edit'] =
				$umiRegistry->getVal('//modules/users/pages_permissions_changing_enabled_on_edit');

			$data = $this->prepareData($params, 'settings');
			$this->setDataType('settings');
			$this->setActionType('modify');
			$this->setData($data);
			$this->doData();
		}
	};
?>
