#!/usr/bin/env php
<?php 
use lib\daemon;

define("ROOTDIR",'/Users/yangzhihao/PhpstormProjects/services/');

if (!defined('ROOTDIR')) {




	echo "请先设置常量ROOTDIR";
	exit;
}





require_once ROOTDIR.'./core.php';

$serviceConf = require_once ROOTDIR.'./config/services.php';

if (isset($argv[1])) {
	if($argv[1] == 'start'){
		daemon::start();
	}
	if($argv[1] == 'stop'){
		daemon::stop('monitor');
		foreach ($serviceConf as $value) {
			if ($value['name'] == 'monitor') {
				continue;
			}
			daemon::stop($value['name']);
		}
	}
	if($argv[1] == 'help' || $argv[1] == '-h'){
		echo "Usage: zcjservice [options] [argv2]\n start\t\t\t\t\t\t run all services \n stop\t\t\t\t\t\t stop all service \n status\t\t\t\t\t\t show all service run status \n status [name]\t\t\t\t\t show the service run status\n备注:\n 使用stop命令会让各服务平滑关闭，即让其完整执行后关闭，可能需要最少200秒的时间\n";
		exit;
	}
	if($argv[1] == 'status'){
		
		if (!empty($argv[2])) {
			
			daemon::checkStatus($argv[2], false,$serviceConf);
				
			
		}else{
			foreach ($serviceConf as $value) {
				daemon::checkStatus($value['name'], true);
			}
			
		}
		
	}
}else{
	echo "Usage: zcjservice [options] [argv2]\n start\t\t\t\t\t\t run all services \n stop\t\t\t\t\t\t stop all service \n status\t\t\t\t\t\t show all service run status \n status [name]\t\t\t\t\t show the service run status\n备注:\n 使用stop命令会让各服务平滑关闭，即让其完整执行后关闭，可能需要最少200秒的时间\n";
	
}



?>
