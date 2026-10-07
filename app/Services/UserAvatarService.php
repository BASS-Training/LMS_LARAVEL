<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UserAvatarService
{
    public function save(User $user, ?UploadedFile $avatar = null, bool $remove = false): void
    {
        $oldAvatar = $user->getOriginal('avatar');
        $newAvatar = null;

        if ($remove) {
            $user->avatar = null;
        } elseif ($avatar) {
            $newAvatar = $avatar->store('avatars', 'public');

            if (! $newAvatar) {
                throw new RuntimeException('Foto profil gagal disimpan.');
            }

            $user->avatar = $newAvatar;
        }

        try {
            $user->save();
        } catch (Throwable $exception) {
            if ($newAvatar) {
                Storage::disk('public')->delete($newAvatar);
            }

            throw $exception;
        }

        if (($remove || $newAvatar) && $oldAvatar && $oldAvatar !== $user->avatar) {
            Storage::disk('public')->delete($oldAvatar);
        }
    }
}
