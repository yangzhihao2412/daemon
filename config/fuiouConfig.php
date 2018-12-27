<?php
return array (
		
		'is_debug' => true,
		'local_callback_password' => 'zcjddd',
		'local_callback_url' =>'http://www.zicj.com',//尾部不加/的url
		'debug' => array (
				'merNo' => '0002900F0041271',
				'APIuri' => 'https://jzh-test.fuiou.com/jzh/',
				'pbKeyFilePath' => ROOTDIR.'config/fuioukey/testpbkey.pem',
				'prKeyFilePath' => ROOTDIR.'config/fuioukey/testprkey.pem' 
		),
		'normal' => array (
				'merNo' => '',
				'APIuri' => 'http://www-1.fuiou.com:9057/',
				'pbKeyFilePath' => '',
				'prKeyFilePath' => '' 
		) 
)
?>

