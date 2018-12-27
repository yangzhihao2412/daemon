<?php
namespace service;
use lib\daemon;
//重启服务
class monitor extends daemon{
	

	
	public function main() {
		// TODO Auto-generated method stub
		global $serviceConf;
		foreach ($serviceConf as $key => $value) {
			if ($value['name'] != 'monitor') {
				if (!self::isRun($value['name'])) {
					try {
						$$value ['name'] = new $value ['class'] ();
						$$value ['name']->name = $value ['name'];
						$$value ['name']->username = $value ['username'];
						$$value ['name']->setStaticServiceName($value['name']);
						$$value['name']->runCountLimit = $value['runCountLimit'];
						$$value['name']->runTimeInteval = $value['runTimeInteval'];
						$$value ['name']->setUser();
						$$value ['name']->run ();
						self::console($value['name']."\t\t    [重启成功]");
					} catch (\Exception $e) {
						echo $e->getMessage();
						daemon::closeSelf();
					}
				}
			}
		}
		
		
		
		
	}
	
	public function __destruct(){
		//$this->dbCon = null;
	}

	
}
?>











