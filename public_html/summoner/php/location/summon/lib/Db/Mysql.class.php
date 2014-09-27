<?php
/**
 *@ mysql类
 **/
class Db_Mysql{
	private $type = '';		#记录当前db初始化时的类型值
	private $connect;
	private $queryId;
	private $lastSql;
	private $dbconf;		#当前类型值对应的配置信息
	private $lastInsertId;		#最后插入的id
	static $mysql=array();

	private function __construct($config,$type=''){
		$this->type = $type;
		$this->dbconf = $config;
		$this->connect = mysql_connect($config['host'].':'.$config['port'],$config['username'],$config['password']) or die('Mysql connect fail!'.mysql_error());
		mysql_select_db($config['dbname'],$this->connect) or die('select_db fail'.mysql_error());
		mysql_query('set names "'.$config['charset'].'"',$this->connect) or die('mysql_query set names fail'.mysql_error());
	}
	public function getConfig(){
		return $this->dbconf;
	}

	public static function init($type=''){
		$tag = $type;
		#$db_config = isset(Config::$db_config[Config::$env][$type]) ? Config::$db_config[Config::$env][$type] : Config::$db_config[Config::$env]['slave'];
		if( empty(self::$mysql) || !isset(self::$mysql[$type]) || !self::$mysql[$type] ){
			$con = new Config($type);
			$db_config = $con->getDbConfig( $type );
			self::$mysql[$type] = new Db_Mysql($db_config,$tag);
		}
		return self::$mysql[$type];
	}

	public function find($table, $columns='*', $where='', $other = ''){//²éÑ¯Êý×éÐÎÊ½
		$cond = 'where 1=1 ';
		if(!empty($where)){
			foreach ($where as $k => $v) {
				if( is_array($v) ){
					if( isset( $v['>'] ) || isset( $v['<'] ) || isset( $v['<>'] ) ){
						foreach( $v as $kk=>$vv){
							$cond .= ' and `'.$k.'` '.$kk.$vv;
						}
					}else{
						$cond .= ' AND `'.$k.'` in ('.implode(',',$v).')';
					}
				}else{
					$value = mysql_real_escape_string($v);
					$cond .= " AND `$k` = '$value' ";
				}
			}
		}
	        	$sql = "select $columns FROM `{$table}` $cond $other";
	    	return $this->query($sql);
	}
	
	public function findOne($table, $columns='*', $where='', $other = ''){
		$cond = 'where 1=1 ';
		if(!empty($where)){
			foreach ($where as $k => $v) {
				if( is_array($v) ){
					if( isset( $v['>'] ) || isset( $v['<'] ) ){
						foreach( $v as $kk=>$vv){
							$cond .= ' and `'.$k.'` '.$kk.$vv;
						}
					}else{
						$cond .= ' AND `'.$k.'` in ('.implode(',',$v).')';
					}
				}else{
					$value = mysql_real_escape_string($v);
					$cond .= " AND `$k` = '$value' ";
				}
			}
		}
	        	$sql = "select $columns FROM `{$table}` $cond $other limit 1";
	    	$ret = $this->query($sql);
		return is_array($ret)&&count($ret)>0 ? $ret[0] : false;
	}

	public function insert($table, $row) {
		$stat = $keys = $values = '';
		foreach ($row as $k => $v) {
			$value = mysql_real_escape_string($v);
			$keys .= '`'.$k.'`,';
			$values .= '\''.$value.'\',';
		}
		$stat = substr($stat, 0, strlen($stat) - 1);
		$sql = "insert INTO `{$table}` ( ".rtrim($keys,',')." ) values (".rtrim($values,',').")";
		$this->_exet($sql);
		return $this->_insert_id();
	}

	public function update($table, $row, $where='') {
        $stat = '';
        foreach ($row as $k => $v) {
			$value = mysql_real_escape_string($v);
            $stat .= "`$k` = '$value',";
        }
        $stat = substr($stat, 0, strlen($stat) - 1);
		
        $cond = ' where 1=1';
        foreach ($where as $k => $v) {
			if( is_array($v) ){
				$cond .= ' and  '.$k.' in ('.implode($v,',').')';
			}else{
				$value = mysql_real_escape_string($v);
				$cond .= " and `$k` = '$value' ";
			}
        }
		
        $sql = "update `{$table}` SET $stat $cond";
        return  $this->_exet($sql);
	}

	public function delete( $table,$where ){
		$cond = ' where 1=1';
	            foreach ($where as $k => $v) {
			if( is_array($v) ){
				$cond .= ' and  '.$k.' in ('.implode($v,',').')';
			}else{
				$value = mysql_real_escape_string($v);
				$cond .= " and `$k` = '$value' ";
			}
	            }
		$sql = 'delete from `'.$table.'` '.$cond;
		return $this->_exet( $sql );
	}

	public function query($sql){ //Ö±½ÓÖ´ÐÐsqlÓï¾ä
		$r = array();
		$this->queryId = $this->_exet($sql);
		while($row = mysql_fetch_assoc($this->queryId)){
			$r[] = $row;
		}
		return count($r)>0 ? $r : false;
	}

	public function _exet($sql){
		mysql_select_db($this->dbconf['dbname'],$this->connect) or die('select_db fail'.self::error());
		$this->_setLastSql($sql);
		if( empty( $this->type ) ){
			if( strpos($sql,'insert') !== false || strpos($sql,'update') !== false || strpos($sql,'alter') !== false || strpos($sql,'delete') !== false ){
				$t = $this->init('master');
			}else{
				$t = $this->init('slave');
			}
		}else{
			$t = $this->init( $this->type );
		}
		$ret = mysql_query($sql,$t->getConn());
		$this->setInsertId( mysql_insert_id( $t->getConn() ) );
		if( !$ret ){
            		global $log;
			gettype($log) == 'object' && $log->i($sql.'__'.$t->error());
		}
		return $ret;
	}
	private function _setLastSql($sql){
		$this->lastSql = $sql;
	}
	public function getMyId(){
		return mysql_thread_id( $this->connect );
	}
	public function getLastSql(){
		return $this->lastSql;
	}
	private function _insert_id(){
		return $this->lastInsertId;
	}
	private function setInsertId( $id ){
		return $this->lastInsertId = $id;
	}
	/**
     * È¡µÃÊý¾Ý¿âµÄ±íÐÅÏ¢
     * @access public
     * @return array
     */
    public function getTables($dbName='') {
        if(!empty($dbName)) {
           $sql    = 'SHOW TABLES FROM '.$dbName;
        }else{
           $sql    = 'SHOW TABLES ';
        }
        $result =   $this->query($sql);
        $info   =   array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }
        return $info;
    }

	public function getConn(){
		return $this->connect;
	}

	/**
     * Êý¾Ý¿â´íÎóÐÅÏ¢
     * ²¢ÏÔÊ¾µ±Ç°µÄSQLÓï¾ä
     * @access public
     * @return string
     */
    public function error() {
        return mysql_errno($this->connect).':'.mysql_error($this->connect);
    }
	/**
     * ÊÍ·Å²éÑ¯½á¹û
     * @access public
     */
    public function free() {
        mysql_free_result($this->queryId);
        $this->queryID = null;
    }
	public function _close() {
        if($this->connect){
            $ret = mysql_close($this->connect);
        }
        $this->connect = null;
    }
	 /**
     * ¹Ø±ÕÊý¾Ý¿â
     * @access public
     * @return void
     */
    public function close() {
        foreach( self::$mysql as $v ){
			$v->_close();
		}
    }
	/**
     * Îö¹¹·½·¨
     * @access public
     */
    public function __destruct() {
        // ÊÍ·Å²éÑ¯
        if ( !empty( $this->queryId ) ){
            $this->free();
        }
    }
}