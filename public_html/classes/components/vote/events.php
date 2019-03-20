<?php

	/** Копирует варианты ответа при копировании страницы с опросом. */
	new umiEventListener('systemCloneElement', 'vote', 'onCloneElement');

