<?php 
/**
 * 所有用到的公共函数放到此类
 * @author xuelin
 */
namespace lib;
class util{
	
	/**
	 * 请求 HTTP POST
	 * 
	 * @param URL $url        	
	 * @param String $post_data        	
	 * @param URL $http_referer  default  http://www.51zichanjia.com
	 * @return String 返回的数据
	 */
	public static function httpRequestPOST($url, $postData, $http_referer = NULL) {
		$fields_string = http_build_query ( $postData, '&' );
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_HEADER, false );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 60 );
		if (! $http_referer) {
			curl_setopt ( $ch, CURLOPT_REFERER, 'http://www.51zichanjia.com' );
		} else {
			curl_setopt ( $ch, CURLOPT_REFERER, $http_referer );
		}
		curl_setopt ( $ch, CURLOPT_USERAGENT, "webservice" );
		curl_setopt ( $ch, CURLOPT_POST, 1 ); // 发送一个常规的Post请求
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields_string );
		
		$error = curl_error($ch);
		$errorNo = curl_errno($ch);
		$data = curl_exec ( $ch );
		if ($error) {
			daemon::console("网络请求失败：错误信息\n".$error."错误码".$errorNo);
		}
		
		return $data;
	}
	
	/**
	 * 获取毫秒级别的时间戳
	 * @return bigint
	 */
	public static function getMillisecondTimestamp() {
		list ( $smalltime, $time ) = explode ( ' ', microtime () );
		$smalltime = substr($smalltime, 2,3);
		$newtime = ($time - date ( 'Z' )) . $smalltime;
		return $newtime;
	}
	
	/**
	 * 获取当前的utc时间戳
	 * @return number
	 */
	public static function getUTCtime(){
		$utcTime = time() - date('Z');
		return $utcTime;
	}
	
	/**
	 * 对数组按key字母进行排序后连接成字符串
	 * @param pointer $arr
	 * @return unknown
	 */
	public static function parseArraytoStr(&$arr) {
		ksort ( $arr );
		// print_r($arr);
		$str = implode ( $arr, '|' );
	
		return $str;
	}
}



?>