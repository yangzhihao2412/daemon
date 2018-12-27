<?php

namespace model;

use lib\Model;

class fuiouTaskModel extends Model {
	private $tablenName = 'fanwe_fuiou_task';
	
	/**
	 * 获取用户手机号，即富友账户
	 *
	 * @param int|array $userId        	
	 * @return array['user_id`=>`user_mobile`]
	 */
	public function getMobileByUserId($userId) {


		if (is_array ( $userId )) {
				
			$sql = "SELECT `user_id`,`ips_acct_no` FROM `fanwe_user_ips_relation` WHERE `ips_type` = 2 AND `user_id` IN(" . implode(',',$userId ) . ')';
		} else {
				
			$sql = "SELECT `ips_acct_no` FROM `fanwe_user_ips_relation` WHERE `ips_type` = 2 AND `user_id` = " . $userId;
		}

		
		$this->R ( $sql );
		
		
		
		$result = array ();
		
		
		if (is_array ( $userId )) {
			$data = $this->fetchAll ();
			foreach ( $data as $key => $value ) {
				$result [$value ['user_id']] = $value ['ips_acct_no'];
			}
		} else {
			
			$result = $this->fetchColumn ();
		}
		
		
		
		
		
		
		
		return $result;
	}
	
	/**
	 * 获取当前可处理单deal未处理满标数据
	 *
	 * @return boolean|int  deal_id
	 */
	public function getFullDealId() {
		$sqlForGetDealId = "SELECT `deal_id` FROM `fanwe_fuiou_deal_task_status` WHERE `status` = 1 LIMIT 1"; // 满标处理中
	
		$this->R ( $sqlForGetDealId );
		$deal_id = $this->fetchColumn ();
	
		return $deal_id;
	}
	
	
	/**
	 * 获取当前可处理单deal未处理满标数据
	 * @param int $deal_id deal_id
	 * @return boolean
	 */
	public function getFullDealTask($deal_id) {

		if ($deal_id) {
			$this->R ( "SELECT *  FROM `" . $this->tablenName . "` WHERE `response_code` <> '0000' AND `deal_id` = " . $deal_id . " AND `type` IN(1,2)" );
		} else {
			return FALSE;
		}
		
	
		// 返回数组中解冻和未解冻的数据。
		return $this->fetchAll ();
	}
	
	
	
	
	
	/**
	 * 获取 还款 冻结到冻结数据
	 *
	 * @return boolean
	 */
	public function getFreezeToFreezeForRepaymentDealTask($deal_id) {

		if ($deal_id) {
			$this->R ( "SELECT *  FROM `" . $this->tablenName . "` WHERE `response_code` <> '0000' AND `deal_id` = " . $deal_id . " AND `type` = 4" );
		} else {
			return FALSE;
		}
		
		// 返回数组中解冻和未解冻的数据。
		return $this->fetchAll ();
	}
	
	/**
	 * 获取还款 解冻数据
	 *
	 * @return boolean
	 */
	public function getUnFreezeForRepaymentDealTask($deal_id) {
	
		if ($deal_id) {
			$this->R ( "SELECT *  FROM `" . $this->tablenName . "` WHERE `response_code` <> '0000' AND `deal_id` = " . $deal_id . " AND `type` = 5" );
		} else {
			return FALSE;
		}
		// 返回数组中解冻和未解冻的数据。
		return $this->fetchAll ();
	}
	/**
	 * 检查还款时的 冻结到冻结是否全部完成
	 */
	
	public function isAllSuccessFreezeToFreezeForRepayment($deal_id){
	
		$checkSql = "SELECT COUNT(*) FROM `".$this->tablenName."` WHERE `response_code` <> '0000' AND `deal_id` = ".$deal_id." AND `type` = 4 ";
	
		$this->R($checkSql);
	
		$count = $this->fetchColumn();
	
		if ($count > 0) {
			return FALSE;
		}else{
			return TRUE;
		}
	
	}
	
	/**
	 * 检查还款时的 解冻是否全部完成
	 */
	
	public function isAllSuccessUnFreezeForRepayment($deal_id){
	
		$checkSql = "SELECT COUNT(*) FROM `".$this->tablenName."` WHERE `response_code` <> '0000' AND `deal_id` = ".$deal_id." AND `type` = 5 ";
	
		$this->R($checkSql);
	
		$count = $this->fetchColumn();
	
		if ($count > 0) {
			return FALSE;
		}else{
			return TRUE;
		}
	
	}
	
	
	/**
	 * 检查流标时 解冻投资人账户是否全部完成
	 */
	
	public function isAllunFreezeForRevokeTask($deal_id){
	
		$checkSql = "SELECT COUNT(*) FROM `".$this->tablenName."` WHERE `response_code` <> '0000' AND `deal_id` = ".$deal_id;
	
		$this->R($checkSql);
	
		$count = $this->fetchColumn();
	
		if ($count > 0) {
			return FALSE;
		}else{
			return TRUE;
		}
	
	}
	
	
	/**
	 * 检查是否全部处理完解冻到解冻 满标
	 * @param INT $deal_id
	 * @return boolean
	 */
	
	public function isAllSuccessFreezeToFreezeForFullDealTask($deal_id){
		
		$checkSql = "SELECT COUNT(*) FROM `".$this->tablenName."` WHERE `response_code` <> '0000' AND `deal_id` = ".$deal_id." AND `type` = 1";
		
		$this->R($checkSql);
		
		$count = $this->fetchColumn();
		
		if ($count > 0) {
			return FALSE;
		}else{
			return TRUE;
		}
		
	}
	
	
	/**
	 * 获取对应 deal_id callback处理状态数据
	 *
	 * @param int $deal_id        	
	 */
	public function getCompleteDealCallbackTask($deal_id) {
		$sql = "SELECT * FROM `" . $this->tablenName . "` WHERE `deal_id` = " . $deal_id . " AND `response_code` <> '0000'";
		
		$this->R ( $sql );
		
		return $this->fetchAll ();
	}
	
	/**
	 * 获取关于还款的单个deal_id
	 *
	 * @return string|bool
	 */
	public function getDealIdForRepayment() {
		$sqlForGetDealId = "SELECT `deal_id` FROM `fanwe_fuiou_deal_task_status` WHERE `status` = 7 LIMIT 1"; // 满标处理中
		
		$this->R ( $sqlForGetDealId );
		return $this->fetchColumn ();
	}
	/**
	 * 还款时 获取关于冻结到冻结的数据
	 *
	 * @param int $deal_id        	
	 * @return boolean
	 */
	public function getRepayTaskForFreezeToFreezeByDealId($deal_id) {
		if ($deal_id) {
			$this->R ( "SELECT *  FROM `" . $this->tablenName . "` WHERE `response_code` <> '0000' AND `deal_id` = " . $deal_id . " AND (`type` = 4 OR `type` = 5)" );
		} else {
			return FALSE;
		}
		// 返回数组中解冻和未解冻的数据。
		return $this->fetchAll ();
	}
	/**
	 * 获取还款解冻的列表数据
	 *
	 * @param int $deal_id        	
	 * @return boolean|array
	 */
	public function getRepayTaskForUnFreezeByDealId($deal_id) {
		if ($deal_id) {
			$this->R ( "SELECT *  FROM `" . $this->tablenName . "` WHERE `response_code` <> '0000' AND `deal_id` = " . $deal_id . " AND (`type` = 4 OR `type` = 5)" );
		} else {
			return FALSE;
		}
		// 返回数组中解冻和未解冻的数据。
		return $this->fetchAll ();
	}
	
	
	/**
	 * 获取流标数据deal_id
	 *
	 * @return boolean
	 */
	public function getRevokeTaskDealId() {
		$sqlForGetDealId = "SELECT `deal_id` FROM `fanwe_fuiou_deal_task_status` WHERE `status` = 4 LIMIT 1"; // 满标处理中
	
		$this->R ( $sqlForGetDealId );
	
		$deal_id = $this->fetchColumn ();
	
		return $deal_id;
	}
	
	/**
	 * 获取流标数据
	 *@param int $deal_id 
	 * @return boolean
	 */
	public function getRevokeTask($deal_id) {
		
		if ($deal_id) {
			$this->R ( "SELECT *  FROM `" . $this->tablenName . "` WHERE `response_code` <> '0000' AND `deal_id` = " . $deal_id . " AND `type` = 3" );
		} else {
			return FALSE;
		}
		// 返回数组中解冻和未解冻的数据。
		return $this->fetchAll ();
	}
	
	
	/**
	 * 更新order_id
	 *
	 * @param int $id
	 * @param String $response_code
	 * @return bool
	 */
	public function setOrderId($id, $orderId) {
		$sql = "UPDATE `" . $this->tablenName . "` SET `order_id` = '" . $orderId . "',`update_time` = " . getUTCtime() . " WHERE `id` = " . $id;
	
		return $this->U ( $sql );
	}
	
	/**
	 * 更新response_code 和 order_id
	 *
	 * @param int $id        	
	 * @param String $response_code        	
	 * @return bool
	 */
	public function setOrderIdAndResponseCode($id, $response_code, $orderId) {
		$sql = "UPDATE `" . $this->tablenName . "` SET `response_code` = '" . $response_code . "',`order_id` = '" . $orderId . "', `update_time` = " . getUTCtime() . " WHERE `id` = " . $id;
		
		return $this->U ( $sql );
	}
	
	/**
	 * 获取当前需要处理的任务 单deal_id
	 *
	 * @return 需要处理的整个数组 or false;
	 */
	public function getAll() {
		$sqlForGetDealId = "SELECT `deal_id` FROM `fanwe_fuiou_deal_task_status` WHERE `status` = 7 LIMIT 1";
		$this->R ( $sqlForGetDealId );
		$deal_id = $this->fetchColumn ();
		if ($deal_id) {
			$this->R ( "select *  FROM `" . $this->tablenName . "` WHERE `response_code` <> '0000' AND `deal_id` = " . $deal_id );
		} else {
			return FALSE;
		}
		
		return $this->fetchAll ();
	}
	
	/**
	 * 获取满标或结表时需要还款的实际金额
	 *
	 * @param int $deal_id        	
	 * @return string|boolean
	 */
	public function getMoneyForCompleteByDeal_id($deal_id) {
		$sql = "SELECT `money` FROM `" . $this->tablenName . "` WHERE `deal_id` = " . $deal_id . " AND `type`= 2";
		
		if ($this->R ( $sql )) {
			
			$sumMoney = $this->fetchColumn ();
			
			if ($sumMoney) {
				return $sumMoney;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

?>