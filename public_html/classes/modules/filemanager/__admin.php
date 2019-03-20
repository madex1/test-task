<?php
	abstract class __filemanager extends baseModuleAdmin {

		public function directory_list() {
			throw new publicAdminException(getLabel('error-fs-not-allowed'));
		}

		public function getCurrentPath() {
			throw new publicAdminException(getLabel('error-fs-not-allowed'));
		}

		public function upload_files() {
			throw new publicAdminException(getLabel('error-fs-not-allowed'));
		}

		public function make_directory() {
			throw new publicAdminException(getLabel('error-fs-not-allowed'));
		}

		public function del() {
			throw new publicAdminException(getLabel('error-fs-not-allowed'));
		}
		
		public function rename() {
			throw new publicAdminException(getLabel('error-fs-not-allowed'));
		}

		public function getDatasetConfiguration($param = '') {
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'filemanager', '#__name'=>'shared_files'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'filemanager', '#__name'=>'del_shared_file', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'filemanager', '#__name'=>'shared_file_activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'move'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang')),
					'types' => array(
						array('common' => 'true', 'id' => 'shared_file')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'anons', 'content', 'rate_voters', 'rate_sum'),
					'default' => 'name[400px]|downloads_counter[250px]'
				);
		}

		public function checkIsAllowedPath() {
			throw new publicAdminException(getLabel('error-fs-not-allowed'));
		}
	};
?>