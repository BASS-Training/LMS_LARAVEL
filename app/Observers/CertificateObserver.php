<?php

namespace App\Observers;

use App\Models\Certificate;
use App\Notifications\CertificateIssuedNotification;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class CertificateObserver implements ShouldHandleEventsAfterCommit
{
    public function updated(Certificate $certificate): void
    {
        if (! $certificate->wasChanged('path') || ! $certificate->path || $certificate->getOriginal('path')) {
            return;
        }

        $claimed = Certificate::query()
            ->whereKey($certificate->id)
            ->whereNull('issued_email_queued_at')
            ->update(['issued_email_queued_at' => now()]);

        if ($claimed === 1 && $certificate->user) {
            $certificate->loadMissing('course');
            $certificate->user->notify(new CertificateIssuedNotification($certificate));
        }
    }
}
