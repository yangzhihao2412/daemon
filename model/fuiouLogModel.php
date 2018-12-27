<?php
namespace model;

use lib\Model;

class fuiouLogModel extends Model
{

    private $tablenName = 'fanwe_fuiou_log';

    
    /**
     * 满标时，冻结到冻结请求log
     *
     * @param int $userId
     * @param int $orderId
     * @param string $responseData
     *
     * @return bool
     */
    public function addRequestDataForFreezeToFreeze($userId,$orderId,$requestData)
    {
        $sql = "INSERT INTO `" . $this->tablenName . "` (`code`,`user_id`,`order_id`,`data`,`sent_date`) VALUES ( 'freezetofreeze_Task'," . $userId ."," . $orderId . ",'" . $requestData . "','" . date('Y-m-d H:i:s') . "')";
        
        return $this->C($sql);
        
    }

    /**
     * 满标时 冻结到冻结回调log
     * 
     * @param int $userId
     * @param int $orderId
     * @param string $responseData
     * 
     * @return bool
     */
    public function addResponseDataForFreezeToFreeze($userId,$orderId,$responseData)
    {
        $sql = "INSERT INTO `" . $this->tablenName . "` (`code`,`user_id`,`order_id`,`data`,`sent_date`) VALUES ( 'freezetofreeze_response'," . $userId ."," . $orderId . ",'" . $responseData . "','" . date('Y-m-d H:i:s') . "')";
        
        return $this->C($sql);
        
    }
    
    /**
     *  满标时，解冻请求log
     *
     * @param int $userId
     * @param int $orderId
     * @param string $responseData
     *
     * @return bool
     */   
    public function addUnfreezeRequestDataForUnFreeze($userId,$orderId,$requestData)
    {
        $sql = "INSERT INTO `" . $this->tablenName . "` (`code`,`user_id`,`order_id`,`data`,`sent_date`) VALUES ( 'unfreeze_Task'," . $userId ."," . $orderId . ",'" . $requestData . "','" . date('Y-m-d H:i:s') . "')";
        
        return $this->C($sql);
    }

    /**
     *   满标时，解冻回调log
     *
     * @param int $userId
     * @param int $orderId
     * @param string $responseData
     *
     * @return bool
     */
    public function addUnfreezeResponseDataForUnFreeze($userId,$orderId,$responseData)
    {
        $sql = "INSERT INTO `" . $this->tablenName . "` (`code`,`user_id`,`order_id`,`data`,`sent_date`) VALUES ( 'unfreeze_task_response'," . $userId ."," . $orderId . ",'" . $responseData . "','" . date('Y-m-d H:i:s') . "')";
        
        return $this->C($sql);
    }
}

?>