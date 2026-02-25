<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        private readonly int $userId,
        private readonly array $userRoles,
        private readonly string $action,
        private readonly array $data,
    ) {}

    public function handle(): void
    {
        // Skip pure participants — same rule as ActivityLog::log()
        $roles = collect($this->userRoles);
        if ($roles->count() === 1 && $roles->contains('participant')) {
            return;
        }

        ActivityLog::create([
            'user_id'       => $this->userId,
            'action'        => $this->action,
            'file_name'     => $this->data['file_name'] ?? null,
            'file_path'     => $this->data['file_path'] ?? null,
            'file_type'     => $this->data['file_type'] ?? null,
            'file_size'     => $this->data['file_size'] ?? null,
            'ip_address'    => $this->data['ip_address'] ?? null,
            'user_agent'    => $this->data['user_agent'] ?? null,
            'description'   => $this->data['description'] ?? null,
            'metadata'      => $this->data['metadata'] ?? null,
            'status'        => $this->data['status'] ?? 'success',
            'error_message' => $this->data['error_message'] ?? null,
        ]);
    }
}