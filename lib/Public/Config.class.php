<?php
class Config {
	/**
	 *@ ÔËÐÐ»·¾³  testÎª²âÊÔ»·¾³£¬onlineÎªÏßÉÏ»·¾³
	 **/
	public static $env = 'test'; 
	/**
	 *@ redisÓÃ»§ÇóÓàµÄ»ùÊý
	 **/
	public static $redis_count = 10;
	/**
	 *@ 数据库配置
	 **/
	public static $db_config = array( 
		'test'=>array(
			'master'=>array( //召唤师主库	 各服务器不同
				'host' => '10.0.4.192',
				'port' => 3306,
				'username' => 'root',
				'password' => 'root',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'slave'=>array( //召唤师从库	 各服务器不同
				'host' => '192.168.13.24',
				'port' => 3306,
				'username' => 'cooldou',
				'password' => 'cooldou159357',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'stats'=>array( //统计用数据库         通用
				'host' => '10.0.4.192',
				'port' => 3306,
				'username' => 'root',
				'password' => 'root',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'config'=>array( //公共配置信息库 + 所有服活动配置库         通用
				'host' => '10.0.4.192',
				'port' => 3306,
				'username' => 'root',
				'password' => 'root',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'action'=>array( //所有服活动配置库                                   与config同一库
				'host' => '10.0.4.192',
				'port' => 3306,
				'username' => 'root',
				'password' => 'root',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'admin'=>array( //管理后台公用库  接口信息         	   与config同一库
				'host' => '192.168.13.24',
				'port' => 3306,
				'username' => 'cooldou',
				'password' => 'cooldou159357',
				'dbname' => 'public',
				'charset' => 'utf8'
			),
			'login'=>array( //公共登录库         通用
				'host' => '10.0.4.192',
				'port' => 3306,
				'username' => 'root',
				'password' => 'root',
				'dbname' => 'summon',
				'charset' => 'utf8'
			)
		),
		'online'=>array(
			'master'=>array(
				'host' => '10.9.29.147',
				'port' => 3306,
				'username' => 'root',
				'password' => 'YzZh!#%135',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'slave'=>array( //召唤师从库	 各服务器不同
				'host' => '10.9.29.147',
				'port' => 3306,
				'username' => 'root',
				'password' => 'YzZh!#%135',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'stats'=>array( //统计用数据库         通用
				'host' => '10.9.19.72',
				'port' => 3306,
				'username' => 'root',
				'password' => 'YzZh!#%135',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'config'=>array( //公共配置信息库 + 所有服活动配置库         通用
				'host' => '10.9.19.72',
				'port' => 3306,
				'username' => 'root',
				'password' => 'YzZh!#%135',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'action'=>array( //所有服活动配置库                                   与config同一库
				'host' => '10.9.19.72',
				'port' => 3306,
				'username' => 'root',
				'password' => 'YzZh!#%135',
				'dbname' => 'summon',
				'charset' => 'utf8'
			),
			'admin'=>array( //管理后台公用库  接口信息         	   与config同一库
				'host' => '10.9.19.72',
				'port' => 3306,
				'username' => 'root',
				'password' => 'YzZh!#%135',
				'dbname' => 'public',
				'charset' => 'utf8'
			),
			'login'=>array( //公共登录库         通用
				'host' => '10.9.21.27',
				'port' => 3306,
				'username' => 'root',
				'password' => 'YzZh!#%135',
				'dbname' => 'summon',
				'charset' => 'utf8'
			)
		)
	);
	/**
	 *@ redis ÅäÖÃÐÅÏ¢
	 **/
	public static $redis_config = array(
		'test'=>array(
			'redis0' => array('host' => '192.168.13.24', 'port' => 20000, 'pass' => 'coolplay159357'),
			'redis1' => array('host' => '192.168.13.24', 'port' => 20001, 'pass' => 'coolplay159357'),
			'redis2' => array('host' => '192.168.13.24', 'port' => 20002, 'pass' => 'coolplay159357'),
			'redis3' => array('host' => '192.168.13.24', 'port' => 20003, 'pass' => 'coolplay159357'),
			'redis4' => array('host' => '192.168.13.24', 'port' => 20004, 'pass' => 'coolplay159357'),
			'redis5' => array('host' => '192.168.13.24', 'port' => 20005, 'pass' => 'coolplay159357'),
			'redis6' => array('host' => '192.168.13.24', 'port' => 20006, 'pass' => 'coolplay159357'),
			'redis7' => array('host' => '192.168.13.24', 'port' => 20007, 'pass' => 'coolplay159357'),
			'redis8' => array('host' => '192.168.13.24', 'port' => 20008, 'pass' => 'coolplay159357'),
			'redis9' => array('host' => '192.168.13.24', 'port' => 20009, 'pass' => 'coolplay159357'),
			'Cond_0' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_1' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_2' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_3' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_4' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_5' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_6' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_7' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_8' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Cond_9' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_0' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_1' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_2' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_3' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_4' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_5' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_6' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_7' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_8' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Friend_9' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_0' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_1' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_2' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_3' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_4' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_5' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_6' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_7' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_8' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'Login_9' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357'),
			'default' => array('host' => '192.168.13.24', 'port' => 20010, 'pass' => 'coolplay159357')
		),
		'online'=>array( //ÐÅÏ¢´ýÉÏÏßÈ·ÈÏºóÔÙ½øÐÐÅäÖÃ
			'redis0' => array('host' => '10.9.31.169', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis1' => array('host' => '10.9.31.169', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis2' => array('host' => '10.9.31.169', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis3' => array('host' => '10.9.31.169', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis4' => array('host' => '10.9.31.169', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis5' => array('host' => '10.9.19.55', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis6' => array('host' => '10.9.19.55', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis7' => array('host' => '10.9.19.55', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis8' => array('host' => '10.9.19.55', 'port' => 6379, 'pass' => 'coolplay159357'),
			'redis9' => array('host' => '10.9.19.55', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_0' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_1' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_2' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_3' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_4' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_5' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_6' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_7' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_8' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Cond_9' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_0' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_1' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_2' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_3' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_4' => array('host' => '10.9.26.251', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_5' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_6' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_7' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_8' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Friend_9' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Login_0' => array('host' => '10.9.24.112', 'port' => 6379, 'pass' => 'coolplay159357'),
			'Login_1' => array('host' => '10.9.31.232', 'port' => 6379, 'pass' => 'coolplay159357'),
			'default' => array('host' => '10.9.17.40', 'port' => 6379, 'pass' => 'coolplay159357')          //公共配置 通用
		)
	);
}