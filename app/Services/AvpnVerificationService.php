<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AvpnVerificationStatusNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AvpnVerificationService
{
    /**
     * @param  iterable<int>  $userIds
     * @return Collection<int, User>
     */
    public function transitionPending(iterable $userIds, string $status, User $admin, ?string $reason = null): Collection
    {
        if (! in_array($status, [
            AvpnVerificationStatusNotification::APPROVED,
            AvpnVerificationStatusNotification::REJECTED,
        ], true)) {
            throw new InvalidArgumentException('Status verifikasi AVPN tidak valid.');
        }

        return DB::transaction(function () use ($userIds, $status, $admin, $reason) {
            $users = User::query()
                ->whereIn('id', collect($userIds)->unique()->values())
                ->where('avpn_verification_status', 'pending')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($users as $user) {
                $user->update([
                    'avpn_verification_status' => $status,
                    'avpn_verified_at' => now(),
                    'avpn_verified_by' => $admin->id,
                    'avpn_rejection_reason' => $status === AvpnVerificationStatusNotification::REJECTED ? $reason : null,
                ]);
                $user->notify(new AvpnVerificationStatusNotification($status, $reason));
            }

            return $users;
        });
    }
}
