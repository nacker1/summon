#!/bin/bash

check_process()
{
        while [ 1 ]
        do
                count=`ps -ef|grep "processname"|grep -v "grep"|wc -l`
                if [ $count -ge 1 ];then
                        echo "sleep 2s"
                        sleep 2
                else
                        #这里启动进程 eg: /usr/local/webserver/nginx/sbin/nginx
                        nohup php /data/web/summon/syncDb.php &
                        sleep 1
                fi
        done
}

check_process