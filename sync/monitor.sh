#!/bin/bash
check_process()
{
        while [ 1 ]
        do
                count='ps -ef|grep "syncDb.php"|grep -v "grep"|wc -l'
                if [ $count -ge 1 ];then
                        echo "sleep 3s"
                        sleep 3
                else
                        #这里启动进程 eg: /usr/local/webserver/nginx/sbin/nginx
                        nohup php /data/web/summon/syncDb.php &
                        sleep 3
                fi
        done
}

check_process