<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class MailLogParser
{
    public function parseLine(string $line): ?array
    {
        if (! preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/', $line, $time)) {
            return null;
        }

        $eventType = 'unknown';
        $status = null;
        foreach (['sent', 'bounced', 'deferred'] as $candidate) {
            if (stripos($line, "status={$candidate}") !== false) {
                $eventType = $candidate;
                $status = $candidate;
            }
        }
        if (preg_match('/\bNOQUEUE: reject\b|\breject\b/i', $line)) {
            $eventType = 'reject';
            $status = 'reject';
        } elseif (preg_match('/\bwarning\b/i', $line)) {
            $eventType = 'warning';
        } elseif (preg_match('/\bconnect from\b/i', $line)) {
            $eventType = 'connect';
        } elseif (preg_match('/sasl|authentication|auth/i', $line)) {
            $eventType = 'auth';
        } elseif (preg_match('/rspamd|spam|greylist|rbl|blacklist/i', $line)) {
            $eventType = 'spam';
        }

        preg_match('/\b([A-Fa-f0-9]{5,20}):\s/', $line, $queue);
        preg_match('/from=<([^>]+)>/i', $line, $from);
        preg_match('/to=<([^>]+)>/i', $line, $to);
        preg_match('/client=.*?\[([0-9a-f:.]+)\]|connect from .*?\[([0-9a-f:.]+)\]/i', $line, $ip);
        preg_match('/relay=([^,\s]+)/i', $line, $relay);
        preg_match('/dsn=([^,\s]+)/i', $line, $dsn);
        preg_match('/delay=([^,\s]+)/i', $line, $delay);

        $recipient = strtolower($to[1] ?? '');

        return [
            'event_time' => CarbonImmutable::parse($time[1].' '.$time[2]),
            'queue_id' => strtoupper($queue[1] ?? ''),
            'event_type' => $eventType,
            'status' => $status,
            'sender' => strtolower($from[1] ?? ''),
            'recipient' => $recipient,
            'recipient_domain' => str_contains($recipient, '@') ? substr(strrchr($recipient, '@'), 1) : '',
            'client_ip' => $ip[1] ?? $ip[2] ?? '',
            'relay' => $relay[1] ?? '',
            'dsn' => $dsn[1] ?? '',
            'delay' => $delay[1] ?? '',
            'message' => mb_substr($line, 0, 1000),
            'raw_line' => $line,
        ];
    }
}
