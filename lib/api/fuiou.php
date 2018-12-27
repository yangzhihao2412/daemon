<?php

namespace lib\api;

use lib\xml;
use lib\util;

/**
 * 富友服务接口
 *
 * @author xuelin
 */
class fuiou {
	private $merNo; // 商户号
	private $APIuri; // 富友接口地址前缀
	private $localCallBackPassword; // 请求内部接口密码
	private $localCallBackUrl; // 请求内部接口url地址
	private $prKeyFilePath; // 私钥文件地址
	private $pbKeyFilePath; // 公钥文件地址
	public function __construct() {
		$configPath = ROOTDIR . 'config/fuiouConfig.php';
		
		if (file_exists ( $configPath )) {
			
			$fuiouConfigArr = require_once $configPath;
			
			$this->localCallBackPassword = $fuiouConfigArr ['local_callback_password'];
			$this->localCallBackUrl = $fuiouConfigArr ['local_callback_url'];
			if ($fuiouConfigArr ['is_debug']) {
				
				$fuiouConfigArr = $fuiouConfigArr ['debug'];
			} else {
				
				$fuiouConfigArr = $fuiouConfigArr ['normal'];
			}
			
			$this->merNo = $fuiouConfigArr ['merNo'];
			
			$this->APIuri = $fuiouConfigArr ['APIuri'];
			
			$this->pbKeyFilePath = $fuiouConfigArr ['pbKeyFilePath'];
			$this->prKeyFilePath = $fuiouConfigArr ['prKeyFilePath'];
		}
	}
	
	/**
	 * 冻结
	 *
	 * @param int $account        	
	 * @param int $money
	 *        	注意钱的单位是元
	 * @return bool or array
	 */
	public function freeze($account, $money) {
		$APIuri = $this->APIuri . 'freeze.action';
		
		$data = array ();
		
		$data ['mchnt_cd'] = $this->merNo;
		$data ['mchnt_txn_ssn'] = util::getMillisecondTimestamp ();
		$data ['cust_no'] = $account;
		$data ['amt'] = bcmul ( $money, 100, 0 );
		$data ['rem'] = '冻结接口';
		
		$sign = util::parseArraytoStr ( $data );
		$data ['signature'] = $this->rsaSign ( $sign );
		
		$response = util::httpRequestPOST ( $APIuri, $data );
		
		if (empty ( $response )) {
			return FALSE;
		}
		$xml = new xml ( $response );
		
		if ($this->verification ( $response, $xml->data )) {
			return Array (
					'response_info' => $response,
					'request_info' => serialize ( $data ),
					'response_code' => $xml->data ['ap'] [0] ['plain'] [0] ['resp_code'] 
			);
		} else {
			return FALSE;
		}
	}
	
	/**
	 * 冻结到冻结
	 *
	 * @param int $from_account        	
	 * @param int $to_account        	
	 * @param int $money
	 *        	注意钱的单位是元
	 * @param object $dbModel
	 *        	数据模型引用
	 * @return bool or array
	 */
	public function freezeToFreeze($from_account, $to_account, $money, $orderId = null) {
		$APIuri = $this->APIuri . 'transferBuAndFreeze2Freeze.action';
		
		$data = array ();
		
		$data ['mchnt_cd'] = $this->merNo;
		
		if (! empty ( $orderId )) {
			
			$data ['mchnt_txn_ssn'] = $orderId;
		} else {
			
			$data ['mchnt_txn_ssn'] = util::getMillisecondTimestamp ();
		}
		
		$data ['out_cust_no'] = $from_account;
		$data ['in_cust_no'] = $to_account;
		$data ['amt'] = bcmul ( $money, 100, 0 );
		$data ['rem'] = '满标时调用冻结到冻结接口';
		
		$sign = util::parseArraytoStr ( $data );
		$data ['signature'] = $this->rsaSign ( $sign );
		
		$response = util::httpRequestPOST ( $APIuri, $data );
		$xml = new xml ( $response );
		if (empty ( $response )) {
			return Array (
					'success' => 0,
					'order_id' => $data ['mchnt_txn_ssn'],
					'request_info' => serialize ( $data ) 
			);
		}
		
		if ($this->verification ( $response, $xml->data )) {
			return Array (
					'success' => 1, // 成功
					'response_info' => serialize ( $xml->data ),
					'request_info' => serialize ( $data ),
					'response_code' => $xml->data ['ap'] [0] ['plain'] [0] ['resp_code'],
					'order_id' => $data ['mchnt_txn_ssn'] 
			);
		} else {
			return Array (
					'success' => 2, // 验签失败，但获取了数据
					'response_info' => serialize ( $xml->data ),
					'request_info' => serialize ( $data ),
					'response_code' => $xml->data ['ap'] [0] ['plain'] [0] ['resp_code'],
					'order_id' => $data ['mchnt_txn_ssn'] 
			);
		}
	}
	/**
	 * 获取用户资金概况
	 *
	 * @param number $account        	
	 * @return mixed
	 */
	public function getBanlance($account) {
		$APIuri = $this->APIuri . 'BalanceAction.action';
		
		$data = array ();
		
		$data ['mchnt_cd'] = $this->merNo;
		$data ['mchnt_txn_ssn'] = util::getMillisecondTimestamp ();
		$data ['mchnt_txn_dt'] = date ( "Ymd" );
		
		$data ['cust_no'] = $account;
		
		$sign = util::parseArraytoStr ( $data );
		$data ['signature'] = $this->rsaSign ( $sign );
		
		$response = util::httpRequestPOST ( $APIuri, $data );
		$xml = new xml ( $response );
		
		return $xml->data;
	}
	
	/**
	 * 解冻
	 *
	 * @param int $account        	
	 * @param int $money
	 *        	注意钱的单位是元
	 * @param int $order_id
	 *        	流水号 默认为空 空则取毫秒级时间戳
	 * @return bool|array(respose_info,request_info,response_code,order_id)
	 */
	public function unFreeze($account, $money, $order_id = NULL) {
		$APIuri = $this->APIuri . 'unFreeze.action';
		
		$data = array ();
		
		$data ['mchnt_cd'] = $this->merNo;
		if ($order_id) {
			$data ['mchnt_txn_ssn'] = $order_id;
		} else {
			$data ['mchnt_txn_ssn'] = util::getMillisecondTimestamp ();
		}
		$data ['cust_no'] = $account;
		$data ['amt'] = bcmul ( $money, 100, 0 );
		$data ['rem'] = '';
		
		$sign = util::parseArraytoStr ( $data );
		$data ['signature'] = $this->rsaSign ( $sign );
		
		$response = util::httpRequestPOST ( $APIuri, $data );
		
		if (empty ( $response )) {
			return Array (
					'success' => 0,
					'order_id' => $data ['mchnt_txn_ssn'],
					'request_info' => serialize ( $data ) 
			);
		}
		
		$xml = new xml ( $response );
		
		if ($this->verification ( $response, $xml->data )) {
			return Array (
					'success' => 1, // 成功
					'response_info' => serialize ( $xml->data ),
					'request_info' => serialize ( $data ),
					'response_code' => $xml->data ['ap'] [0] ['plain'] [0] ['resp_code'],
					'order_id' => $data ['mchnt_txn_ssn'] 
			);
		} else {
			return Array (
					'success' => 2, // 验签失败，但获取了数据
					'response_info' => serialize ( $xml->data ),
					'request_info' => serialize ( $data ),
					'response_code' => $xml->data ['ap'] [0] ['plain'] [0] ['resp_code'],
					'order_id' => $data ['mchnt_txn_ssn'] 
			);
		}
	}
	
	/**
	 * 验证签名
	 *
	 * @param pointer $xmlString
	 *        	原始xml数据
	 * @param pointer $dataArr
	 *        	xml转换后的数组
	 * @param string $sign        	
	 * @return bool
	 */
	public function verification(&$xmlString, &$dataArr) {
		if (! $dataArr) {
			return FALSE;
		}
		
		$plainRegPattern = '/<plain>(.*)<\/plain>/';
		
		if (preg_match ( $plainRegPattern, $xmlString, $regxResult )) {
			
			$signVaildStr = $regxResult [0];
			
			if ($this->rsaVerify ( $signVaildStr, $dataArr ['ap'] [1] ['signature'] )) {
				return true;
			} else {
				return FALSE;
			}
		} else {
			
			return FALSE;
		}
	}
	
	/**
	 * RSA签名
	 *
	 * @param $data 待签名数据(按照文档说明拼成的字符串)        	
	 * @param $private_key_path 商户私钥文件路径
	 *        	return 签名结果
	 */
	private function rsaSign($data) {
		$prKey = file_get_contents ( $this->prKeyFilePath );
		
		$res = openssl_get_privatekey ( $prKey );
		$sign = '';
		openssl_sign ( $data, $sign, $res );
		openssl_free_key ( $res );
		// base64编码
		$sign = base64_encode ( $sign );
		return $sign;
	}
	
	/**
	 * RSA验签
	 *
	 * @param $data 待签名数据(文档中<plain>标签的值,包含<plain>标签)        	
	 * @param $ali_public_key_path 富友的公钥文件路径        	
	 * @param $sign 要校对的的签名结果        	
	 * @return boolean 验证结果
	 */
	private function rsaVerify($data, $sign) {
		$pubKey = file_get_contents ( $this->pbKeyFilePath );
		$res = openssl_get_publickey ( $pubKey );
		$result = ( bool ) openssl_verify ( $data, base64_decode ( $sign ), $res );
		openssl_free_key ( $res );
		return $result;
	}
	
	/**
	 * 调用本地服务完成满标最后一公里
	 * 
	 * @param int $deal_id        	
	 * @return bool
	 */
	public function fullDealCallback($deal_id) {
		$url = $this->localCallBackUrl . "/index.php?ctl=collocation&act=localCallback&class_name=Fuiou&class_act=DoLoans";
		
		$postData = array (
				'deal_id' => $deal_id,
				'password' => $this->localCallBackPassword 
		);
		
		$result = util::httpRequestPOST ( $url, $postData );
		
		if (! empty ( $result )) {
			
			$result = json_decode ( $result ,true);
			
			if ($result ['success']) {
				return TRUE;
			}
		}
	}
	
	public function repaymentDealCallback($deal_id){
		
		$url = $this->localCallBackUrl . "/index.php?ctl=collocation&act=localCallback&class_name=Fuiou&class_act=repayment";
		
		$postData = array (
				'deal_id' => $deal_id,
				'password' => $this->localCallBackPassword
		);
		
		$result = util::httpRequestPOST ( $url, $postData );
		
		if (! empty ( $result )) {
				
			$result = json_decode ( $result ,true);
				
			if ($result ['success']) {
				return TRUE;
			}
		}
		
	}
	
	
	public function revokeDealCallback($deal_id){
	
		$url = $this->localCallBackUrl . "/index.php?ctl=collocation&act=localCallback&class_name=Fuiou&class_act=DoBids";
	
		$postData = array (
				'deal_id' => $deal_id,
				'password' => $this->localCallBackPassword
		);
	
		$result = util::httpRequestPOST ( $url, $postData );
	
		if (! empty ( $result )) {
	
			$result = json_decode ( $result ,true);
	
			if ($result ['success']) {
				return TRUE;
			}
		}
	
	}
	
	
}

?>