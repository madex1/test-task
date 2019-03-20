<?php

	/** Класс макросов, то есть методов, доступных в шаблоне */
	class StatMacros {

		/** @var stat $module */
		public $module;

		/**
		 * Возвращает облако собранных тегов сайта.
		 * @param string $template имя шаблона (для tpl)
		 * @param int $limit ограничение на количество тегов
		 * @param int $max_font_size максимальный кегль шрифта для тега
		 * @return mixed
		 */
		public function tagsCloud($template = 'default', $limit = 50, $max_font_size = 16) {
			list($template_block, $template_line, $template_separator) = stat::loadTemplates(
				'stat/' . $template,
				'tags_block',
				'tags_block_line',
				'tags_separator'
			);

			$factory = new statisticFactory(dirname(__FILE__) . '/classes/reports');
			/** @var allTagsXml|allTags $report */
			$report = $factory->get('allTags');
			$report->setStart(0);
			$report->setFinish(strtotime('+1 day', time()));
			$result = $report->get();
			$max = $result['max'];
			$lines = [];

			$sz = umiCount($result['labels']);
			for ($i = 0; $i < min($sz, $limit); $i++) {
				$label = $result['labels'][$i];
				$line_arr = [];

				$tag = $label['tag'];
				$cnt = $label['cnt'];

				$fontSize = ceil($max_font_size * ($cnt / $max));
				$line_arr['node:tag'] = $tag;
				$line_arr['attribute:cnt'] = $cnt;
				$line_arr['attribute:font-size'] = $fontSize;
				$line_arr['void:separator'] = ($i < $sz - 1) ? $template_separator : '';
				$line_arr['void:font_size'] = $fontSize;
				$lines[] = stat::parseTemplate($template_line, $line_arr);
			}

			$block_arr = [];
			$block_arr['subnodes:lines'] = $lines;
			$block_arr['total'] = $sz;
			$block_arr['per_page'] = $limit;
			return stat::parseTemplate($template_block, $block_arr);
		}
	}

