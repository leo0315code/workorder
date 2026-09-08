<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Process\InvokedProcess;
use Illuminate\Support\Facades\Process;

/**
 * 队列 worker 启动/停止（适配 Unix / Windows）：
 * - Unix：后台守护 php artisan queue:work（Redis 连接）
 * - Windows：启动独立进程，PID 记入 storage/app/queue.pid
 *
 * 用法：php artisan ws:queue start|stop|status
 * 说明：通知邮件已队列化（SendNotificationEmailJob），生产环境必须常驻该 worker，
 *       否则邮件任务会积压在队列中不被消费。
 */
class WsQueue extends Command
{
    protected $signature = 'ws:queue {action=start : start | stop | status}';

    protected $description = '启动/停止队列 worker（通知邮件消费，Unix 守护 / Windows 兼容）';

    protected const PID_FILE = 'app/queue.pid';

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'stop' => $this->stop(),
            'status' => $this->status(),
            default => $this->start(),
        };
    }

    protected function start(): int
    {
        if ($this->isRunning()) {
            $this->warn('队列 worker 已在运行');

            return self::SUCCESS;
        }

        if ($this->isWindows()) {
            return $this->startWindows();
        }

        $cmd = sprintf(
            'cd %s && nohup php artisan queue:work --sleep=3 --tries=3 --max-time=3600 >> storage/logs/queue-worker.log 2>&1 &',
            base_path()
        );

        Process::run($cmd);
        sleep(1);

        if (! $this->isRunning()) {
            $this->error('队列 worker 启动失败，请检查 storage/logs/queue-worker.log');

            return self::FAILURE;
        }

        $this->info('队列 worker 已启动（后台守护）');
        $this->line('消费 Redis 队列的邮件通知任务，日志: storage/logs/queue-worker.log');
        $this->line('停止: php artisan ws:queue stop');

        return self::SUCCESS;
    }

    protected function startWindows(): int
    {
        $process = $this->spawnWindows();
        if (! $process) {
            $this->error('队列 worker 启动失败');

            return self::FAILURE;
        }

        file_put_contents(storage_path(self::PID_FILE), (string) $process->id());
        $this->info("队列 worker 已启动（Windows 进程 PID {$process->id()}）");
        $this->line('停止: php artisan ws:queue stop');

        return self::SUCCESS;
    }

    protected function stop(): int
    {
        if ($this->isWindows()) {
            $pids = $this->readPids();
            foreach ($pids as $pid) {
                Process::run(['taskkill', '/PID', $pid, '/F']);
            }
            @unlink(storage_path(self::PID_FILE));
            $this->info('队列 worker 已停止');

            return self::SUCCESS;
        }

        Process::run('pkill -f "artisan queue:work"');
        sleep(1);
        $this->info('队列 worker 已停止');

        return self::SUCCESS;
    }

    protected function status(): int
    {
        $this->line($this->isRunning() ? '队列 worker：运行中' : '队列 worker：未运行');

        return self::SUCCESS;
    }

    protected function isRunning(): bool
    {
        if ($this->isWindows()) {
            foreach ($this->readPids() as $pid) {
                $result = Process::run(['tasklist', '/FI', "PID eq {$pid}", '/NH']);
                if (str_contains($result->output(), (string) $pid)) {
                    return true;
                }
            }

            return false;
        }

        $result = Process::run('pgrep -f "artisan queue:work" | head -1');

        return trim($result->output()) !== '';
    }

    /**
     * Windows 下异步拉起子进程（数组命令，避免 shell 转义问题）
     */
    protected function spawnWindows(): ?InvokedProcess
    {
        $command = [PHP_BINARY, base_path('artisan'), 'queue:work', '--sleep=3', '--tries=3'];

        return Process::path(base_path())
            ->env(['APP_ENV' => config('app.env')])
            ->start($command);
    }

    protected function readPids(): array
    {
        $pidFile = storage_path(self::PID_FILE);
        if (! file_exists($pidFile)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(PHP_EOL, (string) file_get_contents($pidFile)))));
    }

    protected function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\';
    }
}
