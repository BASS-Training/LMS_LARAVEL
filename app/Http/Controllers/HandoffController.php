<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\WebSessionApiController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Menukar tautan sekali-pakai dari mobile menjadi session web.
 *
 * Route sengaja TIDAK memakai middleware `auth` maupun `guest`:
 *  - tanpa `auth` karena pengguna memang belum login di browser,
 *  - tanpa `guest` agar session lama milik user lain tidak mem-bounce
 *    sebelum tautan sempat dipakai.
 */
class HandoffController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        // Cache::pull = baca + hapus dalam satu langkah → tautan hanya berlaku
        // sekali, meski ada dua request berbarengan hanya satu yang menang.
        $payload = Cache::pull(WebSessionApiController::cacheKey($token));

        if (! is_array($payload) || ! isset($payload['user_id'], $payload['target'])) {
            return $this->reject('Tautan tidak valid atau sudah kedaluwarsa. Silakan buka website dari aplikasi lagi.');
        }

        $target = (string) $payload['target'];

        // Pertahanan terakhir: tujuan hanya boleh path internal. Nilai ini
        // memang dibangun server, tapi tetap dicek sebelum redirect.
        if (! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return $this->reject('Tautan tidak valid.');
        }

        $user = User::find($payload['user_id']);

        if (! $user) {
            return $this->reject('Akun tidak ditemukan. Silakan login ulang dari aplikasi.');
        }

        // Urutan penting: buang identitas & data session lama SEBELUM login,
        // karena invalidate() menghapus seluruh isi session termasuk key login.
        $request->session()->invalidate();

        Auth::login($user, remember: false);

        // Token CSRF baru (session sebelumnya sudah di-flush), lalu tandai
        // tujuan asli agar pengguna kembali ke sini setelah verifikasi OTP.
        $request->session()->regenerateToken();
        $request->session()->put('url.intended', $target);

        return redirect()->to($target);
    }

    private function reject(string $message): RedirectResponse
    {
        return redirect()
            ->route('shop.index')
            ->withErrors(['shop' => $message]);
    }
}
