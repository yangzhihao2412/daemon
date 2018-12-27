<?php 

use lib\daemon;
ini_set("display_error", 1);
error_reporting(2047);
//捕获致命错误处理
function fatalErrorParse(){	
	

	$last_error =  error_get_last();
	
	// 判断错误类型进行处理
	if(isset($last_error['type']) && $last_error['type']==E_ERROR)
	{
		print_r(error_get_last());
		daemon::closeSelf();
	}

}


register_shutdown_function("fatalErrorParse");

date_default_timezone_set('PRC');
define('UTC_TIME', getUTCtime());
function getUTCtime(){
	$utcTime = time() - date('Z');
	return $utcTime;
}


function console($msg,$exit = null){
	
	if ($exit) {
		echo $msg."\n";
		exit;
	}else{
		echo $msg."\n";
	}
	
}


if (!defined('ROOTDIR')) {
	console("请先设置常量ROOTDIR",1);
}



function __autoload($className){
	$className = str_replace("\\", "/", $className);	
			
	$filePath = ROOTDIR.$className.'.php';
	if (file_exists($filePath)) {
		require_once $filePath;
	}else{
		echo $filePath;
		
	}
}


?>