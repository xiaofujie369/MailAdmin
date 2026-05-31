<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class DockerCommandService
{
    public function __construct(private readonly InputValidator $validator)
    {
    }

    public function logs(int $tail = 5000, ?string $since = null): string
    {
        $tail = max(100, min($tail, (int) config('mailadmin.log_tail_limit', 50000)));
        $args = ['docker', 'logs', '--timestamps', '--tail', (string) $tail];
        if ($since) {
            $args[] = '--since';
            $args[] = $since;
        }
        $args[] = $this->container();
        return $this->run($args, 45);
    }

    public function postqueue(): string
    {
        return $this->run(['docker', 'exec', $this->container(), 'postqueue', '-p'], 25);
    }

    public function postsuperDelete(string $queueId): string
    {
        $queueId = $this->validator->queueId($queueId);
        return $this->run(['docker', 'exec', $this->container(), 'postsuper', '-d', $queueId], 30);
    }

    public function postsuperDeleteAll(): string
    {
        return $this->run(['docker', 'exec', $this->container(), 'postsuper', '-d', 'ALL'], 45);
    }

    public function flushQueue(): string
    {
        return $this->run(['docker', 'exec', $this->container(), 'postqueue', '-f'], 35);
    }

    public function postconf(): string
    {
        return $this->run(['docker', 'exec', $this->container(), 'postconf', '-n'], 25);
    }

    public function rspamdConfigtest(): string
    {
        return $this->run(['docker', 'exec', $this->container(), 'rspamadm', 'configtest'], 25);
    }

    public function dockerPs(): string
    {
        return $this->run(['docker', 'ps', '--format', 'table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}'], 15);
    }

    private function run(array $args, int $timeout): string
    {
        foreach ($args as $arg) {
            if (! is_string($arg) || $arg === '') {
                throw new RuntimeException('命令参数不合法');
            }
        }
        $process = new Process($args);
        $process->setTimeout($timeout);
        $process->run();
        return trim($process->getOutput().$process->getErrorOutput());
    }

    private function container(): string
    {
        $container = (string) config('mailadmin.container', 'mailserver');
        if (! preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,127}$/', $container)) {
            throw new RuntimeException('邮件容器名不合法');
        }
        return $container;
    }
}
