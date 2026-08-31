<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * 停止 GatewayWorker 实时服务（ws:stop）
 * - Windows：读取 storage/app/websocket.pid 逐条 taskkill
 * - Unix：执行 websocket/start.php stop
 * 与 WsStart 配套，均以 Artisan 命令封装，避免直接手写原始 PHP 脚本
 */
class WsStop extends Command
{
    protected $signature = 'ws:stop';

    protected $description = '停止 GatewayWorker 实时服务（自动适配 Windows / Unix）';

    public function handle(): int
    {
        if ($this->isWindows()) {
            return $this->stopWindows();
        }

        Process::run(sprintf('cd %s && php websocket/start.php stop', base_path()));
        $this->info('GatewayWorker 已停止');

        return self::SUCCESS;
    }

    protected function stopWindows(): int
    {
        $pidFile = storage_path('app/websocket.pid');

        if (! file_exists($pidFile)) {
            $this->warn('未找到 pid 文件，可能未启动');

            return self::SUCCESS;
        }

        $pids = array_values(array_filter(array_map('trim', explode(PHP_EOL, (string) file_get_contents($pidFile)))));

        foreach ($pids as $pid) {
            $result = Process::run(['taskkill', '/F', '/PID', $pid]);
            if ($result->successful()) {
                $this->line("已停止 PID {$pid}");
            } else {
                $this->warn("PID {$pid} 停止失败或已退出");
            }
        }

        @unlink($pidFile);
        $this->info('GatewayWorker 已停止');

        return self::SUCCESS;
    }

    protected function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}
