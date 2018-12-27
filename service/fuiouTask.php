<?php

/**
 * 富友满标 结标 流标  还款
 * @author xuelin
 */
namespace service;

use lib\api\fuiou;
use model\fuiouTaskStatusModel;
use model\fuiouTaskModel;
use model\fuiouLogModel;
use lib\daemon;

class fuiouTask extends daemon{
	private $deal_id;
	private $fuiouTaskModel;
	private $fuiouTaskStatusModel;
	private $fuiouLogModel;
	private $fuiouApi;
	public function main() {
		$this->fuiouTaskModel = new fuiouTaskModel ();
		$this->fuiouLogModel = new fuiouLogModel();
		$this->fuiouTaskStatusModel = new fuiouTaskStatusModel();
		$this->fuiouApi = new fuiou ();
		
		
		##test
	
		
		
		
 		$this->fullTask();

		
		$this->revokeDeaTask();
		
		
		$this->repaymentTask();
		
		# endtest
		
		
	}
	
	
	// 满标
	public function fullTask() {
		
		$deal_id = $this->fuiouTaskModel->getFullDealId();

		if (!$deal_id) {
			return;
		}

		$task = $this->fuiouTaskModel->getFullDealTask ($deal_id); // 获取全部的包含解冻的未处理的数据

		if (! empty ( $task )) {
				
			$dealUserId = $task [0] ['deal_user_id']; // 借款人userId
			
			if (!$dealUserId) {
				$this->fuiouTaskStatusModel->setStatus ( $deal_id, 2 );
				return;
			}

			$loadUserIds = array (); // 投标用户的uid数组 以方便获取取出对应的手机号
				
			$dealUserMobile = $this->fuiouTaskModel->getMobileByUserId ( $dealUserId ); // 借款人富友账户
			if (! $dealUserMobile) {
                $this->fuiouTaskStatusModel->setStatus ( $deal_id, 2 );
				return;
			}
				
			foreach ( $task as $key => $value ) {
				$loadUserIds [] = $value ['load_user_id'];
			}
			if (empty($loadUserIds)) {
				$this->fuiouTaskStatusModel->setStatus ( $deal_id, 2 );
				return;
			}

			$loadUserMobiles = $this->fuiouTaskModel->getMobileByUserId ( $loadUserIds ); // 投资人富友账户
			
			$dealSumUnfreeze = array (); // 解冻row

			foreach ( $task as $key => $value ) {
	
				if ($value ['type'] == 1) { // 冻结到冻结的转移
					
					if ($value ['order_id'] != 0) { // 已处理过 order_id 默认0
						
			
						if ($this->onHasOrderId ( $value, $dealUserMobile, 1, $loadUserMobiles )) {
							
							
							continue;
							
							
						} else {
								
							return FALSE;
						}
					} else { // 未处理过
						if ($this->onHasNotOrderId ( $value, $dealUserMobile, 1, $loadUserMobiles )) {
								
							continue;
						} else {
								
							return FALSE;
						}
					}
				} else if ($value ['type'] == 2) {
	
					
					$dealSumUnfreeze = $value;
					
					
				}
			}
				
			
			if (! empty ( $dealSumUnfreeze )) {
	
				if ($this->fuiouTaskModel->isAllSuccessFreezeToFreezeForFullDealTask ( $dealSumUnfreeze ['deal_id'] )) {
						
					return $this->unfreezeDealUserMoneyForFullDealTask ( $dealSumUnfreeze, $dealUserMobile, $loadUserMobiles );
				}
			}
		} else {
			// 空数据
			$this->fuiouTaskStatusModel->setStatus ( $deal_id, 2 );
			return;
		}
	}
	
	/**
	 * 还款
	 */
	private function repaymentTask() {
		$repayMentDealId = $this->fuiouTaskModel->getDealIdForRepayment ();
	
		if (!$repayMentDealId) {
			return;
		}
	
		// 获取冻结到冻结数据
	
		$freezeToFreezeDataForRepayment = $this->fuiouTaskModel->getFreezeToFreezeForRepaymentDealTask ( $repayMentDealId );
	
		if (empty ( $freezeToFreezeDataForRepayment )) {
				$this->fuiouTaskStatusModel->setStatus ( $repayMentDealId, 8 );
			return;
		}
	
		$dealUid = $freezeToFreezeDataForRepayment [0] ['deal_user_id'];
	
		$freezeToFreezeLoadUids = array ();
	
		foreach ( $freezeToFreezeDataForRepayment as $key => $value ) {
			$freezeToFreezeLoadUids[] = $value ['load_user_id'];
		}
		// 获取借款人手机号
		$dealUserMobile = $this->fuiouTaskModel->getMobileByUserId ( $dealUid );
	
		if (! $dealUserMobile) {
			return;
		}
	
		// 获取投资人手机号列表
	
		$freezeToFreezeLoadMobiles = $this->fuiouTaskModel->getMobileByUserId ( $freezeToFreezeLoadUids );
	
		// 冻结到冻结
	
		foreach ( $freezeToFreezeDataForRepayment as $key => $value ) {
				
			// 冻结到冻结的转移
				
			if ($value ['order_id'] != 0) { // 已处理过 order_id 默认0
	
				if ($this->onHasOrderId ( $value, $dealUserMobile, 4, $freezeToFreezeLoadMobiles )) {
						
					continue;
				} else {
						
					return FALSE;
				}
			} else { // 未处理过
	
				if ($this->onHasNotOrderId ( $value, $dealUserMobile, 4, $freezeToFreezeLoadMobiles )) {
						
					continue;
				} else {
						
					return FALSE;
				}
			}
		}
	
		if ($this->fuiouTaskModel->isAllSuccessFreezeToFreezeForFullDealTask ( $repayMentDealId )) { // 冻结到冻结是否全部处理完成
	
			// 获取需要解冻的数据
				
			$unFreezeDataForRepayment = $this->fuiouTaskModel->getUnFreezeForRepaymentDealTask ( $repayMentDealId );
				
			if (! $unFreezeDataForRepayment) {
				return FALSE;
			}
				
			// 解冻投资者账户中对 利息＋本金
				
			$unFreezeLoadMobiles = array ();
				
			foreach ( $unFreezeDataForRepayment as $key => $value ) {
				$unFreezeLoadMobiles [] = $value ['load_user_id'];
			}
				
			foreach ( $unFreezeDataForRepayment as $key => $value ) {
	
				// 解冻 放款
	
				if ($value ['order_id'] != 0) { // 已处理过 order_id 默认0
						
					if ($this->onHasOrderId ( $value, $dealUserMobile, 5, $freezeToFreezeLoadMobiles )) {
	
						continue;
					} else {
	
						return FALSE;
					}
				} else { // 未处理过
						
					if ($this->onHasNotOrderId ( $value, $dealUserMobile, 5, $freezeToFreezeLoadMobiles )) {
	
						continue;
					} else {
	
						return FALSE;
					}
				}
			}
				
			// 判断还款解冻操作是否全部完成
				
			if ($this->fuiouTaskModel->isAllSuccessUnFreezeForRepayment ( $repayMentDealId )) {
	
				$this->fuiouTaskStatusModel->setStatus ( $repayMentDealId, 9 );
	
				$res = $this->fuiouApi->repaymentDealCallback ( $repayMentDealId );
				// 写日志
				if (! $res) {
					file_put_contents ( ROOTDIR . 'log/fuiouRepaymentDealCallback/' . date ( 'Y-m-d' ) . '_repaymentDealCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t faild \n", FILE_APPEND );
				} else {
					file_put_contents ( ROOTDIR . 'log/fuiouRepaymentDealCallback/' . date ( 'Y-m-d' ) . '_repaymentDealCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t success \n", FILE_APPEND );
				}
			}
		}
	}
	
	/**
	 * 流标任务处理
	 */
	public function revokeDeaTask() {
		
		$deal_id = $this->fuiouTaskModel->getRevokeTaskDealId();
		
		if (!$deal_id) {
			return;
		}
		
		
		$revokeData = $this->fuiouTaskModel->getRevokeTask ($deal_id);
	
		if (empty ( $revokeData )) {
				$this->fuiouTaskStatusModel->setStatus ( $deal_id, 5 );
			return;
		}
	
		$loadUids = array ();
	
		foreach ( $revokeData as $key => $value ) {
				
			$loadUids [] = $value ['load_user_id'];
		}
	
		if (empty ( $loadUids )) {
			return;
		}
	
		$loadUserMobiles = $this->fuiouTaskModel->getMobileByUserId ( $loadUids );
	
		// 执行投资人账户解冻金额操作
	
		foreach ( $revokeData as $key => $value ) {
				
			// 冻结到冻结的转移
				
			if ($value ['order_id'] != 0) { // 已处理过 order_id 默认0
	
				if ($this->onHasOrderId ( $value, 0, 3, $loadUserMobiles )) {
						
					continue;
				} else {
						
					return FALSE;
				}
			} else { // 未处理过
	
				if ($this->onHasNotOrderId ( $value, 0, 3, $loadUserMobiles )) {
						
					continue;
				} else {
						
					return FALSE;
				}
			}
		}
	
		if ($this->fuiouTaskModel->isAllunFreezeForRevokeTask ( $deal_id )) {
				
			$this->fuiouTaskStatusModel->setStatus ( $deal_id, 6 );
				
			$res = $this->fuiouApi->revokeDealCallback ( $deal_id );
			// 写日志
			if (! $res) {
				file_put_contents ( ROOTDIR . 'log/fuiouRevokeDealCallback/' . date ( 'Y-m-d' ) . '_revokeDealCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t faild \n", FILE_APPEND );
			} else {
				file_put_contents ( ROOTDIR . 'log/fuiouRevokeDealCallback/' . date ( 'Y-m-d' ) . '_repvokeDealCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t success \n", FILE_APPEND );
			}
		}
	}
	
	
	
	
	/**
	 * 无orderid时的操作
	 *
	 * @param unknown $value        	
	 * @param unknown $dealUserMobile        	
	 * @param unknown $type        	
	 */
	private function onHasNotOrderId($value, $dealUserMobile, $type, $loadUserMobiles = array()) {
		if ($type == 1) {
            echo '0000' .'\n\n\n';
			$result3 = $this->fuiouApi->freezeToFreeze ( $loadUserMobiles [$value ['load_user_id']], $dealUserMobile, $value ['money'] );

		}
		
		if ($type == 2) { // 满标借款人账户解冻
			$result3 = $this->fuiouApi->unFreeze ( $dealUserMobile, $value ['money'] );
		}
		
		if ($type == 4) { // 还款借款人冻结到冻结到投资人
			
			$result3 = $this->fuiouApi->freezeToFreeze ( $dealUserMobile, $loadUserMobiles [$value ['load_user_id']], $value ['money'] );
		}
		
		if ($type == 5) { // 还款解冻投资人
			
			$result3 = $this->fuiouApi->unFreeze ( $loadUserMobiles [$value ['load_user_id']], $value ['money'] );
		}
		
		if ($type == 3) { // 还款解冻投资人
			
			$result3 = $this->fuiouApi->unFreeze ( $loadUserMobiles [$value ['load_user_id']], $value ['money'] );
		}
		
		$checkstatus3 = $this->taskCheck ( $value, $result3, $type );
		
		if ($checkstatus3 != false) {
			
			if ($checkstatus3 != '0000') {
				return FALSE;
			} else { // 成功
				
				if ($type == 2) {
					// 成功，发送请求至本地回调地址
					$res = $this->fuiouApi->fullDealCallback ( $value ['deal_id'] );
					// 写日志
					if (! $res) {
						file_put_contents ( ROOTDIR . 'log/fuiouFullTask/' . date ( 'Y-m-d' ) . '_fulltaskLocalCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t faild \n", FILE_APPEND );
					} else {
						file_put_contents ( ROOTDIR . 'log/fuiouFullTask/' . date ( 'Y-m-d' ) . '_fulltaskLocalCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t success \n", FILE_APPEND );
					}
				}
				
				return TRUE;
			}
		} else {
			
			return FALSE;
		}
	}
	
	/**
	 * 当有orderId时的操作
	 *
	 * @param int $value
	 *        	fuiou_task row
	 * @param bigint $dealUserMobile
	 *        	//标所有人手机号
	 * @param int $type
	 *        	1，满标冻结到冻结 2 满标解冻
	 * @param array $loadUserMobiles
	 *        	array['userid'=>'mobile'] 格式的数据
	 */
	private function onHasOrderId($value, $dealUserMobile, $type, $loadUserMobiles = array()) {
		
		
		if ($value ['response_code'] == 0) { // 虽然已处理过，但是没有任何响应，即使有响应也可能写入response_code失败，这时用原来的订单号再次进行请求
			
			if ($type == 1) {
				
				$result = $this->fuiouApi->freezeToFreeze ( $loadUserMobiles [$value ['load_user_id']], $dealUserMobile, $value ['money'], $value ['order_id'] );

			}
			if ($type == 2) {
				
				$result = $this->fuiouApi->unFreeze ( $dealUserMobile, $value ['money'] ,$value['order_id']);//
			}
			
			if ($type == 4) {
				
				$result = $this->fuiouApi->freezeToFreeze ( $dealUserMobile, $loadUserMobiles [$value ['load_user_id']], $value ['money'], $value ['order_id'] );
			}
			
			if ($type == 5) {
				
				$result = $this->fuiouApi->unFreeze ( $loadUserMobiles [$value ['load_user_id']], $value ['money'], $value['order_id'] );
			}
			
			if ($type == 3) {
				
				$result = $this->fuiouApi->unFreeze ( $loadUserMobiles [$value ['load_user_id']], $value ['money'], $value['order_id'] );
			}
			
			
			
			$checkStatus = $this->taskCheck ( $value, $result, $type );
			
			if ($checkStatus !== false) {
				
				if ($type == 2) {
					
					if ($checkStatus === '0000') {
						
						// 成功，发送请求至本地回调地址
						$res = $this->fuiouApi->fullDealCallback ( $value ['deal_id'] );
						// 写日志
						if (! $res) {
							file_put_contents ( ROOTDIR . 'log/fuiouFullTask/' . date ( 'Y-m-d' ) . '_fulltaskLocalCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t faild \n", FILE_APPEND );
						} else {
							file_put_contents ( ROOTDIR . 'log/fuiouFullTask/' . date ( 'Y-m-d' ) . '_fulltaskLocalCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t success \n", FILE_APPEND );
						}
						
						return true; // 完成
					} else if ($checkStatus === '5345') { // 流水号重复 使用新的流水号进行请求
						
						$result6 = $this->fuiouApi->unFreeze ( $dealUserMobile, $value ['money'] );
						
						$checkStatus6 = $this->taskCheck ( $value, $result6, $type );
						
						if ($checkStatus6 !== false) {
							
							if ($checkStatus6 == '0000') {
								
								return true; // 第二次请求成功
							} else {
								
								return FALSE;
							}
						}
					} else {
						return FALSE;
					}
				} else { // 非type == 2
					
					if ($checkStatus === '0000') {
						
						return TRUE; // 完成
					}
					
					// 满标冻结到冻结
					if ($type == 1) {

						if ($checkStatus == '5345') { // 流水号重复 使用新的流水号进行请求
							
							$result1 = $this->fuiouApi->freezeToFreeze ( $loadUserMobiles [$value ['load_user_id']], $dealUserMobile, $value ['money'] );
							
							$checkStatus1 = $this->taskCheck ( $value, $result1, $type );
							
							if ($checkStatus1 !== false) {
								
								if ($checkStatus1 == '0000') {
									return TRUE; // 第二次请求成功
								} else {
									
									return FALSE;
								}
							}
						} else {
							return FALSE;
						}
					}
					
					// 还款冻结到冻结
					if ($type == 4) {
						
						if ($checkStatus == '5345') { // 流水号重复 使用新的流水号进行请求
							
							$result1 = $this->fuiouApi->freezeToFreeze ( $dealUserMobile, $loadUserMobiles [$value ['load_user_id']], $value ['money'] );
							
							$checkStatus1 = $this->taskCheck ( $value, $result1, $type );
							
							if ($checkStatus1 !== false) {
								
								if ($checkStatus1 == '0000') {
									return TRUE; // 第二次请求成功
								} else {
									
									return FALSE;
								}
							}
						} else {
							return FALSE;
						}
					}
					
					// 还款解冻
					if ($type == 5 || $type == 3) {
						
						if ($checkStatus == '5345') { // 流水号重复 使用新的流水号进行请求
							
							$result1 = $this->fuiouApi->unFreeze ( $loadUserMobiles [$value ['load_user_id']], $value ['money'] );
							
							$checkStatus1 = $this->taskCheck ( $value, $result1, $type );
							
							if ($checkStatus1 !== false) {
								
								if ($checkStatus1 == '0000') {
									return TRUE; // 第二次请求成功
								} else {
									
									return FALSE;
								}
							}
						} else {
							return FALSE;
						}
					}
				}
			} else {
				
				return FALSE; // 终止
			}
		} else {
			// 得到了必需的响应(response_code有值)，但是结果非成功 这时再次请求
			if ($value ['response_code'] == '0000') {
				return true;
			}
			
			if ($type == 1) {
				
				$result1 = $this->fuiouApi->freezeToFreeze ( $loadUserMobiles [$value ['load_user_id']], $dealUserMobile, $value ['money'] );
			}
			
			if ($type == 2) {
				$result1 = $this->fuiouApi->unFreeze ( $dealUserMobile, $value ['money'] );
			}
			
			if ($type == 4) {
				
				$result1 = $this->fuiouApi->freezeToFreeze ( $dealUserMobile, $loadUserMobiles [$value ['load_user_id']], $value ['money'] );
			}
			
			if ($type == 5) {
				
				$result1 = $this->fuiouApi->unFreeze ( $loadUserMobiles [$value ['load_user_id']], $value ['money'] );
			}
			
			if ($type == 3) {
				
				$result1 = $this->fuiouApi->unFreeze ( $loadUserMobiles [$value ['load_user_id']], $value ['money'] );
			}
			
			$checkStatus1 = $this->taskCheck ( $value, $result1, $type );
			
			if ($checkStatus1 != false) {
				
				if ($checkStatus1 != '0000') {
					
					return FALSE;
				} else {
					
					if ($type == 2) {
						// 成功，发送请求至本地回调地址
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 3 );
						$res = $this->fuiouApi->fullDealCallback ( $value ['deal_id'] );
						// 写日志
						if (! $res) {
							file_put_contents ( ROOTDIR . 'log/fuiouFullTask/' . date ( 'Y-m-d' ) . '_fulltaskLocalCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t faild \n", FILE_APPEND );
						} else {
							file_put_contents ( ROOTDIR . 'log/fuiouFullTask/' . date ( 'Y-m-d' ) . '_fulltaskLocalCallback.log', date ( "Y-m-d h:i:s" ) . "\tdeal_id \t" . $value ['deal_id'] . "\t success \n", FILE_APPEND );
						}
						
						return true;
					} else {
						
						// type = 4 AND
						
						return true;
					}
				}
			} else {
				
				return FALSE;
			}
		}
	}
	

	
	/**
	 * 富友满标解冻操作
	 *
	 * @param int $value
	 *        	fuiou_task table one row
	 * @param int $dealUserMobile        	
	 */
	private function unfreezeDealUserMoneyForFullDealTask($value, $dealUserMobile, $loadUserMobiles) {
		
		// 富友满标解冻操作
		if ($value ['order_id'] != 0) {
			
			if ($this->onHasOrderId ( $value, $dealUserMobile, 2, $loadUserMobiles )) {
				return true;
			} else {
				return FALSE;
			}
		} else {
			
			if ($this->onHasNotOrderId ( $value, $dealUserMobile, 2, $loadUserMobiles )) {
				return true;
			} else {
				return FALSE;
			}
		}
	}
	
	/**
	 *
	 * @param array $value
	 *        	一条待处理任务数据
	 * @param Array $response
	 *        	富友接口返回的数据
	 * @param number $type
	 *        	1 冻结到冻结 2 解冻
	 * @return boolean|string
	 */
	private function taskCheck($value, $result, $type = 1) {
		

		if ($type == 1) { // 满标冻结到冻结
			
			
			$this->fuiouLogModel->addRequestDataForFreezeToFreeze ( $value ['load_user_id'], $result ['order_id'], $result ['request_info'] );
			

			if ($result ['success'] == 0) { // 无响应
				
				$this->fuiouTaskModel->setOrderId ( $value ['id'], $result ['order_id'] );
				
				$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 2 );
				
				return false;
			}
			
			if ($result ['success'] == 1 || $result ['success'] == 2) {
				
				$this->fuiouLogModel->addResponseDataForFreezeToFreeze ( $value ['load_user_id'], $result ['order_id'], $result ['response_info'] );
				
					
				$this->fuiouTaskModel->setOrderIdAndResponseCode ( $value ['id'], $result ['response_code'], $result ['order_id'] );
			

				if ($result ['success'] == 2) { // 验签失败 设置状态为失败状态
					
					$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 2 );
					
					return false;
				}
				
				if ($result ['success'] == 1) {
					
					
					if ($result ['response_code'] == '0000') {
						
						return '0000'; // 成功
					}else if ($result ['response_code'] == '5345') { // 如果等于5345 说明订单号重复
						
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 2 );
						
						return '5345';
					}else{
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 2 );
						return false;
						
					}
				}
			}
		} else if ($type == 2) { // 满标解冻
			
			
		
			
			$this->fuiouLogModel->addUnfreezeRequestDataForUnFreeze ( $value ['load_user_id'], $result ['order_id'], $result ['request_info'] );
			
			if ($result ['success'] == 0) { // 无响应
				
				$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 2 );
				
				return false;
			}
			
			if ($result ['success'] == 1 || $result ['success'] == 2) {
				
				$this->fuiouLogModel->addUnfreezeResponseDataForUnFreeze( $value ['load_user_id'], $result ['order_id'], $result ['response_info'] );
				
				$this->fuiouTaskModel->setOrderIdAndResponseCode ( $value ['id'], $result ['response_code'], $result ['order_id'] );
				
				$result['success'] = 1;//for test.
				$result['response_code'] = '0000';//for test.
				
				
				if ($result ['success'] == 2) { // 验签失败 设置状态为失败状态
					$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 2 );
					return false;
				}
				
				if ($result ['success'] == 1) {
					
					if ($result ['response_code'] == '0000') {
						
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 3 );
						
						return '0000'; // 成功
					}else if ($result ['response_code'] == '5345') { // 如果等于5345 说明订单号重复
						
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 2 );
						
						return '5345';
					}else{
						

						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 2 );
						return FALSE;
					}
					
					
					
					

	
					
				}
			}
		} else if ($type == 4) {
			
			$this->fuiouLogModel->addRequestDataForFreezeToFreeze ( $value ['load_user_id'], $result ['order_id'], $result ['request_info'] );

			if ($result ['success'] == 0) { // 无响应
				
				$this->fuiouTaskModel->setOrderId ( $value ['id'], $result ['order_id'] );
				
				$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 8 );
				
				return false;
			}
			
			if ($result ['success'] == 1 || $result ['success'] == 2) {
				
				

				
				$this->fuiouLogModel->addResponseDataForFreezeToFreeze ( $value ['load_user_id'], $result ['order_id'], $result ['response_info'] );
				
				$this->fuiouTaskModel->setOrderIdAndResponseCode ( $value ['id'], $result ['response_code'], $result ['order_id'] );
				
				

				
				if ($result ['success'] == 2) { // 验签失败 设置状态为失败状态
					
					$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 8 );

					return false;
				}
				
				if ($result ['success'] == 1) {
					
					if ($result ['response_code'] == '0000') {
						
						return '0000'; // 成功
					}
					
					if ($result ['response_code'] == '5345') { // 如果等于5345 说明订单号重复
						
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 8 );
						
						return '5345';
					}else{
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 8 );
						return false;
						
					}
				}
			}
		} else if ($type == 5) { // 还款解冻
			
			$this->fuiouLogModel->addUnfreezeRequestDataForUnFreeze ( $value ['load_user_id'], $result ['order_id'], $result ['request_info'] );
			
			if ($result ['success'] == 0) { // 无响应
				
				$this->fuiouTaskModel->setOrderId ( $value ['id'], $result ['order_id'] );
				
				$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 8 );
				
				return false;
			}
			
			if ($result ['success'] == 1 || $result ['success'] == 2) {
				
				$this->fuiouLogModel->addUnfreezeResponseDataForUnFreeze( $value ['load_user_id'], $result ['order_id'], $result ['response_info'] );
				
				$this->fuiouTaskModel->setOrderIdAndResponseCode ( $value ['id'], $result ['response_code'], $result ['order_id'] );
				
				if ($result ['success'] == 2) { // 验签失败 设置状态为失败状态
					
					$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 8 );
					return false;
				}
				
				if ($result ['success'] == 1) {
					
					if ($result ['response_code'] == '0000') {
						
						return '0000'; // 成功
					}
					
					if ($result ['response_code'] == '5345') { // 如果等于5345 说明订单号重复
						
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 8 );
						
						return '5345';
					}else{
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 8 );
						return false;
						
					}
				}
			}
		} else if ($type == 3) { // 流标解冻
			
			$this->fuiouLogModel->addUnfreezeRequestDataForUnFreeze ( $value ['load_user_id'], $result ['order_id'], $result ['request_info'] );
			
			if ($result ['success'] == 0) { // 无响应
				
				$this->fuiouTaskModel->setOrderId ( $value ['id'], $result ['order_id'] );
				
				$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 5 );
				
				return false;
			}
			
			if ($result ['success'] == 1 || $result ['success'] == 2) {
				
				$this->fuiouLogModel->addUnfreezeResponseDataForUnFreeze ( $value ['load_user_id'], $result ['order_id'], $result ['response_info'] );
				
				$this->fuiouTaskModel->setOrderIdAndResponseCode ( $value ['id'], $result ['response_code'], $result ['order_id'] );
				
				if ($result ['success'] == 2) { // 验签失败 设置状态为失败状态
					
					$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 5 );
					return false;
				}
				
				if ($result ['success'] == 1) {
					
					if ($result ['response_code'] == '0000') {
						
						return '0000'; // 成功
					}
					
					if ($result ['response_code'] == '5345') { // 如果等于5345 说明订单号重复
						
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 5 );
						
						return '5345';
					}else{
						$this->fuiouTaskStatusModel->setStatus ( $value ['deal_id'], 5 );
						return false;
						
					}
				}
			}
		}
	}
}

?>