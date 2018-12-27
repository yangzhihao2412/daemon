<?php 
namespace  service;


use lib\api\fuiou;
use lib\daemon;


define("ROOTDIR",dirname(__DIR__).DIRECTORY_SEPARATOR);

if (!defined('ROOTDIR')) {
    echo "请先设置常量ROOTDIR";
    exit;
}




require_once ROOTDIR.'core.php';


class ceshiModel extends daemon {

    private $fuiouApi;
    public function main() {
        
        
        
        $from_account = '15378900987';
        $to_account = '15278900987';
        $money = '3500';
        
        
        
        $this->fuiouApi = new fuiou ();
     
        //手机号和钱
        $res = $this->fuiouApi->freeze($from_account,$money);
        // 写日志
     // 写日志
        if (! $res) {
            file_put_contents ( ROOTDIR . 'log/fuiouRepaymentDealCallback/' . date ( 'Y-m-d' ) . '_repaymentDealCallback.log', $from_account .'冻结'. "\tdeal_id \t冻结失败\t faild \n", FILE_APPEND );
        } else {
            file_put_contents ( ROOTDIR . 'log/fuiouRepaymentDealCallback/' . date ( 'Y-m-d' ) . '_repaymentDealCallback.log', $from_account . '冻结'."\tdeal_id \t冻结成功\t success \t" . $res['response_code']."\n", FILE_APPEND );
        }
        
        $res1 = $this->fuiouApi->freezeToFreeze($from_account,$to_account,$money);
        
     // 写日志
        if (! $res1) {
            file_put_contents ( ROOTDIR . 'log/fuiouRepaymentDealCallback/' . date ( 'Y-m-d' ) . '_repaymentDealCallback.log', $from_account . '转账到' . $to_account . "\tdeal_id \t转账失败\t faild \n", FILE_APPEND );
        } else {
            file_put_contents ( ROOTDIR . 'log/fuiouRepaymentDealCallback/' . date ( 'Y-m-d' ) . '_repaymentDealCallback.log', $from_account . '转账到' . $to_account  . "\tdeal_id \t转账成功\t success \t" . $res1['response_code']."\n", FILE_APPEND );
        }
        
        $res2 = $this->fuiouApi->unFreeze($to_account,$money);
        
        // 写日志
        if (! $res2) {
            file_put_contents ( ROOTDIR . 'log/fuiouRepaymentDealCallback/' . date ( 'Y-m-d' ) . '_repaymentDealCallback.log', $to_account .'解冻' . "\tdeal_id \t解冻失败\t faild \n", FILE_APPEND );
        } else {
            file_put_contents ( ROOTDIR . 'log/fuiouRepaymentDealCallback/' . date ( 'Y-m-d' ) . '_repaymentDealCallback.log', $to_account .'解冻'. "\tdeal_id \t解冻成功\t success \t" . $res2['response_code'] ."\n", FILE_APPEND );
        }


    }
}

$ceshiModel = new ceshiModel();

$ceshiModel->main();




?>