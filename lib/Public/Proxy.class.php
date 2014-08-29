<?php
/**
 *@ Proxy 代理类
 **/
class Proxy {
	private $class;			//代理的类名
	private $fun;			//代理类中的方法名

	function __construct( $args, $className, $funName ){
		$class = new ReflectionClass($className);
		$this->class =  $class->newInstance($args);
		$this->fun = $funName;
	}

	function exec(){
		return call_user_func_array( array( $this->class,$this->fun ), func_get_args() );
	}
}
?>