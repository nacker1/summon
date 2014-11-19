<?php
/**
 *@ 服务器配置文件
 **/
define('RECOVER_TIME', 600 );							#恢复时间
define('WEEK_TIMES', 604800 );							#用户数据保存时间
define('MAIL_REGET', false );							#邮件可以重复领取  true 可以重复领取  false 不可重复领取
define('GIVE_LIFE', 2 );								#好友一次性赠送体力值
				
#==============  环境配置  =============				===================
define( 'COND_TAG', 10 );								#Cond类对应的redis端口数量  使用uid对该值取模  （支持标签 Cond, Friend, uniqPay）
define( 'USER_TAG', 10 );								#用户信息对应的redis端口数量  使用uid对该值取模
define( 'LOGIN_TAG', 10 );								#用户登录信息对应的redis端口数量  使用uid对该值取模
define( 'UNLOCK_SKILL_MAX_VALUE_VIP_LEVEL', 4);			#解锁技能点上限的vip等级
define( 'SKILL_MAX_VALUE_1', 10);						#vip指定等级以下技能点上限
define( 'SKILL_MAX_VALUE_2', 20);						#vip指定等级以下技能点上限
define('PAGE_SIZE',5);									#herobase 打开页码 /Herobase.class.php
define('BUFF_TIME',3600);                               #buff有效时长3600秒/Buff.class.php 
define('COOL_DOU',100) 									#修改召唤师昵称扣除钻石数量 /updateInfo.class.php
// define('ATT_LEVEL',0);								    #装备等级 /Equipbase.class.php


#============= log打印 =================                ===================

define('LEVEL_FATAL', 0);
define('LEVEL_ERROR', 1);
define('LEVEL_WARN', 2);
define('LEVEL_INFO', 3); 
define('LEVEL_DEBUG', 4);  
?>