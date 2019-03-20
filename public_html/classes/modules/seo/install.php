<?php

$INFO = Array();

$INFO['name'] = "seo";
$INFO['title'] = "SEO";
$INFO['description'] = "SEO";
$INFO['filename'] = "modules/seo/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_seo";
$INFO['default_method'] = "show";
$INFO['default_method_admin'] = "seo";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/seo'] = "SEO-функции";


$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/seo/.htaccess";
$COMPONENTS[1] = "./classes/modules/seo/__admin.php";
$COMPONENTS[2] = "./classes/modules/seo/class.php";
$COMPONENTS[3] = "./classes/modules/seo/i18n.en.php";
$COMPONENTS[4] = "./classes/modules/seo/i18n.php";
$COMPONENTS[5] = "./classes/modules/seo/lang.php";
$COMPONENTS[6] = "./classes/modules/seo/permissions.php";

?>