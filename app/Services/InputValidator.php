<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class InputValidator
{
    public function queueId(string $value, bool $allowAll = false): string
    {
        $value = strtoupper(trim($value));
        if ($allowAll && $value === 'ALL') {
            return $value;
        }
        if (! preg_match('/^[A-Fa-f0-9]{5,20}$/', $value)) {
            throw ValidationException::withMessages(['queue_id' => '队列 ID 不合法']);
        }
        return $value;
    }

    public function domain(string $value): string
    {
        $value = strtolower(rtrim(trim($value), '.'));
        if (! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/i', $value)) {
            throw ValidationException::withMessages(['domain' => '域名不合法']);
        }
        return $value;
    }

    public function listValue(string $type, string $value): string
    {
        $value = trim($value);
        return match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? strtolower($value) : throw ValidationException::withMessages(['value' => '邮箱不合法']),
            'domain' => $this->domain($value),
            'ip' => filter_var($value, FILTER_VALIDATE_IP) ? $value : throw ValidationException::withMessages(['value' => 'IP 不合法']),
            'cidr' => preg_match('/^([0-9a-f:.]+)\/([0-9]{1,3})$/i', $value) ? $value : throw ValidationException::withMessages(['value' => 'CIDR 不合法']),
            default => throw ValidationException::withMessages(['type' => '类型不合法']),
        };
    }
}
