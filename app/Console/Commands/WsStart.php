<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Process\InvokedProcess;
use Illuminate\Support\Facades\Process;

class WsStart extends Command
{
    protected $signature = 'ws:start {--d : Unix 下后台守护运行}';

    protected $description = '启动 GatewayWorker 实时服务（自动适配 Windows / Unix）';

    public function handle(): int
    {
        if ($this->isWindows()) {
            return $this->startWindows();
        }

        return $this->startUnix();
    }

    protected function startUnix(): int
    {
        $check = Process::run('ps aux | grep "websocket/start.php" | grep -v grep | head -3');

        if (trim($check->output()) !== '') {
            $this->warn('GatewayWorker 已在运行');

            return self::SUCCESS;
        }

        $daemon = $this->option('d') ? ' -d' : '';
        $cmd = sprintf(
            'cd %s && php websocket/start.php start%s > storage/logs/websocket.log 2>&1 &',
            base_path(),
            $daemon
        );

        Process::run($cmd);
        sleep(1);

        $this->info('已执行: php websocket/start.php start'.$daemon);
        $this->line('实时服务监听 ws://127.0.0.1:6001 ，日志: storage/logs/websocket.log');

        return self::SUCCESS;
    }

    protected function startWindows(): int
    {
        $pidFile = storage_path('app/websocket.pid');

        if (file_exists($pidFile) && $this->anyAliveWindows($this->readPids($pidFile))) {
            $this->warn('GatewayWorker 已在运行（pid 文件: '.$pidFile.'）');

            return self::SUCCESS;
        }

        $files = ['start_register.php', 'start_gateway.php', 'start_business.php'];
        $pids = [];

        foreach ($files as $file) {
            $process = $this->spawnWindows(base_path('websocket/'.$file));
            if ($process) {
                $pids[] = (string) $process->id();
                $this->line("已启动 {$file} (PID {$process->id()})");
            } else {
                $this->error("启动失败: {$file}");

                return self::FAILURE;
            }
        }

        file_put_contents($pidFile, implode(PHP_EOL, $pids));
        $this->info('GatewayWorker 已启动（Windows 单进程模式，无守护）');
        $this->line('监听 ws://127.0.0.1:6001 ，停止请运行: php artisan ws:stop');

        return self::SUCCESS;
    }

    /**
     * Windows 下异步拉起子进程（数组命令，避免 shell 转义问题）
     */
    protected function spawnWindows(string $startFile): ?InvokedProcess
    {
        $command = [PHP_BINARY, $startFile, 'start'];

        return Process::path(base_path())
            ->env(['APP_ENV' => config('app.env')])
            ->start($command);
    }

    protected function readPids(string $pidFile): array
    {
        return array_values(array_filter(array_map('trim', explode(PHP_EOL, (string) file_get_contents($pidFile)))));
    }

    protected function anyAliveWindows(array $pids): bool
    {
        foreach ($pids as $pid) {
            $result = Process::run(['tasklist', '/FI', "PID eq {$pid}", '/NH']);
            if (str_contains($result->output(), (string) $pid)) {
                return true;
            }
        }

        return false;
    }

    protected function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}
