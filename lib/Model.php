<?php
/*
 * mysql pdo
 * @author xuelin
 */

namespace lib;
class Model {
	protected $pdoConnection;
	private $statement; 
	protected $connection;
	public function __construct($connection = "mysql") {
		$this->connection = $connection;
		$this->getPdoConnection ( $this->connection );
	}
	
	/**
	 * 获取PDO实例
	 * @author yangzhihao
	 */
	protected function getPdoConnection($dbName = 'mysql') {
		$dbConfigPath = ROOTDIR."config/dbConfig.php";
		if (file_exists ( $dbConfigPath )) {
			$dbConfig = include $dbConfigPath;
			if (array_key_exists ( $dbName, $dbConfig )) {
				try {
					$this->pdoConnection = new \PDO ( 'mysql:host=' . $dbConfig [$dbName] ['host'] . ';dbname=' . $dbConfig [$dbName] ['dbname'], $dbConfig [$dbName] ['username'], $dbConfig [$dbName] ['password'],array(\PDO::ATTR_PERSISTENT => true));
					
					$this->pdoConnection->query("SET NAMES UTF8");
					
					$this->pdoConnection->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
				} catch ( \PDOException $e ) {
					echo "数据库连接错误".$e->getMessage()."\n\n\n";
					sleep(30);
					daemon::closeSelf();
					exit ();
				}
			} else {
				echo "dbName在".$dbConfig."中不存在"."\n\n\n";
				daemon::closeSelf();
				exit ();
			}
		} else {
			echo ROOTDIR."config/dbConfig.php 不存在"."\n\n\n";
			daemon::closeSelf();
			exit ();
		}
		return $this->pdoConnection;
	}
	
	/**
	 * 查询操作
	 */
	protected function execute($param = null) {
		return $this->statement->execute ( $param );
	}
	protected function exec($statement = null) {
		if ($statement) {
			return $this->pdoConnection->exec ( $statement );
		} else {
			return $this->pdoConnection->exec ( $this->statement );
		}
	}
	protected function bindParam($key, $value) {
		return $this->statement->bindParam ( $key, $value );
	}
	protected function bindValue($key, $value) {
		return $this->statement->bindValue ( $key, $value );
	}
	protected function prepare($sql) {
		$this->statement = $this->pdoConnection->prepare ( $sql );
		return $this->statement;
	}
	public function C($sql, $params = null) {
		try {
			$this->prepare ( $sql )->execute ( $params );
			return $this->getLastInsertId ();
		} catch ( \PDOException $e ) {
			$this->outputException($e);
		}
	}
	public function R($sql, $params = NULL) {
		try {
			$this->statement = $this->prepare ( $sql );
			return $this->execute ( $params );
		} catch ( \PDOException $e ) {
			$this->outputException($e);
		}
	}
	public function U($sql, $params = NULL) {
		try {
			return $this->prepare ( $sql )->execute ( $params );
		} catch ( \PDOException $e ) {
			$this->outputException($e);
		}
	}
	public function D($sql, $params = NULL) {
		try {
			return $this->prepare ( $sql )->execute ( $params );
		} catch ( \PDOException $e ) {
			$this->outputException($e);
		}
	}
	public function fetchColumn() {
		return $this->statement->fetchColumn ();
	}
	public function fetchAll($pdoFetchMode = \PDO::FETCH_ASSOC) {
		return $this->statement->fetchAll ( $pdoFetchMode );
	}
	public function fetch($pdoFetchMode = \PDO::FETCH_ASSOC) {
		return $this->statement->fetch ( $pdoFetchMode );
	}
	protected function getLastInsertId() {
		return $this->pdoConnection->lastInsertId ();
	}
	
	public function __destruct(){
		$this->pdoConnection = null;
	}
	
	private function outputException($e){
		echo $e->getMessage()."\n";
		echo "debug_trace:\n";
		print_r(debug_backtrace());
		echo "\n\n\n";
		daemon::closeSelf();
		exit;
	}
	
}
?>