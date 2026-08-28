@echo off
rem ============================================================
rem  GatewayWorker Windows 启停助手（单进程模式，非守护）
rem  Usage:  start_win.bat start | stop | status
rem  依赖:   系统 PATH 中可用的 php（PHP >= 8.1）
rem  注意:   Windows 下 Workerman 不支持 -d 守护与多进程，
rem          本脚本自动以单进程前台模式拉起三个服务进程。
rem ============================================================
setlocal
cd /d %~dp0..

if /i "%1"=="stop" goto stop
if /i "%1"=="status" goto status

php artisan ws:start
goto :eof

:stop
php artisan ws:stop
goto :eof

:status
if exist storage\app\websocket.pid (
  echo GatewayWorker pid file: storage\app\websocket.pid
  type storage\app\websocket.pid
) else (
  echo GatewayWorker is not running.
)
goto :eof
