<?php

	/** Обработчики изменения страниц для актуализации состава меню */
	new umiEventListener('systemModifyElement', 'menu', 'onMenuEditLink');
	new umiEventListener('systemMoveElement', 'menu', 'onMenuEditLink');
	new umiEventListener('systemDeleteElement', 'menu', 'onMenuEditLink');
	new umiEventListener('systemSwitchElementActivity', 'menu', 'onMenuEditLink');
