#!/bin/bash

# 杀死进程
pgrep -f "real-open" | xargs kill -9
pgrep -f "queue:work" | xargs kill -9
pgrep -f "forecast-bets" | xargs kill -9
pgrep -f "zan-money" | xargs kill -9
pgrep -f "index-guess" | xargs kill -9

# 重新启动服务
nohup php artisan module:real-open > /dev/null 2>&1 &
nohup php artisan module:real-open-v > /dev/null 2>&1 &
nohup php artisan module:forecast-bets-one > /dev/null 2>&1 &
nohup php artisan module:forecast-bets-two > /dev/null 2>&1 &
nohup php artisan module:forecast-bets-three > /dev/null 2>&1 &
nohup php artisan module:forecast-bets-four > /dev/null 2>&1 &
nohup php artisan module:forecast-bets-five > /dev/null 2>&1 &
nohup php artisan module:forecast-bets-six > /dev/null 2>&1 &
nohup php artisan module:forecast-bets-seven > /dev/null 2>&1 &
nohup php artisan module:index-guess > /dev/null 2>&1 &
nohup php artisan queue:work > /dev/null 2>&1 &
