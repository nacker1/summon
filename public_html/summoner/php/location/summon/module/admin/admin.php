<?php
/**
 *@ 物品发放接口
 **/
$type = $input['t'];
$nums = $input['n'];
$uid = $input['uid'];
$user=new User_User( $uid );

switch ($type) {
    case '1':
        # code...
        $hid = $input['hid'];
        if( empty( $hid ) ){
            $user->addExp( $nums );
        }else{
            $hero = new User_Hero( $user->getUid(), $hid );
            $hero->addHeroExp( $nums );
        }
        break;
    case '2':
        #添加vip
        if( $nums>15 || $nums<0 ){
            ret( 'vip 等级在  1 - 15级之间', -1 );
        }
        $user->setVip( $nums );
        break;
    case '3':
        #添加用户体力
        $user->addLife( $nums );
        ret($user->getUserLastUpdInfo());
    case '4':
        #添加用户周卡
        $n = isset($input['n']) ? $input['n'] : 3;
        switch( $n ){
            case '1':
                $user->setMonthCode();
                break;
            case '2':
                $user->setWeekCode();
                break;
            default:
                $user->setMonthCode();
                $user->setWeekCode();
        }
        ret( $user->getUserLastUpdInfo() );
        break;
    case '5':
        $level = $input['l'];
        $exp = $input['e'];
        #修改经验，等级，VIP等级
//        if( $exp>150 || $exp<0 ){
//            ret( '经验数值错误', -1 );
//        }
        $user->setExp( $exp );
//        if( $nums>15 || $nums<0 ){
//            ret( 'vip 等级在  1 - 15级之间', -1 );
//        }
        $user->setVip( $nums );
//        if( $level>150 || $level<0 ){
//            ret( '等级在  1 - 150级之间', -1 );
//        }
        $user->setLevel( $level );
        break;
    case '52': 												#设置顶级帐号
        #设置所有关卡进度
        $pros = array(
            910101,910102,910103,910104,910105,910106,910107,910108,910109,910110,910111,910112,910113,910114,910115,910116,910117,910118,910119,910120,
            910201,910202,910203,910204,910205,910206,910207,910208,910209,910210,910211,910212,910213,910214,910215,910216,910217,910218,910219,910220,
            910301,910302,910303,910304,910305,910306,910307,910308,910309,910310,910311,910312,910313,910314,910315,910316,910317,910318,910319,910320,
            910401,910402,910403,910404,910405,910406,910407,910408,910409,910410,910411,910412,910413,910414,910415,910416,
            910501,910502,910503,910504,910505,910506,910507,910508,910509,910510,910511,910512,910513,910514,910515,910516,
            910601,910602,910603,910604,910605,910606,910607,910608,910609,910610,910611,910612,910613,910614,910615,910616,
            910701,910702,910703,910704,910705,910706,910707,910708,910709,910710,910711,910712,910713,910714,910715,910716,
            910801,910802,910803,910804,910805,910806,910807,910808,910809,910810,910811,910812,910813,910814,910815,910816,
            910901,910902,910903,910904,910905,910906,910907,910908,910909,910910,910911,910912,910913,910914,910915,910916,
            911001,911002,911003,911004,911005,911006,911007,911008,911009,911010,911011,911012,911013,911014,911015,911016,
            920102,920104,920106,920108,920110,920113,920115,920117,920119,920120,920202,920204,920206,920208,920210,920212,920214,920216,920218,920220,
            920302,920304,920306,920308,920310,920312,920314,920316,920318,920320,920402,920404,920406,920408,920410,920412,920414,920416,920502,920504,
            920506,920508,920510,920512,920514,920516,920602,920604,920606,920608,920610,920612,920614,920616,920702,920704,920706,920708,920710,920712,
            920714,920716,920802,920804,920806,920808,920810,920812,920814,920816,920902,920904,920906,920908,920910,920912,920914,920916,921002,921004,
            921006,921008,921010,921012,921014,921016,930102,930106,930110,930115,930120,930204,930208,930212,930216,930220,930304,930308,930312,930316,
            930320,930404,930408,930412,930416,930504,930508,930512,930516,930604,930608,930612,930616,930704,930708,930712,930716,930804,930808,930812,
            930816,930904,930908,930912,930916,931004,931008,931012,931016
        );  #关卡id
        foreach( $pros as $v ){
            $pro = new User_Progress( $v, $user->getUid() );
            $pro->setUserProgress(3);
        }
        ret('suc');
        #添加召唤师经验
        $user->addExp( 1000000 );
        $user->addMoney( 100000000 );
        $user->addCooldou( 1000000 );

        #添加所有道具
        $gBase = new Goodbase();
        $gList = $gBase->getAllBaseGood();
        foreach( $gList as $v ){
            if( substr($v['Item_Id'],0,1) == 9 || substr($v['Item_Id'],0,1) == 1 )continue;
            $good = new User_Goods($user->getUid(), $v['Item_Id']);
            $good->addGoods(50);
        }
        #添加所有英雄
        $hList = $input['heros'];
        if( empty( $hList ) ){
            $hList = array(
                10001,10002,10003,10004,10005,
                10006,10007,10008,10009,10010,
                10011,10012,10013,10014,10015,
                10017,10018,10019,10020,
                10022,10023,10024,10025,
                10026,10027,10028,10029,10030,
                10031,10032,10033,10034,10036,10040,
            );
        }
        foreach ( $hList as $v ) {
            $hero = new User_Hero( $user->getUid(),$v );
            $hero->giveHero(4);
            #unset($hero);
        }
        $ret['hero'] = $hero->getLastUpdField();
        #=========== 任务信息 ==================
        $mis = $user->getMissionNotice();
        if( !empty( $mis ) ){
            $ret['mis'] = $mis;
        }
        #添加完后踢下线
        $user->setLoginTime();
        $user->setSkey();
        ret( 'suc' );
    case '500':#发送竞技场排名奖励
        $top = new Top( 4 );
        $top->sendPvpReward();
        ret( 'suc' );
    case '997': #踢下线
        $user->setLoginTime();
        $user->setSkey();
        ret( $user->getUserInfo() );
    case '998':
        $tag = $input['tag'];
        if( empty( $tag ) ){
            #清空所有配置缓存
            $cache = array(
                'baseDrawConfig:*','baseDrawTypeConfig:*','baseMissionConfig:*','shopConfig:*','action:sign:*','zy_baseArenaReward*',
                'baseBuffConfig:*','baseBuyGoldConfig:*','equip:baseinfo*','goodBase:base*','goodBase:equip*',
                'goodBase:compos*','heroSkillCost:*','heroBase:*','roleLevelUp*','vipConfig*','server:list:*','server:list_check','userLimit:*'
            );
        }else{
            $cache = $tag;
        }
        $user->clearConfig( $cache );
        ret('suc');
        break;
    case '999':
        #添加所有道具
        $gBase = new Goodbase();
        $gList = $gBase->getAllBaseGood();
        foreach( $gList as $v ){
            if( substr($v['Item_Id'],0,1) == 9 || substr($v['Item_Id'],0,1) == 1 )continue;
            $good = new User_Goods($user->getUid(), $v['Item_Id']);
            $good->addGoods(10);
        }
        ret('suc');
        break;
    case '1000':
        #添加所有英雄
        $hList = $input['heros'];
        if( empty( $hList ) ){
            $hList = array(
                10001,10002,10003,10004,10005,
                10006,10007,10008,10009,10010,
                10011,10012,10013,10014,10015,
                10017,10018,10019,10020,
                10022,10023,10024,10025,
                10026,10027,10028,10029,10030,
                10031,10032,10033,10034,10036,10040,
            );
        }
        foreach ( $hList as $v ) {
            $hero = new User_Hero( $user->getUid(),$v );
            $hero->giveHero(4);
            #unset($hero);
        }
        $ret['hero'] = $hero->getLastUpdField();
        #=========== 任务信息 ==================
        $mis = $user->getMissionNotice();
        if( !empty( $mis ) ){
            $ret['mis'] = $mis;
        }
        ret( $ret );

    case '1001': #发送邮件

        break;
    case '1002': #获取用户基本信息
        ret( $user->getUserInfo() );
        break;
    case '1003': #发送个人邮件

        $content = $input['content'];
        $type = $input['type'];
        $goods = $input['goods'];
        $title = $input['title'];
        $sendUser = $input['sendUser'];
        $time = $input['time'];

        $uMail = new User_Mail($uid);
        if( $uMail->sendMail($content,$type,$toUser,$title,$goods,$sendUser,$time)){
            ret( '发送成功——：'.$time,-1 );
        }else{
            ret( '操作失败，请重试！',-1 );
        }
        break;
    case '1004': #统计在线人数

        $id = $input['id'];
        global $log;
        $now = time();
        define('ISLOG',true);
        if( PHP_OS == 'Linux' )
            $include = '/data/web/summon/inc/inc.php';
        else
            $include = 'C:/wamp/www/summon/inc/inc.php';
        require_once $include;
        $log = new Logger('xync_userinfo','/data/web/summonAdmin/logs/sync/');
        $db =  Db_Mysql::init('slave');
        $tol_user_nums = 0;
        for($i=0;$i<10;$i++){
            $pre = Redis_Redis::initRedis($i);
            $allkeys = $pre->keys('roleinfo:*:baseinfo');

            $flag_user = 0;
            $flag_uc = 0;
            $start = time();
            foreach($allkeys as $v){
                $userinfo['heartTime'] = $pre->hget($v,'_heartTime');
                $userinfo['uid'] = substr($v,strpos($v,':')+1);
                if( empty($userinfo['uid']) ){
                    $log->e('用户uid取不到值，v:'.$v.',userinfo:'.json_encode($userinfo));
                    continue;
                }
                $beat = $userinfo['heartTime'];
                if( count($userinfo) < 20 ){
                    $abnormal[] = $userinfo['uid'];
                    continue;
                }

                if( ( time() - $beat ) < 300 ){
                    $tol_user_nums += 1;
                }
                unset($userinfo);
                unset($beat);
            }
            $end = time();
            $flag_user = 0;
            $flag_uc = 0;
        }
//server:服务器ID ，不同服务器该值不同
        $db->insert('zy_statsUsernum',array( 'num'=>$tol_user_nums , 'ts'=>$now,'server' =>$id));//统计在线人数
        ret( 'suc' );
        break;

    default:
        # code...
        phpinfo();
        ret( ' YMD '.__LINE__, -1 );
        break;
}

$ret = $user->getUserLastUpdInfo();
$mis = $user->getMissionNotice();
if( !empty( $mis ) ){
    $ret['mis'] = $mis;
}

if( isset( $hero ) && gettype( $hero ) == 'object' ){
    $ret['hero'] = $hero->getLastUpdField();
}

ret($ret);
?>