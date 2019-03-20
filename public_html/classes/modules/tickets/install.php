<?php
	$INFO = array();

	$INFO['name'] = "tickets";
	$INFO['filename'] = "modules/tickets/class.php";
	$INFO['config'] = "0";
	$INFO['ico'] = "ico_tickets";
	$INFO['default_method'] = "";
	$INFO['default_method_admin'] = "tickets";

	$SQL_INSTALL = array();

	$COMPONENTS = array();

	$COMPONENTS[0] = "./classes/modules/content/__admin.php";
	$COMPONENTS[2] = "./classes/modules/content/__custom.php";
	$COMPONENTS[4] = "./classes/modules/content/__events.php";
	$COMPONENTS[9] = "./classes/modules/content/class.php";
	$COMPONENTS[10] = "./classes/modules/content/events.php";
	$COMPONENTS[11] = "./classes/modules/content/i18n.en.php";
	$COMPONENTS[12] = "./classes/modules/content/i18n.php";
	$COMPONENTS[13] = "./classes/modules/content/lang.en.php";
	$COMPONENTS[14] = "./classes/modules/content/lang.php";
	$COMPONENTS[26] = "./classes/modules/content/permissions.php";
?>