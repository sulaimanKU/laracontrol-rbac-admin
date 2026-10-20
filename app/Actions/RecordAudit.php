<?php

namespace App\Actions;

use App\Models\AuditEvent;

class RecordAudit
{
    public static function write(string $action, ?int $subject, array $metadata = []): void
    {
        AuditEvent::create(['actor_id' => auth()->id(), 'subject_id' => $subject, 'action' => $action, 'metadata' => $metadata, 'created_at' => now()]);
    }
}
