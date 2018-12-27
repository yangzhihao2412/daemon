<?php
namespace config;
return array(

		array(
				'name' => 'fuiouTask',
				'class' => '\service\fuiouTask',
				'username' => 'raise',		//运行时用户
				'runCountLimit' => 1,			//运行多少次后重启 必需为1，php版本低于5.6如果
				'runTimeInteval' => 200 //运行时间间隔 单位秒
		),
		array(
				'name' => 'mailTask',
				'class' => '\service\mailTask',
				'username' => 'raise',		//运行时用户
				'runCountLimit' => 1,			//运行多少次后重启 必需为1，php版本低于5.6如果
				'runTimeInteval' => 200 //运行时间间隔 单位秒
		),
		array(
				'name' => 'monitor',
				'class' => '\service\monitor',
				'username' => 'raise',		//运行时用户
				'runCountLimit' => 1,			//运行多少次后重启
				'runTimeInteval' => 10, //运行时间间隔 单位秒
		)
		
		
		
);