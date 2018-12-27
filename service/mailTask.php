<?php
namespace service;
use lib\daemon;
use lib\smtp;
use model\MailLog;

class mailTask extends daemon{
	
	private $sendInteval = 6;//发送时间间隔 秒 防止被邮件服务器 提示发送频率过高
	private $dbCon; //数据库连接句柄
	private $mailConfig;//邮件配置
	private $mailModel;//模型，可改用redis...
	private $smtpHandle;
	
	public function main() {
		// TODO Auto-generated method stub
		try {
			$this->smtpHandle = new smtp();
			$mailModel = new MailLog();
			$queue = $mailModel->getQueue();
			
			if (!$queue) {
				sleep($this->runTimeInteval);
				exit;
			}
			foreach ($queue as $key => $value) {
			
				$this->smtpHandle->addTo($value['mail_to'], 'baofoo');
				$this->smtpHandle->setSubject($value['mail_title']);
				$this->smtpHandle->setMessage($value['mail_contents'], true);
			
			
				$sendStatus = $this->smtpHandle->send();
				if($sendStatus == 250){
						
					$mailModel->setSend($value['id'],1);
					unset($queue[$key]);
					$this->smtpHandle->clearTo();
				}
				sleep($this->sendInteval);
			}
			foreach ($queue as $key => $value) {
			
				$this->smtpHandle->addTo($value['mail_to'], 'baofoo');
				$this->smtpHandle->setSubject($value['mail_title']);
				$this->smtpHandle->setMessage($value['mail_contents'], true);
			
				$sendStatus = $this->smtpHandle->send();
				if($sendStatus == 554){//smtp server result spam
					//$mailModel->setSend($value['id'],2);
				}else if($sendStatus == 250){
					$mailModel->setSend($value['id'],1);
					unset($queue[$key]);
				}else{
					//$mailModel->setSend($value['id'],3);
				}
				sleep($this->sendInteval);
			}
		} catch (\Exception $e) {
			echo $e->getMessage()."\n\n\n";
			self::closeSelf();
		}
		
		
		
	}
	
	public function __destruct(){
		//$this->dbCon = null;
	}

	
}
?>











