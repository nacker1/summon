<?php
/**
 *@ 用户登录注册接口
 **/
 class User_Reg extends User_Rolebase{
	private $table='zy_loginRegister'; 				//用户注册表名
	private $source;								//登录平台id
	private $source_id; 							//合作登录平台对应用户uid
	private $name;									//用户昵称
	private $channel;								//用户渠道
	private $loginDb;								//登录用Db
	private $loginRedis;							//登录用redis


	
	public function __construct($source,$source_id,$name='',$channel=''){
		$this->source = $source ;
		$this->source_id = $source_id ;
		if( empty( $name ) ){
			$this->name = $source.'_'.$source_id;
		}else{
			$this->name = $name;
		}
		if( empty($this->source) || empty($this->source_id) ){
			ret('请求参数错误',-1);
		}
		$this->loginDb = Db_Mysql::init('login');
		$this->_init();	
	}
	
	private function _init(){
		$ret = $this->loginDb->findOne($this->table,'id,status',array( 'source'=>$this->source, 'source_id'=>$this->source_id ));
		if( $ret && is_array($ret) && count($ret)>0 ){
			$rid = $ret['id'];
			if( 1 == $ret['status'] ){
				ret('对不起，您已被封号，请联系客服人员处理。',-1);
			}
		}else{
			$rid = $this->_createUser();
		}
		if( empty($rid) ){
			ret('server error!',-1);
		}
		parent::__construct($rid);
	}

	private function _createUser(){ //用户不存在时注册用户
		$new['name'] = $this->name;
		$new['source'] = $this->source;
		$new['source_id'] = $this->source_id;
		$new['regtime'] = time();
		$new['channel'] = $this->channel;
		return $this->loginDb->insert($this->table,$new);
	}
	
	public function getLoginInfo(){
		$ret['rid'] = $this->rid;
		$ret['sid'] = $this->getUserLastServerId(); //上次登录的服务器id
		$server = new Server();
		if( empty($ret['sid']) ){
			$ret['sid'] = $server->getNewServerId();
		}
		return $ret;
	}	
 }
?>