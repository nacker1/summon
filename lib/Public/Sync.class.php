<?php
/**
 *@ 用户数据库自动同步类 
 **/
class Sync extends Base{
	private $table;				//需要同步的表
	private $data;				//同步的数据
	private $where;				//同上数据时的条件
	private $opt;				//操作类型  1为insert, 2为updata, 3为删除
	private $db;				//用户数据库
	private $dbTag;				//数据库标签

	function __construct( $data ){
		parent::__construct();
		$this->table = $data['table'];
		$this->data = $data['data'];
		$this->where = $data['where'];
		$this->opt = isset($data['opt'])?$data['opt']:'';
		$this->dbTag = isset($data['tag'])?$data['tag']:'master';
		
		if( empty($this->opt) ){
			if( empty( $this->where ) ){
				$this->opt = 1;
			}elseif( empty( $this->data ) ){
				$this->opt = 3;
			}else{
				$this->opt = 2;
			}
		}
	}

	function sendCommand(){
		$com = 'php /data/web/summon/syncDb.php -t '.$this->table.' -d \''.serialize($this->data).'\' -w \''.serialize($this->where).'\' -o '.$this->opt.' -f '.$this->dbTag.' &';
		if( strpos( PHP_OS, 'Linux' ) !== -1 ){
			$this->log->i($com.strpos( PHP_OS, 'Linux' ));
			pclose( popen( $com,'r') );
		}else
			$this->log->e($com.PHP_OS);
	}

	function exec(){ //执行sendCommand抛出来的sql
		C('com_start',gettimeofday(true));
		$this->db = Db_Mysql::init( $this->dbTag );
		switch( $this->opt ){
			case '1':
				$ret = $this->db->insert( $this->table,$this->data );break;
			case '2':
				$ret = $this->db->update( $this->table,$this->data,$this->where );break;
			case '3':
				$ret = $this->db->delete( $this->table,$this->where );break;
		}
		$this->log->i( $this->db->getLastSql().'【'. ( gettimeofday(true) - C('com_start') ).'】' );
		if( !$ret )
			$this->log->e( $this->db->getLastSql() );
	}
}
?>