<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CouponRequest;
use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\CouponSetting;
use App\Models\Course;
use App\Services\Payment\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    public function __construct(private CouponService $coupons) {}

    public function index(Request $request)
    {
        $search = $request->string('search')->trim()->toString();
        $query = Coupon::query()->withCount('redemptions')->latest();

        if ($search !== '') {
            $query->where('code', 'like', "%{$search}%");
        }

        return view('admin.coupons.index', [
            'coupons' => $query->paginate(15)->withQueryString(),
            'search' => $search,
            'settings' => CouponSetting::current(),
            'serverEnabled' => $this->coupons->serverEnabled(),
        ]);
    }

    public function create()
    {
        return view('admin.coupons.create', [
            'coupon' => new Coupon([
                'is_active' => true,
                'applies_to_all_courses' => true,
                'per_user_limit' => 1,
            ]),
            'courses' => $this->courses(),
        ]);
    }

    public function store(CouponRequest $request)
    {
        $coupon = Coupon::create($request->safe()->except('course_ids'));
        $this->syncCourses($coupon, $request);
        $this->log('coupon_created', $coupon, 'Membuat kupon '.$coupon->code);

        return redirect()->route('admin.coupons.index')->with('success', 'Kupon berhasil dibuat.');
    }

    public function edit(Coupon $coupon)
    {
        $coupon->load('courses:id');

        return view('admin.coupons.edit', [
            'coupon' => $coupon,
            'courses' => $this->courses(),
        ]);
    }

    public function update(CouponRequest $request, Coupon $coupon)
    {
        $before = $coupon->only($coupon->getFillable());
        $coupon->update($request->safe()->except('course_ids'));
        $this->syncCourses($coupon, $request);
        $this->log('coupon_updated', $coupon, 'Memperbarui kupon '.$coupon->code, ['before' => $before]);

        return redirect()->route('admin.coupons.index')->with('success', 'Kupon berhasil diperbarui.');
    }

    public function destroy(Coupon $coupon)
    {
        if ($coupon->redemptions()->exists()) {
            return back()->withErrors(['coupon' => 'Kupon yang sudah digunakan tidak dapat dihapus. Nonaktifkan kupon sebagai gantinya.']);
        }

        $code = $coupon->code;
        $coupon->delete();
        $this->log('coupon_deleted', $coupon, 'Menghapus kupon '.$code);

        return back()->with('success', 'Kupon berhasil dihapus.');
    }

    public function updateCheckout(Request $request)
    {
        $validated = $request->validate([
            'checkout_enabled' => ['required', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->coupons->serverEnabled() && $validated['checkout_enabled']) {
            return back()->withErrors(['coupon' => 'Kill switch server masih nonaktif. Aktifkan COUPONS_FEATURE_ENABLED terlebih dahulu.']);
        }

        $settings = CouponSetting::current();
        $before = $settings->checkout_enabled;
        $settings->update([
            'checkout_enabled' => $validated['checkout_enabled'],
            'updated_by' => Auth::id(),
        ]);

        ActivityLog::log('coupon_checkout_toggled', [
            'description' => $validated['checkout_enabled'] ? 'Mengaktifkan kupon di checkout' : 'Menonaktifkan kupon di checkout',
            'metadata' => [
                'before' => $before,
                'after' => (bool) $validated['checkout_enabled'],
                'reason' => $validated['reason'] ?? null,
            ],
        ]);

        return back()->with('success', $validated['checkout_enabled']
            ? 'Kupon sekarang aktif di checkout.'
            : 'Kupon telah dinonaktifkan dari checkout.');
    }

    private function courses()
    {
        return Course::query()->where('price', '>', 0)->orderBy('title')->get(['id', 'title']);
    }

    private function syncCourses(Coupon $coupon, CouponRequest $request): void
    {
        $coupon->courses()->sync($coupon->applies_to_all_courses ? [] : $request->input('course_ids', []));
    }

    private function log(string $action, Coupon $coupon, string $description, array $extra = []): void
    {
        ActivityLog::log($action, [
            'description' => $description,
            'metadata' => ['coupon_id' => $coupon->id, 'code' => $coupon->code] + $extra,
        ]);
    }
}
