<?php
/**
 *@ 服务器配置文件
 **/
define('RECOVER_TIME', 600 );			#恢复时间
define('WEEK_TIMES', 604800 );			#用户数据保存时间
define('MAIL_REGET', false );			#邮件可以重复领取  true 可以重复领取  false 不可重复领取

#==============  环境配置  ================================
define( 'COND_TAG', 10 );				#Cond类对应的redis端口数量  使用uid对该值取模  （支持标签 Cond, Friend, uniqPay）
define( 'USER_TAG', 10 );				#用户信息对应的redis端口数量  使用uid对该值取模
define( 'LOGIN_TAG', 10 );				#用户登录信息对应的redis端口数量  使用uid对该值取模
?>