<?php 
$input = file_get_contents('php://input');
//file_put_contents('test_merge.txt', $input);
//$input = file_get_contents('test_merge.txt');
$dataobj = json_decode($input);

if($dataobj->object_attributes->target_branch == 'master' && $dataobj->object_attributes->state == 'merged') {
	$log = shell_exec('git checkout -f master && git pull origin master');
	$log = "[".date('d.m.Y h:i:s')."] ".$log."\n";
	file_put_contents('deploy_log.txt', $log, FILE_APPEND);
}
