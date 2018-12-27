<?php

namespace model;

use lib\Model;

class fuiouTaskStatusModel extends Model {
	private $tablenName = 'fanwe_fuiou_deal_task_status';
	
	
	
	public function getStatusByDeal_id($deal_id){
		
		$sql = "SELECT `status` FROM `".$this->tablenName."` WHERE `deal_id` = ".$deal_id;
		
		$this->R($sql);
		
		
		return $this->fetchAll();
		
		
		
	}
	
	
	
	public function setStatus($deal_id, $status){
		
		$sql = "UPDATE `".$this->tablenName."` SET `status` = ".$status."  WHERE `deal_id` = ".$deal_id;
		
		return $this->U($sql);
		
		
	}
	
	
	
}

?>