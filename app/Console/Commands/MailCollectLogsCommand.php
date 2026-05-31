<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MailCollectLogsCommand extends Command
{
    protected $signature = 'mail:collect-logs {--path= : Mail log path} {--limit=50000 : Max lines to parse}';
    protected $description = 'Collect postfix/docker mail logs into mail_events';

    public function handle(): int
    {
        if (!Schema::hasTable('mail_events')) {
            $this->error('mail_events table does not exist.');
            return self::FAILURE;
        }

        $paths = array_filter([
            $this->option('path'),
            env('MAIL_LOG_PATH'),
            'storage/logs/mailserver-docker.log',
            '/var/log/mail.log',
            '/var/log/mail.err',
            '/var/log/syslog',
        ]);

        $path = null;
        foreach ($paths as $candidate) {
            if ($candidate && is_readable($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if (!$path) {
            $this->warn('No readable mail log found.');
            return self::SUCCESS;
        }

        $limit = max(100, (int) $this->option('limit'));
        $lines = $this->tailFile($path, $limit);

        $columns = collect(Schema::getColumnListing('mail_events'))->flip();
        $matched = 0;

        foreach ($lines as $line) {
            $eventType = $this->detectEventType($line);
            if (!$eventType) {
                continue;
            }

            $eventTime = $this->parseEventTime($line);

            $sender = $this->match('/from=<([^>]*)>/', $line);
            $recipient = $this->match('/to=<([^>]*)>/', $line);
            $domain = $recipient && str_contains($recipient, '@') ? substr(strrchr($recipient, '@'), 1) : null;

            $queueId = $this->match('/postfix\/[^:]+\/?[^:]*:\s*([A-F0-9]{5,})[: ]/i', $line);
            $clientIp = $this->match('/\[(\d{1,3}(?:\.\d{1,3}){3})\]/', $line);
            $relay = $this->match('/relay=([^,\s]+)/', $line);
            $dsn = $this->match('/dsn=([^,\s]+)/', $line);
            $delay = $this->match('/delay=([^,\s]+)/', $line);
            $status = $this->match('/status=([a-zA-Z0-9_-]+)/', $line) ?: $eventType;
            $rawHash = hash('sha256', $line);

            $data = [
                'server_id' => null,
                'event_time' => $eventTime,
                'occurred_at' => $eventTime,
                'event_type' => $eventType,
                'queue_id' => $queueId,
                'message_id' => null,
                'sender' => $sender,
                'recipient' => $recipient,
                'recipient_domain' => $domain,
                'client_ip' => $clientIp,
                'relay' => $relay,
                'dsn' => $dsn,
                'delay' => $delay,
                'status' => $status,
                'message' => mb_substr($line, 0, 5000),
                'raw_line' => mb_substr($line, 0, 5000),
                'raw_hash' => $rawHash,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $data = array_filter(
                $data,
                fn ($value, $key) => $columns->has($key),
                ARRAY_FILTER_USE_BOTH
            );

            DB::table('mail_events')->updateOrInsert(
                ['raw_hash' => $rawHash],
                $data
            );

            $matched++;
        }

        $this->info("Parsed {$path}, matched {$matched} mail events.");
        return self::SUCCESS;
    }

    private function detectEventType(string $line): ?string
    {
        $l = strtolower($line);

        if (str_contains($l, 'status=sent')) {
            return 'sent';
        }

        if (str_contains($l, 'status=bounced') || str_contains($l, 'dsn=5.')) {
            return 'bounced';
        }

        if (str_contains($l, 'status=deferred') || str_contains($l, 'dsn=4.')) {
            return 'deferred';
        }

        if (str_contains($l, 'reject:') || str_contains($l, ' rejected ') || str_contains($l, 'noqueue: reject')) {
            return 'reject';
        }

        if (str_contains($l, 'warning:')) {
            return 'warning';
        }

        if (str_contains($l, ' connect from ') || str_contains($l, 'connect from')) {
            return 'connect';
        }

        return null;
    }

    private function parseEventTime(string $line): Carbon
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?[+-]\d{2}:\d{2})/', $line, $m)) {
            try {
                return Carbon::parse($m[1])->setTimezone(config('app.timezone'));
            } catch (\Throwable $e) {
                return now();
            }
        }

        return now();
    }

    private function match(string $pattern, string $line): ?string
    {
        return preg_match($pattern, $line, $m) ? ($m[1] ?? null) : null;
    }

    private function tailFile(string $path, int $limit): array
    {
        $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return [];
        }

        return array_slice($lines, -$limit);
    }
}
