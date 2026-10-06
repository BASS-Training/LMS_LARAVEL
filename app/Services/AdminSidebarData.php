<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Refund;
use Illuminate\Support\Facades\Gate;

class AdminSidebarData
{
    public function pendingVerifications(): int
    {
        return Gate::allows('super-admin-only')
            ? Order::query()->where('status', 'awaiting_verification')->count()
            : 0;
    }

    public function pendingRefunds(): int
    {
        return Gate::allows('super-admin-only')
            ? Refund::query()->whereIn('status', ['requested', 'failed', 'approved', 'manual_required'])->count()
            : 0;
    }
}
