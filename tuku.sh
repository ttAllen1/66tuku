#!/bin/bash
if [[ $1 == '-h' || $1 == '--help' || $1 == '-help' || $1 == 'help' ]]; then
	echo -e "\033[42;37m ############# 帮助 ############# \033[0m"
	echo 'tuku.sh forever --进程守护'
	echo 'tuku.sh ffmpeg --ffmpeg安装'
elif [[ $1 == 'forever' ]]; then
	######## 命令配置 ########
	sh[0]="module:video -u -l 5" #澳门视频采集
	sh[1]="module:video -u -l 1" #港彩视频采集
	sh[2]="module:video -u -l 2" #新澳门视频采集
	sh[3]="module:corpus --corpusUpdate2" #77采集资料更新 zlapi8平台
	sh[4]="module:corpus --corpusUpdate3" #77采集资料更新 49j平台
	sh[5]="module:diagram -d" #图解采集49
	sh[6]="module:fiveVirtual" #虚拟人数
	sh[7]="module:real-open" #实时开奖
	sh[8]="module:real-open-v" #实时开奖
	sh[9]="queue:work" #实时开奖
#	sh[9]="queue:work redis --queue=queue_find_recharge1 --timeout=900" #五福列队
#	sh[10]="queue:work redis --queue=queue_find_recharge2 --timeout=900" #五福列队
#	sh[11]="queue:work redis --queue=queue_find_recharge3 --timeout=900" #五福列队
#	sh[12]="queue:work redis --queue=queue_find_recharge4 --timeout=900" #五福列队
#	sh[13]="queue:work redis --queue=queue_find_recharge5 --timeout=900" #五福列队
#	sh[14]="queue:work redis --queue=queue_find_recharge6 --timeout=900" #五福列队
#	sh[15]="queue:work redis --queue=queue_find_recharge7 --timeout=900" #五福列队
#	sh[16]="queue:work redis --queue=queue_find_recharge8 --timeout=900" #五福列队
#	sh[17]="queue:work redis --queue=queue_find_recharge9 --timeout=900" #五福列队
#	sh[18]="queue:work redis --queue=queue_find_recharge10 --timeout=900" #五福列队
	sh[10]="module:hklive" #香港直播视频链接采集
	sh[11]="module:video -u -l 6" #28视频采集
	sh[12]="module:video -u -l 7" #老澳视频采集
	sh[13]="module:checkTransfer" #ky转入转出错误订单处理

	basedir=$(dirname $(readlink -f "$0"))

	for item in "${sh[@]}"; do
		count=`ps -ef | grep "$item" | grep -v "grep" | wc -l`
		if [[ 0 == $count ]]; then
			nohup php $basedir/artisan $item &
			echo "$item 进程启动"
		fi
	done
elif [[ $1 == 'ffmpeg' ]]; then
	rm -rf /tmp/ffmpeg
	cd /tmp
	mkdir ffmpeg
	cd ffmpeg
	### 安装yasm ###
	wget http://www.tortall.net/projects/yasm/releases/yasm-1.3.0.tar.gz
	tar -zxvf yasm-1.3.0.tar.gz
	cd yasm-1.3.0
	./configure
	make && make install
	### 安装ffmpeg ###
	cd /tmp/ffmpeg
	wget http://www.ffmpeg.org/releases/ffmpeg-6.0.tar.gz
	tar -zxvf ffmpeg-6.0.tar.gz
	cd ffmpeg-6.0
	./configure --prefix=/usr/local/ffmpeg
	make && make install
	sed -i '$a export PATH=$PATH:/usr/local/ffmpeg/bin'  /etc/profile
	source /etc/profile
	rm -rf /tmp/ffmpeg
	echo ' Ffmpeg Installation completed！'
fi
