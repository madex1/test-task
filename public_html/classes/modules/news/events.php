<?php
	$onCronNewsRead = new umiEventListener("cron", "news", "feedsImportListener");
	$onCronActivateNews = new umiEventListener("cron", "news", "cronActivateNews");
?>