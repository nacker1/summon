<?php
/**
 *@ 服务器配置文件
 **/
	define('RECOVER_LIFE_TIME'					, 360 );			 #恢复1点体力需要的时间
	define('RECOVER_POINT_TIME'					, 600 );			 #恢复1点技能点需要的时间
	define('GAG_TIME'							, 600 );			 #禁言时间 10 分钟
	define('WEEK_TIMES'							, 604800 );			 #用户数据保存时间
	define('MAIL_REGET'							, false );			 #邮件可以重复领取  true 可以重复领取  false 不可重复领取
	define('GIVE_LIFE'							, 2 );				 #好友一次性赠送体力值
															
	#==============		环境配置	=================================================================
	define( 'COND_TAG'							, 10 );				 #Cond类对应的redis端口数量  使用uid对该值取模  （支持标签 Cond, Friend, uniqPay）
	define( 'USER_TAG'							, 10 );				 #用户信息对应的redis端口数量  使用uid对该值取模
	define( 'LOGIN_TAG'							, 10 );				 #用户登录信息对应的redis端口数量  使用uid对该值取模
	define( 'UNLOCK_SKILL_MAX_VALUE_VIP_LEVEL'	, 4);				 #解锁技能点上限的vip等级
	define( 'SKILL_MAX_VALUE_1'					, 10);				 #vip指定等级以下技能点上限
	define( 'SKILL_MAX_VALUE_2'					, 20);				 #vip指定等级以下技能点上限								
					
	define('PAGE_SIZE'							,5);				 #herobase 打开页码 /Herobase.class.php
	define('BUFF_TIME'							,3600);              #buff默认有效时长3600秒/Buff.class.php 
	define('EDIT_USERNAME_COOLDOU'				,100); 				 #修改召唤师昵称扣除钻石数量 /updateInfo.class.php
	define('WAR_LOW_LEVEL'						,15);				 #战争学院用户最低等级
	define('GENERAL_ID'							,11999);			 #万能灵魂石id
	define('ADMIN_UID'							,1);				 #超级用户uid
	define('STRIKE_TIMES'						,1800);				 #战争学院敲醒功能默认美中间隔时间  30分钟

	#============= log打印 =================                ===================

	define('LOG_LEVEL', 3);											 #打印日志的等级  0 = LEVEL_FATAL，1 = LEVEL_ERROR, 2 = LEVEL_WARN，3 = LEVEL_INFO，4 = LEVEL_DEBUG  4以上的数字为所有日志



	#============== 关卡类配置  ===================
	define( 'INIT_ROUNDID', 910102);								#初始关卡任务的关卡id
?>
