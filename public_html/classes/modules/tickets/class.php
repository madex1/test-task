<?php
	/** Модуль управления заметками */
	class tickets extends def_module {

		/** Конструктор */
		public function __construct() {
			parent::__construct();

			$this->loadCommonExtension();

			if($this->cmsController->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__tickets");

				$this->loadAdminExtension();

				$this->__loadLib("__custom_adm.php");
				$this->__implement("__tickets_custom_admin");
			}

			$this->loadSiteExtension();

			$this->__loadLib("__events.php");
			$this->__implement("__tickets_events");

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_tickets");
		}

		/**
		 * Метод управления заметками на клиентской части cайта
		 * Позволяет создавать, удалять и изменять заметки
		 * @throws coreException
		 * @throws publicException
		 * @throws selectorException
		 */
		public function manage () {
			$mode = getRequest('param0');
			$id = getRequest('param1');

			$objects = umiObjectsCollection::getInstance();
			$buffer = \UmiCms\Service::Response()
				->getCurrentBuffer();
			$buffer->contentType('text/javascript');
			$buffer->clear();

			$json = new jsonTranslator;

			if ($mode == 'create') {
				$type = selector::get('object-type')->name('content', 'ticket');
				$id = $objects->addObject(null, $type->getId());
				if (!$id) {
					throw new publicException(__METHOD__ . ': cant create ticket');
				}
			}

			if ($id) {
				/** @var iUmiObject $ticket */
				$ticket = selector::get('object')->id($id);
				$this->validateEntityByTypes($ticket, array('module' => 'content', 'method' => 'ticket'));
			} else {
				throw new publicException('Wrong params');
			}

			$permissions = permissionsCollection::getInstance();

			try {
				if ($mode !== 'create' && !$permissions->isSv() && !$this->canUserEditTicket($id)) {
					$buffer->push($this->getErrorJSON(getLabel('wrong-permissions-json')));
					$buffer->end();
				}
			} catch (Exception $e) {
				$buffer->push($this->getErrorJSON(getLabel('ticket-not-found-json')));
				$buffer->end();
			}


			if ($mode == 'delete') {
				$deleteEventPoint = new umiEventPoint('deleteTicket');
				$deleteEventPoint->setParam('id', $id);
				$deleteEventPoint->setParam('message', (string) $ticket->getValue('message'));
				$deleteEventPoint->setParam('url', (string) $ticket->getValue('url'));
				$objects->delObject($id);
				$deleteEventPoint->setMode('after');
				$this->setEventPoint($deleteEventPoint);
				$buffer->end();
			}

			if ($mode == 'create') {
				$ticket->setValue('create_time', time());
			}

			$ticket->setValue('x', (int) getRequest('x'));
			$ticket->setValue('y', (int) getRequest('y'));
			$ticket->setValue('width', (int) getRequest('width'));
			$ticket->setValue('height', (int) getRequest('height'));
			$ticket->setValue('message', getRequest('message'));
			$ticket->setName(getRequest('message'));

			$url = getRequest('referer') ? getRequest('referer') : getServer('HTTP_REFERER');
			$url = str_replace("%", "&#37", $url);

			if ($url) {
				$ticket->setValue('url', $url);
			}

			if ($mode == 'create') {
				$permissions = permissionsCollection::getInstance();
				$ticket->setValue('user_id', $permissions->getUserId());
			}

			$ticket->commit();

			if ($mode == 'create') {
				$createEventPoint = new umiEventPoint('createTicket');
				$createEventPoint->setMode('after');
				$createEventPoint->setParam('id', $id);
				$this->setEventPoint($createEventPoint);
			}

			$data = array(
				'id' => $ticket->id
			);

			$result = $json->translateToJson($data);
			$buffer->push($result);
			$buffer->end();
		}

		/**
		 * Возвращает список именованных цветов
		 * @return array('имя цвета' => 'код цвета')
		 */
		public function getNamedColors() {
			return array(
				'aliceblue'				=>	'#f0f8ff',
				'antiquewhite'			=>	'#faebd7',
				'aqua'					=>	'#00ffff',
				'aquamarine'			=>	'#7fffd4',
				'azure'					=>	'#f0ffff',
				'beige'					=>	'#f5f5dc',
				'bisque'				=>	'#ffe4c4',
				'black'					=>	'#000000',
				'blanchedalmond'		=>	'#ffebcd',
				'blue'					=>	'#0000ff',
				'blueviolet'			=>	'#8a2be2',
				'brown'					=>	'#a52a2a',
				'burlywood'				=>	'#deb887',
				'cadetblue'				=>	'#5f9ea0',
				'chartreuse'			=>	'#7fff00',
				'chocolate'				=>	'#d2691e',
				'coral'					=>	'#ff7f50',
				'cornflowerblue'		=>	'#6495ed',
				'cornsilk'				=>	'#fff8dc',
				'crimson'				=>	'#dc143c',
				'cyan'					=>	'#00ffff',
				'darkblue'				=>	'#00008b',
				'darkcyan'				=>	'#008b8b',
				'darkgoldenrod'			=>	'#b8860b',
				'darkgray'				=>	'#a9a9a9',
				'darkgreen'				=>	'#006400',
				'darkkhaki'				=>	'#bdb76b',
				'darkmagenta'			=>	'#8b008b',
				'darkolivegreen'		=>	'#556b2f',
				'darkorange'			=>	'#ff8c00',
				'darkorchid'			=>	'#9932cc',
				'darkred'				=>	'#8b0000',
				'darksalmon'			=>	'#e9967a',
				'darkseagreen'			=>	'#8fbc8f',
				'darkslateblue'			=>	'#483d8b',
				'darkslategray'			=>	'#2f4f4f',
				'darkturquoise'			=>	'#00ced1',
				'darkviolet'			=>	'#9400d3',
				'deeppink'				=>	'#ff1493',
				'deepskyblue'			=>	'#00bfff',
				'dimgray'				=>	'#696969',
				'dodgerblue'			=>	'#1e90ff',
				'firebrick'				=>	'#b22222',
				'floralwhite'			=>	'#fffaf0',
				'forestgreen'			=> 	'#228b22',
				'fuchsia'				=>	'#ff00ff',
				'gainsboro'				=>	'#dcdcdc',
				'ghostwhite'			=>	'#f8f8ff',
				'gold'					=>	'#ffd700',
				'goldenrod'				=>	'#daa520',
				'gray'					=>	'#808080',
				'green'					=>	'#008000',
				'greenyellow'			=>	'#adff2f',
				'honeydew'				=>	'#f0fff0',
				'hotpink'				=>	'#ff69b4',
				'indianred'				=>	'#cd5c5c',
				'indigo'				=>	'#4b0082',
				'ivory'					=>	'#fffff0',
				'khaki'					=>	'#f0e68c',
				'lavender'				=>	'#e6e6fa',
				'lavenderblush'			=>	'#fff0f5',
				'lawngreen'				=>	'#7cfc00',
				'lemonchiffon'			=>	'#fffacd',
				'lightblue'				=>	'#add8e6',
				'lightcoral'			=>	'#f08080',
				'lightcyan'				=>	'#e0ffff',
				'lightgoldenrodyellow'	=>	'#fafad2',
				'lightgrey'				=>	'#d3d3d3',
				'lightgreen'			=>	'#90ee90',
				'lightpink'				=>	'#ffb6c1',
				'lightsalmon'			=>	'#ffa07a',
				'lightseagreen'			=>	'#20b2aa',
				'lightskyblue'			=> 	'#87cefa',
				'lightslategray'		=>	'#778899',
				'lightsteelblue'		=>	'#b0c4de',
				'lightyellow'			=>	'#ffffe0',
				'lime'					=>	'#00ff00',
				'limegreen'				=>	'#32cd32',
				'linen'					=>	'#faf0e6',
				'magenta'				=>	'#ff00ff',
				'maroon'				=>	'#800000',
				'mediumaquamarine'		=>	'#66cdaa',
				'mediumblue'			=>	'#0000cd',
				'mediumorchid'			=>	'#ba55d3',
				'mediumpurple'			=>	'#9370d8',
				'mediumseagreen'		=>	'#3cb371',
				'mediumslateblue'		=>	'#7b68ee',
				'mediumspringgreen'		=>	'#00fa9a',
				'mediumturquoise'		=>	'#48d1cc',
				'mediumvioletred'		=>	'#c71585',
				'midnightblue'			=>	'#191970',
				'mintcream'				=>	'#f5fffa',
				'mistyrose'				=>	'#ffe4e1',
				'moccasin'				=>	'#ffe4b5',
				'navajowhite'			=>	'#ffdead',
				'navy'					=>	'#000080',
				'oldlace'				=>	'#fdf5e6',
				'olive'					=>	'#808000',
				'olivedrab'				=>	'#6b8e23',
				'orange'				=>	'#ffa500',
				'orangered'				=>	'#ff4500',
				'orchid'				=>	'#da70d6',
				'palegoldenrod'			=>	'#eee8aa',
				'palegreen'				=>	'#98fb98',
				'paleturquoise'			=>	'#afeeee',
				'palevioletred'			=>	'#d87093',
				'papayawhip'			=>	'#ffefd5',
				'peachpuff'				=>	'#ffdab9',
				'peru'					=>	'#cd853f',
				'pink'					=>	'#ffc0cb',
				'plum'					=>	'#dda0dd',
				'powderblue'			=>	'#b0e0e6',
				'purple'				=>	'#800080',
				'red'					=>	'#ff0000',
				'rosybrown'				=>	'#bc8f8f',
				'royalblue'				=>	'#4169e1',
				'saddlebrown'			=>	'#8b4513',
				'salmon'				=>	'#fa8072',
				'sandybrown'			=>	'#f4a460',
				'seagreen'				=>	'#2e8b57',
				'seashell'				=>	'#fff5ee',
				'sienna'				=>	'#a0522d',
				'silver'				=>	'#c0c0c0',
				'skyblue'				=>	'#87ceeb',
				'slateblue'				=>	'#6a5acd',
				'slategray'				=>	'#708090',
				'snow'					=>	'#fffafa',
				'springgreen'			=>	'#00ff7f',
				'steelblue'				=>	'#4682b4',
				'tan'					=>	'#d2b48c',
				'teal'					=>	'#008080',
				'thistle'				=>	'#d8bfd8',
				'tomato'				=>	'#ff6347',
				'turquoise'				=>	'#40e0d0',
				'violet'				=>	'#ee82ee',
				'wheat'					=>	'#f5deb3',
				'white'					=>	'#ffffff',
				'whitesmoke'			=> 	'#f5f5f5',
				'yellow'				=>	'#ffff00',
				'yellowgreen'			=>	'#9acd32'
			);
		}

		/**
		 * Возвращает случайное название цвета
		 * @return string
		 */
		public function getRandomColorName() {
			$namedColors = $this->getNamedColors();
			return array_rand($namedColors);
		}

		/**
		 * Возвращает данные об ошибке в формате JSON
		 * @param string $message сообщение об ошибке
		 * @return string данные в формате JSON
		 */
		private function getErrorJSON($message) {
			$data = array('error' => $message);
			$json = new jsonTranslator;
			return $json->translateToJson($data);
		}

		/**
		 * Проверяет имеет ли текущий пользователь права на редактирование и удаление заметки
		 * @param int $ticketId ID заметки
		 * @throws publicException если объект заметки не найден
		 * @return bool
		 */
		private function canUserEditTicket($ticketId) {
			$currentUserId = permissionsCollection::getInstance()->getUserId();
			$ticket = umiObjectsCollection::getInstance()->getObject($ticketId);

			if (!$ticket instanceof iUmiObject) {
				throw new publicException('Ticket #' . $ticketId . ' does not exist');
			}

			$ownerId = $ticket->getOwnerId();
			return ($ownerId === $currentUserId);
		}
	};
?>
