<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    public function write(string $action, ?string $targetType = null, ?string $targetId = null, array $payload = [], ?Request $request = null): void
    {
        $request ??= request();
        AuditLog::create([
            'user_id' => optional($request->user())->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
