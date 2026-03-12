<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParticipantController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $query = User::permission('attempt quizzes');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('institution_name', 'like', "%{$search}%")
                  ->orWhere('occupation', 'like', "%{$search}%");
            });
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $query->where('gender', $request->gender);
        }

        // Filter by institution
        if ($request->filled('institution')) {
            $query->where('institution_name', $request->institution);
        }

        if ($request->filled('registration_program')) {
            $query->where('registration_program', $request->registration_program);
        }

        if ($request->filled('avpn_status')) {
            $query->where('avpn_verification_status', $request->avpn_status);
        }

        $participants = $query->orderBy('created_at', 'desc')->paginate(20);

        // Get unique institutions for filter
        $institutions = User::permission('attempt quizzes')
            ->whereNotNull('institution_name')
            ->distinct()
            ->pluck('institution_name')
            ->sort();

        return view('admin.participants.index', compact('participants', 'institutions'));
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);

        // Get enrolled courses with progress
        $enrolledCourses = $user->enrolledCourses()
            ->with(['lessons.contents.quiz.questions'])
            ->get()
            ->map(function ($course) use ($user) {
                $progress = $user->getProgressForCourse($course);
                return [
                    'course' => $course,
                    'progress' => $progress
                ];
            });

        return view('admin.participants.show', compact('user', 'enrolledCourses'));
    }

    public function approveAvpn(User $user)
    {
        $this->authorize('viewAny', User::class);

        if ($user->avpn_verification_status !== 'pending') {
            return back()->with('error', 'User ini tidak sedang menunggu verifikasi AVPN.');
        }

        $user->update([
            'avpn_verification_status' => 'approved',
            'avpn_verified_at' => now(),
            'avpn_verified_by' => auth()->id(),
            'avpn_rejection_reason' => null,
        ]);

        \App\Models\ActivityLog::log('avpn_registration_approved', [
            'description' => "Approved AVPN verification for user: {$user->name}",
            'metadata' => [
                'participant_id' => $user->id,
                'participant_name' => $user->name,
                'participant_email' => $user->email,
                'approved_by' => auth()->id(),
            ],
        ]);

        return back()->with('success', "Validasi AVPN untuk {$user->name} berhasil disetujui.");
    }

    public function rejectAvpn(Request $request, User $user)
    {
        $this->authorize('viewAny', User::class);

        if ($user->avpn_verification_status !== 'pending') {
            return back()->with('error', 'User ini tidak sedang menunggu verifikasi AVPN.');
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $user->update([
            'avpn_verification_status' => 'rejected',
            'avpn_verified_at' => now(),
            'avpn_verified_by' => auth()->id(),
            'avpn_rejection_reason' => $validated['reason'] ?? null,
        ]);

        \App\Models\ActivityLog::log('avpn_registration_rejected', [
            'description' => "Rejected AVPN verification for user: {$user->name}",
            'metadata' => [
                'participant_id' => $user->id,
                'participant_name' => $user->name,
                'participant_email' => $user->email,
                'rejected_by' => auth()->id(),
                'reason' => $validated['reason'] ?? null,
            ],
        ]);

        return back()->with('success', "Validasi AVPN untuk {$user->name} berhasil ditolak.");
    }

    public function forceAccess(Request $request, User $user)
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'access_mode' => 'required|in:regular_only,avpn_allowed,avpn_blocked',
            'reason' => 'nullable|string|max:500',
        ]);

        $mode = $validated['access_mode'];

        if ($mode === 'avpn_allowed') {
            $user->update([
                'avpn_verification_status' => 'approved',
                'avpn_google_form_submitted_at' => $user->avpn_google_form_submitted_at ?? now(),
                'avpn_verified_at' => now(),
                'avpn_verified_by' => auth()->id(),
                'avpn_rejection_reason' => null,
            ]);

            \App\Models\ActivityLog::log('avpn_access_force_granted', [
                'description' => "Force granted AVPN access for user: {$user->name}",
                'metadata' => [
                    'participant_id' => $user->id,
                    'participant_name' => $user->name,
                    'participant_email' => $user->email,
                    'forced_by' => auth()->id(),
                ],
            ]);

            return back()->with('success', "Akses AVPN untuk {$user->name} berhasil diaktifkan secara paksa.");
        }

        if ($mode === 'avpn_blocked') {
            $reason = $validated['reason'] ?? 'Akses AVPN diblokir secara paksa oleh admin.';

            $user->update([
                'avpn_verification_status' => 'rejected',
                'avpn_verified_at' => now(),
                'avpn_verified_by' => auth()->id(),
                'avpn_rejection_reason' => $reason,
            ]);

            \App\Models\ActivityLog::log('avpn_access_force_blocked', [
                'description' => "Force blocked AVPN access for user: {$user->name}",
                'metadata' => [
                    'participant_id' => $user->id,
                    'participant_name' => $user->name,
                    'participant_email' => $user->email,
                    'forced_by' => auth()->id(),
                    'reason' => $reason,
                ],
            ]);

            return back()->with('success', "Akses AVPN untuk {$user->name} berhasil diblokir secara paksa.");
        }

        $user->update([
            'avpn_verification_status' => 'not_required',
            'avpn_verified_at' => null,
            'avpn_verified_by' => null,
            'avpn_rejection_reason' => null,
        ]);

        \App\Models\ActivityLog::log('avpn_access_force_reset', [
            'description' => "Force reset AVPN access to regular for user: {$user->name}",
            'metadata' => [
                'participant_id' => $user->id,
                'participant_name' => $user->name,
                'participant_email' => $user->email,
                'forced_by' => auth()->id(),
            ],
        ]);

        return back()->with('success', "Akses AVPN untuk {$user->name} diubah ke reguler (not required).");
    }

    public function batchApproveAvpn(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'integer|exists:users,id',
        ]);

        $ids = collect($validated['participant_ids'])->unique()->values();

        $pendingUsers = User::query()
            ->whereIn('id', $ids)
            ->where('avpn_verification_status', 'pending')
            ->get();

        if ($pendingUsers->isEmpty()) {
            return back()->with('error', 'Tidak ada peserta berstatus pending yang dapat di-approve.');
        }

        User::query()
            ->whereIn('id', $pendingUsers->pluck('id'))
            ->update([
                'avpn_verification_status' => 'approved',
                'avpn_verified_at' => now(),
                'avpn_verified_by' => auth()->id(),
                'avpn_rejection_reason' => null,
            ]);

        \App\Models\ActivityLog::log('avpn_registration_batch_approved', [
            'description' => 'Batch approved AVPN verification.',
            'metadata' => [
                'participant_ids' => $pendingUsers->pluck('id')->toArray(),
                'participant_emails' => $pendingUsers->pluck('email')->toArray(),
                'approved_count' => $pendingUsers->count(),
                'approved_by' => auth()->id(),
            ],
        ]);

        $skipped = $ids->count() - $pendingUsers->count();
        $message = "Berhasil approve batch AVPN untuk {$pendingUsers->count()} peserta.";
        if ($skipped > 0) {
            $message .= " {$skipped} peserta dilewati karena status bukan pending.";
        }

        return back()->with('success', $message);
    }

    public function batchRejectAvpn(Request $request)
    {
        $this->authorize('viewAny', User::class);

        $validated = $request->validate([
            'participant_ids' => 'required|array|min:1',
            'participant_ids.*' => 'integer|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        $ids = collect($validated['participant_ids'])->unique()->values();

        $pendingUsers = User::query()
            ->whereIn('id', $ids)
            ->where('avpn_verification_status', 'pending')
            ->get();

        if ($pendingUsers->isEmpty()) {
            return back()->with('error', 'Tidak ada peserta berstatus pending yang dapat di-reject.');
        }

        $reason = $validated['reason'] ?? 'Ditolak batch oleh admin.';

        User::query()
            ->whereIn('id', $pendingUsers->pluck('id'))
            ->update([
                'avpn_verification_status' => 'rejected',
                'avpn_verified_at' => now(),
                'avpn_verified_by' => auth()->id(),
                'avpn_rejection_reason' => $reason,
            ]);

        \App\Models\ActivityLog::log('avpn_registration_batch_rejected', [
            'description' => 'Batch rejected AVPN verification.',
            'metadata' => [
                'participant_ids' => $pendingUsers->pluck('id')->toArray(),
                'participant_emails' => $pendingUsers->pluck('email')->toArray(),
                'rejected_count' => $pendingUsers->count(),
                'reason' => $reason,
                'rejected_by' => auth()->id(),
            ],
        ]);

        $skipped = $ids->count() - $pendingUsers->count();
        $message = "Berhasil reject batch AVPN untuk {$pendingUsers->count()} peserta.";
        if ($skipped > 0) {
            $message .= " {$skipped} peserta dilewati karena status bukan pending.";
        }

        return back()->with('success', $message);
    }

    public function syncLegacyAvpnParticipants()
    {
        $this->authorize('viewAny', User::class);

        $legacyAvpnFromCourses = DB::table('course_user as cu')
            ->join('courses as c', 'c.id', '=', 'cu.course_id')
            ->where('c.program_type', 'avpn_ai')
            ->pluck('cu.user_id');

        $legacyAvpnFromClasses = DB::table('course_class_user as ccu')
            ->join('course_classes as cc', 'cc.id', '=', 'ccu.course_class_id')
            ->leftJoin('courses as c', 'c.id', '=', 'cc.course_id')
            ->where(function ($query) {
                $query->where('cc.program_type', 'avpn_ai')
                    ->orWhere('c.program_type', 'avpn_ai');
            })
            ->pluck('ccu.user_id');

        $legacyAvpnUserIds = $legacyAvpnFromCourses
            ->merge($legacyAvpnFromClasses)
            ->unique()
            ->values();

        if ($legacyAvpnUserIds->isEmpty()) {
            return back()->with('error', 'Tidak ditemukan user lama dengan riwayat kelas AVPN.');
        }

        $matchedUsers = User::query()
            ->whereIn('id', $legacyAvpnUserIds)
            ->get(['id', 'registration_program', 'avpn_verification_status']);

        $usersNeedingProgramUpdate = $matchedUsers
            ->where('registration_program', '!=', 'avpn_ai')
            ->pluck('id');

        $usersNeedingApproval = $matchedUsers
            ->filter(fn ($user) => $user->avpn_verification_status !== 'approved')
            ->pluck('id');

        if ($usersNeedingProgramUpdate->isNotEmpty()) {
            User::query()
                ->whereIn('id', $usersNeedingProgramUpdate)
                ->update([
                    'registration_program' => 'avpn_ai',
                ]);
        }

        if ($usersNeedingApproval->isNotEmpty()) {
            User::query()
                ->whereIn('id', $usersNeedingApproval)
                ->update([
                    'avpn_verification_status' => 'approved',
                    'avpn_google_form_submitted_at' => now(),
                    'avpn_verified_at' => now(),
                    'avpn_verified_by' => auth()->id(),
                    'avpn_rejection_reason' => null,
                ]);
        }

        $updatedUsersCount = $matchedUsers
            ->filter(function ($user) {
                return $user->registration_program !== 'avpn_ai'
                    || $user->avpn_verification_status !== 'approved';
            })
            ->count();

        \App\Models\ActivityLog::log('avpn_legacy_sync_executed', [
            'description' => 'Legacy AVPN participants synchronization executed.',
            'metadata' => [
                'matched_count' => $matchedUsers->count(),
                'updated_count' => $updatedUsersCount,
                'program_updated_count' => $usersNeedingProgramUpdate->count(),
                'approval_updated_count' => $usersNeedingApproval->count(),
                'executed_by' => auth()->id(),
            ],
        ]);

        $alreadySynced = $matchedUsers->count() - $updatedUsersCount;

        return back()->with(
            'success',
            "Sinkronisasi user lama AVPN selesai. Total terdeteksi: {$matchedUsers->count()}, diperbarui: {$updatedUsersCount}, sudah sinkron sebelumnya: {$alreadySynced}."
        );
    }

    public function analytics()
    {
        $this->authorize('viewAny', User::class);

        $participants = User::permission('attempt quizzes')->get();

        // ✅ DEBUG: Log beberapa sample data untuk debugging (bisa diaktifkan saat testing)
        if (config('app.debug') && $participants->count() > 0) {
            $sampleParticipants = $participants->take(5);
            foreach ($sampleParticipants as $p) {
                if ($p->date_of_birth) {
                    try {
                        $birthDate = \Carbon\Carbon::parse($p->date_of_birth);
                        $age = $birthDate->age;
                        \Log::info("Participant Age Debug", [
                            'name' => $p->name,
                            'date_of_birth' => $p->date_of_birth,
                            'calculated_age' => $age,
                            'is_future' => $birthDate->isFuture()
                        ]);
                    } catch (\Exception $e) {
                        \Log::error("Participant Age Parse Error", [
                            'name' => $p->name,
                            'date_of_birth' => $p->date_of_birth,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Gender Distribution (termasuk yang belum mengisi)
        $genderData = [
            'male' => $participants->where('gender', 'male')->count(),
            'female' => $participants->where('gender', 'female')->count(),
            'not_specified' => $participants->whereNull('gender')->count(),
        ];

        // ✅ FIXED: Age Distribution dengan validasi yang lebih baik
        $ageGroups = [
            '< 20' => 0,
            '20-25' => 0,
            '26-30' => 0,
            '31-35' => 0,
            '36-40' => 0,
            '> 40' => 0,
            'Tidak Diketahui' => 0,
        ];

        foreach ($participants as $participant) {
            if ($participant->date_of_birth) {
                try {
                    // Pastikan date_of_birth dalam format Carbon
                    $birthDate = \Carbon\Carbon::parse($participant->date_of_birth);

                    // Validasi: tanggal lahir tidak boleh di masa depan
                    if ($birthDate->isFuture()) {
                        $ageGroups['Tidak Diketahui']++;
                        continue;
                    }

                    // Validasi: umur tidak boleh lebih dari 120 tahun
                    $age = $birthDate->age;
                    if ($age > 120) {
                        $ageGroups['Tidak Diketahui']++;
                        continue;
                    }

                    // Kelompokkan berdasarkan umur
                    if ($age < 20) {
                        $ageGroups['< 20']++;
                    } elseif ($age >= 20 && $age <= 25) {
                        $ageGroups['20-25']++;
                    } elseif ($age >= 26 && $age <= 30) {
                        $ageGroups['26-30']++;
                    } elseif ($age >= 31 && $age <= 35) {
                        $ageGroups['31-35']++;
                    } elseif ($age >= 36 && $age <= 40) {
                        $ageGroups['36-40']++;
                    } else {
                        $ageGroups['> 40']++;
                    }
                } catch (\Exception $e) {
                    // Jika parsing gagal, masukkan ke "Tidak Diketahui"
                    $ageGroups['Tidak Diketahui']++;
                }
            } else {
                $ageGroups['Tidak Diketahui']++;
            }
        }

        // Institution Distribution (Top 10, termasuk yang belum mengisi)
        $institutionDataRaw = $participants
            ->groupBy(function($item) {
                return $item->institution_name ?? 'Belum Diisi';
            })
            ->map->count()
            ->sortDesc();

        $institutionData = $institutionDataRaw->take(10);

        // Occupation Distribution (Top 10, termasuk yang belum mengisi)
        $occupationDataRaw = $participants
            ->groupBy(function($item) {
                return $item->occupation ?? 'Belum Diisi';
            })
            ->map->count()
            ->sortDesc();

        $occupationData = $occupationDataRaw->take(10);

        // Registration Trend (Last 12 months)
        $registrationTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $count = User::permission('attempt quizzes')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            $registrationTrend[$month->format('M Y')] = $count;
        }

        // Total Statistics
        $stats = [
            'total' => $participants->count(),
            'male' => $genderData['male'],
            'female' => $genderData['female'],
            'not_specified' => $genderData['not_specified'],
            'with_complete_data' => $participants->whereNotNull('gender')
                ->whereNotNull('date_of_birth')
                ->whereNotNull('institution_name')
                ->whereNotNull('occupation')
                ->count(),
            'this_month' => User::permission('attempt quizzes')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];

        return view('admin.participants.analytics', compact(
            'stats',
            'genderData',
            'ageGroups',
            'institutionData',
            'occupationData',
            'registrationTrend'
        ));
    }
}
