<?php

namespace App\Services\Payment;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\CouponSetting;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use RuntimeException;

class CouponService
{
    public function serverEnabled(): bool
    {
        return (bool) config('shop.coupons_feature_enabled', false);
    }

    public function checkoutEnabled(): bool
    {
        return $this->serverEnabled() && CouponSetting::current()->checkout_enabled;
    }

    public function normalize(?string $code): ?string
    {
        $normalized = mb_strtoupper(trim((string) $code));

        return $normalized !== '' ? $normalized : null;
    }

    /** @return array{coupon:Coupon, code:string, discount:int, original_base:int, discounted_base:int} */
    public function quote(string $code, int $amount, Course $course, User $user, ?Order $existingOrder = null): array
    {
        return $this->resolve($code, $amount, $course, $user, $existingOrder, false);
    }

    /** @return array{coupon:Coupon, code:string, discount:int, original_base:int, discounted_base:int} */
    public function quoteForReservation(string $code, int $amount, Course $course, User $user): array
    {
        return $this->resolve($code, $amount, $course, $user, null, true);
    }

    /** @param array{coupon:Coupon, discount:int} $quote */
    public function createRedemption(array $quote, Order $order, User $user): CouponRedemption
    {
        return CouponRedemption::create([
            'coupon_id' => $quote['coupon']->id,
            'order_id' => $order->id,
            'user_id' => $user->id,
            'discount_amount' => $quote['discount'],
            'redeemed_at' => now(),
        ]);
    }

    public function releaseForOrder(Order $order): void
    {
        $order->couponRedemption()->delete();
    }

    /**
     * @return array{coupon:Coupon, code:string, discount:int, original_base:int, discounted_base:int}
     */
    private function resolve(
        string $code,
        int $amount,
        ?Course $course,
        User $user,
        ?Order $existingOrder,
        bool $lock,
    ): array {
        if (! $this->checkoutEnabled()) {
            throw new RuntimeException('Fitur kupon sedang tidak tersedia.');
        }

        $normalized = $this->normalize($code);
        if (! $normalized) {
            throw new RuntimeException('Masukkan kode kupon terlebih dahulu.');
        }

        $query = Coupon::query()->where('code', $normalized);
        if ($lock) {
            $query->lockForUpdate();
        }

        $coupon = $query->first();
        if (! $coupon) {
            throw new RuntimeException('Kode kupon tidak valid atau sudah tidak aktif.');
        }

        $existingRedemption = $existingOrder?->couponRedemption()
            ->where('coupon_id', $coupon->id)
            ->first();

        // Harga order payable sudah menjadi snapshot. Perubahan periode/status
        // kupon tidak boleh mengubah tagihan yang sudah terbit.
        if ($existingRedemption && $existingOrder?->isPayable()) {
            return [
                'coupon' => $coupon,
                'code' => $coupon->code,
                'discount' => $existingRedemption->discount_amount,
                'original_base' => $amount,
                'discounted_base' => $amount - $existingRedemption->discount_amount,
            ];
        }

        if (! $coupon->is_active) {
            throw new RuntimeException('Kode kupon tidak valid atau sudah tidak aktif.');
        }

        if ($coupon->starts_at?->isFuture()) {
            throw new RuntimeException('Kupon ini belum memasuki periode penggunaan.');
        }

        if ($coupon->expires_at?->isPast()) {
            throw new RuntimeException('Kupon ini sudah kedaluwarsa.');
        }

        if (! $coupon->applies_to_all_courses) {
            if (! $course || ! $coupon->courses()->whereKey($course->id)->exists()) {
                throw new RuntimeException('Kupon ini tidak berlaku untuk kursus yang dipilih.');
            }
        }

        if ($coupon->minimum_amount !== null && $amount < $coupon->minimum_amount) {
            throw new RuntimeException(
                'Minimum pembelian untuk kupon ini adalah Rp '.number_format($coupon->minimum_amount, 0, ',', '.').'.'
            );
        }

        $redemptions = $coupon->redemptions();
        $userRedemptions = $coupon->redemptions()->where('user_id', $user->id);

        if ($existingRedemption) {
            $redemptions->whereKeyNot($existingRedemption->id);
            $userRedemptions->whereKeyNot($existingRedemption->id);
        }

        if ($coupon->usage_limit !== null && $redemptions->count() >= $coupon->usage_limit) {
            throw new RuntimeException('Kuota penggunaan kupon ini sudah habis.');
        }

        if ($coupon->per_user_limit !== null && $userRedemptions->count() >= $coupon->per_user_limit) {
            throw new RuntimeException('Anda sudah mencapai batas penggunaan kupon ini.');
        }

        $discount = $coupon->discount_type === Coupon::TYPE_PERCENTAGE
            ? (int) ceil($amount * $coupon->discount_value / 100)
            : min($amount, $coupon->discount_value);
        $discountedBase = $amount - $discount;

        if ($discount <= 0 || $discountedBase <= 0) {
            throw new RuntimeException('Kupon tidak berlaku untuk transaksi ini karena total harus lebih dari Rp0.');
        }

        return [
            'coupon' => $coupon,
            'code' => $coupon->code,
            'discount' => $discount,
            'original_base' => $amount,
            'discounted_base' => $discountedBase,
        ];
    }
}
