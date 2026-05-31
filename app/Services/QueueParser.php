<?php

namespace App\Services;

class QueueParser
{
    public function parse(string $raw): array
    {
        $items = [];
        $blocks = preg_split("/\n\s*\n/", trim($raw)) ?: [];
        foreach ($blocks as $block) {
            $lines = array_values(array_filter(explode("\n", $block), fn ($line) => trim($line) !== ''));
            if (! $lines || ! preg_match('/^([A-Fa-f0-9]{5,20})([*!]?)\s+(\d+)\s+(.+?)\s+(\S+)\s*$/', $lines[0], $m)) {
                continue;
            }
            $recipients = [];
            $reason = '';
            foreach (array_slice($lines, 1) as $line) {
                $line = trim($line);
                if (str_starts_with($line, '(')) {
                    $reason = trim($line, '() ');
                } elseif (str_contains($line, '@')) {
                    $recipients[] = trim(strtok($line, ' '), '<>,');
                }
            }
            $recipient = $recipients[0] ?? '';
            $items[] = [
                'queue_id' => strtoupper($m[1]),
                'sender' => trim($m[5], '<>'),
                'recipient' => $recipient,
                'recipient_domain' => str_contains($recipient, '@') ? substr(strrchr($recipient, '@'), 1) : '',
                'size' => (int) $m[3],
                'queued_at' => null,
                'reason' => $reason,
                'raw_block' => $block,
            ];
        }
        return $items;
    }
}
