<?php
class Public_Modinit{
    private $mid;                       #模块id cmd
    private $key;                       #校验key
    private $path;                      #cmd对应的文件路径
    private $version;                   #cmd对应的版本要求
    private $status;                    #cmd的状态
    private $tag;                       #cmd标签用于打日志
    private $islog;                     #是否打印日志
    private $exist;                     #指定cmd是否存在
    private $online;                    #是否在线
	private $name;                      #指定cmd的名称
	private $gameid=2;                  #游戏业务id  1 斗地主  2为召唤师

    public function __construct($mid,$key,$ver){
        $this->key = $key;
        $this->mid = $mid;
        $this->version = $ver;
        $this->_getModinfo();
        $this->exist || ret('Module not exist!',-1);
        if(!$this->status()){
            ret('Module was deprecated!!',-1);
        }
        if(!$this->isOnline()){
            ret('Module not online!',-1);
        }
        $this->islog();
    }
    
    private function _getModinfo(){
        $pre = Redis_Redis::init();
        $modinfo = $pre->hgetall('modinfo:'.$this->gameid.':'.$this->mid);
        if( C('test') || !$modinfo || !is_array($modinfo) || !count($modinfo)>0 ){
            $db = Db_Mysql::init('admin');
            $modinfo = $db->findOne('zy_modConfig','`mid`,`path`,`version`,`status`,`tag`,`key`,`islog`,`online`,`info`,`name`',array('mid'=>$this->mid,'gameid'=>$this->gameid) );
            unset($modinfo['id']);
            if($modinfo){
				$modinfo['info'] = strip_tags($modinfo['info']);
                $pre->hmset('modinfo:'.$this->gameid.':'.$this->mid,$modinfo,get3time());
            }
        }
        if( is_array($modinfo) && count($modinfo)>0 ){
            $this->key == $modinfo['key'] || ret('key error!',-1);
            $this->path = $modinfo['path'];
            if( !empty($modinfo['version']) && $modinfo['version'] > 0 ){
                $this->version >= $modinfo['version'] || ret('Version '.$modinfo['version'].' minimum requirements');
            }
            $this->status = $modinfo['status'];
            $this->tag = $modinfo['tag'].'_'.gettimeofday(true);
            $this->islog = $modinfo['islog'];
            $this->exist = true;
            $this->online = $modinfo['online'];
		$this->name = isset($modinfo['name'])?$modinfo['name']:'';
        }else{
            $this->exist = false;
        }
    }
    
    public function isExist(){
        return $this->exist;
    }

    public function path(){
        return '/module/'.$this->path;
    }

    public function status(){
        $env = Config::$env;
        if( $env == 'online'){
            if($this->status == 1)
                return true;
        }else if( $this->status == 3 ){
            return false;
        }else{
            return true;
        }
        return false;
    }
    public function isOnline(){
        return $this->online;
    }
    public function tag(){
        return $this->tag;
    }
    public function name(){
        return $this->name;
    }
    public function islog(){
        return $this->islog==1 ? define('ISLOG',true) : define('ISLOG',false);
    }

}
