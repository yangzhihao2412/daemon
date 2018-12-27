<?php
namespace model;
use lib\Model;
class MailLog extends Model{
	
	private $tablenName = 'fanwe_mail_log';
	
	
	public function getQueue(){
		$this->R("select * FROM `".$this->tablenName."` WHERE `is_send` = 0 LIMIT 100");
		$data = $this->fetchAll();
		
		return $data;
		
	}
	
	//设置已发送
	public function setSend($id, $status){
		$sql = "UPDATE `".$this->tablenName."` SET `is_send` = ".$status.",`update_time` = ".getUTCtime()." WHERE `id` = ".$id;
		return $this->U($sql);
	}
	
	
	
	
}


?>