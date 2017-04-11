#!/bin/bash  
#自动监控价格定时任务
pid=$$
name=`basename $0`
step=1 #间隔的秒数，不能大于60
ps -ef|awk -v p=$pid -v n=$name '$2!=p && $NF~n{system("kill "p)}'  #禁止重复运行

for (( i = 0; i < 3600; i=(i+step) )); do
	task_id=$(/usr/bin/php /www/wwwroot/monitor/public/index.php crond/cron/index) 
	for loop in $task_id           
	do 
		/usr/bin/php /www/wwwroot/monitor/public/index.php crond/task/index/id/$loop &
	done
sleep $step
done
exit 0
