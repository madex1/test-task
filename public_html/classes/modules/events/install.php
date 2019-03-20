<?php

$INFO = Array();

$INFO['name'] = "events";
$INFO['title'] = "Events";
$INFO['description'] = "Events";
$INFO['filename'] = "modules/events/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_events";
$INFO['default_method'] = "getUserSettings";
$INFO['default_method_admin'] = "last";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/events'] = "Events";


$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[1] = "./classes/modules/events/__admin.php";
$COMPONENTS[2] = "./classes/modules/events/class.php";
$COMPONENTS[3] = "./classes/modules/events/i18n.en.php";
$COMPONENTS[4] = "./classes/modules/events/i18n.php";
$COMPONENTS[5] = "./classes/modules/events/lang.php";

?>