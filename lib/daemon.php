<?php

/*
 * 守护启动服务程序
 *
 * @author xuelin
 */
namespace lib;
// pcntl_signal(SIGCLD, SIG_IGN);
// pcntl_signal(SIGCHLD,SIG_IGN);
// 不在乎子进程退出

// declare(ticks = 1);//意义复杂 每执行一次简单语句就调用 pcntl_signal_dispatch
// function hup($hup){
// t::$needStop = true;
// echo 'SIGHUP IS CALLED'.$hup."\n";
// print t::$needStop;
// }

// 必须将可能改变的动态配置设置以静态属性的形式进行设定，否则在接受到HUP信号时无法改变作用变量的值。
abstract class daemon {
	public $name = 'service'; // 进程名
	protected static $staticName;//方便调用
	public $username; // 运行用户
	public  $runCount = 1;//子进程运行次数计数器
	public $runCountLimit = 5;//默认运行5次后关闭再运行
	public $runTimeInteval = 50;//运行时间间隔 单位秒
	public static $needStop = 0; // 接受sighup信号后关闭
	public function __construct() {
		pcntl_signal ( SIGHUP, __CLASS__ . "::hup" );
		
		pcntl_signal ( SIGCHLD, array (
				__CLASS__,
				"sigchld" 
		), false );
	}
	
	// 检测指定进程是否确定在运行
	public static function isRun($serviceName) {
		$pidFilePath = ROOTDIR . "./run/" . $serviceName . ".pid";
		
		if (file_exists ( $pidFilePath )) {
			
			$pid = file_get_contents ( $pidFilePath );
			
			$pid = intval ( $pid );
			$shell = 'ps -ax | grep php |awk \'{ print $1 }\' | grep -e "^' . $pid . '$"'; // 查看进程是否存在
			$isRun = shell_exec ( $shell );
			if ($isRun == $pid) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
	
	public static function console($msg,$stop = false){
		echo date ( "Y-m-d H:i:s" )."\t".$msg."\n";
		if ($stop) {
			exit();
		}
	}
	
	public static function start() {
		$isRun = false;
		
		$servConfigPath = ROOTDIR . "config/services.php";


		if (file_exists ( $servConfigPath)) {
			$serviceConf = require ROOTDIR.'./config/services.php';
			foreach ($serviceConf as $value) {
				if (self::isRun($value['name'])) {
					$isRun = true;
					break;
				}
			}
				
		} else {
			self::console ( "配置错误！".$servConfigPath."不存在",true);
		}
		
		if ($isRun) {
			self::console("已在运行状态,请勿重复使用此命令 守护程序会自动将异常关闭的服务进行重启操作",true);
		} else {
			
			umask ( 0 );
			
			$pid = pcntl_fork ();
			
			if ($pid != 0) {
				exit ();
			}
			posix_setsid ();
			
			if (pcntl_fork () != 0) { // 第二子进程下再fork，退出第二子进程
				exit ();
			}
			// 在第三子进程中运行
			chdir ( "/" );
			
			self::createPidFile ();
			
			// 关闭标准输出
			fclose ( STDIN );
			fclose ( STDOUT );
			fclose ( STDERR );
			//
			
			
			// 默认日志加载
			
			$STDIN = fopen ( ROOTDIR . '/run/runtime.log', 'a' );
			$STDOUT = fopen ( ROOTDIR . '/run/runtime.log', 'a' );
			$STDERR = fopen ( ROOTDIR . '/run/err.log', 'a' );
				
				foreach ( $serviceConf as $key => $value ) {
					if (class_exists ( $value ['class'] )) {
						if ($value['name'] == 'monitor' && $key != (count($serviceConf) - 1)) {
							$serviceConf[] = $value; //压入栈底
							unset($serviceConf[$key]);
							continue;
						}
						
							$$value ['name'] = new $value ['class'] ();
							$$value ['name']->name = $value ['name'];
							$$value ['name']->username = $value ['username'];
							$$value ['name']->setStaticServiceName($value['name']);
							$$value['name']->runCountLimit = $value['runCountLimit'];
							$$value['name']->runTimeInteval = $value['runTimeInteval'];
								
							
							$$value ['name']->setUser();
							self::console($value['name']."\t\t    [启动成功]");
							
							$$value ['name']->run ();


					} else {
						self::console("类" . $value ['name'] . "不存在");
					}
				}
				
				self::console("启动流执行完毕");
			

			
			
		}
	}
	
	
	public static function createPidFile() {
		$pidFilePath = ROOTDIR . "./run/" . self::$staticName . ".pid";
		return file_put_contents ( $pidFilePath, getmypid () );
	}
	
	// 子类实现此方法
	abstract function main();
	protected function run() {
		if (self::isRun($this->name)) {
			self::console($this->name.'正在运行，可能是在关闭中，请稍后再试');
			return;
		}
		pcntl_signal_dispatch ();
		
		if (pcntl_fork () == 0) { // 在第二子进程下再fork
			
			self::createPidFile();
			
			while ( true ) {
				pcntl_signal_dispatch ();
				$pid = pcntl_fork ();
				
				if ($pid == - 1) {
					
					self::console( pcntl_get_last_error () );
					self::closeSelf();
				}
				
				if ($pid == 0) {
					
					
					while ($this->runCount <= $this->runCountLimit) {
							$this->main (); // run in child proccess
							$this->runCount++;
							pcntl_signal_dispatch (); // 快给我发送信号	
							if (self::$needStop) {
								self::console($this->name."的子进程" . getmypid () . "接受到HUP信号,执行关闭。",true);
							}
							sleep($this->runTimeInteval);
					}
					exit;
				}
				
				if ($pid > 0) {
					
					pcntl_signal_dispatch ();
					if (self::$needStop) {
						posix_kill ( $pid, SIGHUP );
					}

					pcntl_wait ( $status ); // 防止僵尸与并发,等待完成
					                        
					// 快给我发送信号
					pcntl_signal_dispatch ();
					if (self::$needStop) {
						self::console($this->name.'关闭',1);
					}
				}
			}
		}
	}
	// 设置运行时用户
	public function setUser() {
		if (! $this->username) {
			return false;
		}
		
		$systemUser = posix_getpwnam ( $this->username );
		$uid = $systemUser ['uid'];
		if ($uid) {
			$result = posix_setuid ( $systemUser ['uid'] );
			posix_setgid ( $systemUser ['gid'] );
			return $result;;
		}else{
			return false;
		}
	}
	// 停止
	public static function stop($serviceName) {
		
		
		
		$pidFilePath = ROOTDIR.'./run/'.$serviceName.'.pid';
		
		if (file_exists($pidFilePath)) {
			if (self::isRun($serviceName)) {
				if ($serviceName == 'monitor') {
					if (posix_kill(file_get_contents($pidFilePath), SIGKILL)) {
						self::console($serviceName."\t\t    [停止成功]");
					}else{
						self::console($serviceName."\t\t    [停止失败]");
					}
				}else{
					if (posix_kill(file_get_contents($pidFilePath), SIGHUP)) {
						self::console($serviceName."\t\t    [停止成功]");
					}
				}
				
				
			}else{
				self::console($serviceName."\t\t    [未运行]");				
			}
		}else{
			self::console($serviceName."\t\t    [未运行]");
		}
		
		
	}
	
	
	
	
	
	/*
	 * 检查状态 
	 * @param $argv String command argv[2]
	 * @param $checkAll boolean 是否是检测全部 因为检测全部时 $argv2的值必定是从配置信息中获取且存在的
	 * @param $serviceConf array 服务配置信息
	 */
	
	public static function checkStatus($argv,$checkAll = false,$serviceConf = null){
		
		if (!$checkAll) {
			$pidFilePath = ROOTDIR.'./run/'.$argv.'.pid';
			$isInConfigure = false;
			foreach ($serviceConf as $key => $value) {
				if ($value['name'] == $argv) {
					$isInConfigure = true;
					break;
				}
			}
				
			if (!$isInConfigure) {
				echo $argv."\t\t 无此服务，忘了在config/services.php中配置服务？( ^_^ )\n";
				exit();
			}
		}else{
			$pidFilePath = ROOTDIR.'./run/'.$argv.'.pid';
		}
		
		if (file_exists ( $pidFilePath )) {
		
			$pid = file_get_contents ( $pidFilePath );
		
			$pid = intval ( $pid );
			$shell = 'ps -ax | grep php |awk \'{ print $1 }\' | grep -e "^' . $pid . '$"'; // 查看进程是否存在
			$isRun = shell_exec ( $shell );
			if ($isRun == $pid) {
				self::console($argv."      \t\t [正在运行]");
			} else {
				self::console($argv."      \t\t[未运行]\n");
			}
		} else {
			
			self::console($argv."      \t\t[未运行]");
		}
		
		
	}
	
	public static function closeSelf(){
		$path = ROOTDIR.'run/'.self::$staticName.'.pid';
		if (file_exists($path)) {
			$pid = file_get_contents($path);
			$pid = intval($pid);
			if (self::isRun(self::$staticName)){
				posix_kill ( $pid, SIGHUP );
			}
		}
	}
	protected function setStaticServiceName($name){
		self::$staticName = $name;
	}
	
	
	public static function hup($hup) {
		self::$needStop = true;
	}
	public function sigchld($sig) {
		while ( ($pid = pcntl_waitpid ( - 1, $status, WNOHANG )) > 0 ) {
		}
	}
}
?>