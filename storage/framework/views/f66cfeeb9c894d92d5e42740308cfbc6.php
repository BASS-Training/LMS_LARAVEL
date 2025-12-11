<?php if (isset($component)) { $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54 = $attributes; } ?>
<?php $component = App\View\Components\AppLayout::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('app-layout'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\AppLayout::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('header', null, []); ?> 
        <div class="flex flex-wrap justify-between items-center gap-4">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-2xl text-gray-900 leading-tight">Detail Peserta</h2>
                        <p class="text-blue-600 font-medium text-sm"><?php echo e($user->name); ?></p>
                    </div>
                </div>
            </div>
            <a href="<?php echo e(route('admin.participants.index')); ?>" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-xl font-medium text-sm text-gray-700 hover:bg-gray-50 hover:shadow-md transition-all duration-200 shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column - Personal Info -->
                <div class="lg:col-span-1">
                    <!-- Profile Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 px-6 py-8 text-center">
                            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                                <span class="text-3xl font-bold text-blue-600"><?php echo e(strtoupper(substr($user->name, 0, 2))); ?></span>
                            </div>
                            <h3 class="text-xl font-bold text-white mb-1"><?php echo e($user->name); ?></h3>
                            <p class="text-blue-100 text-sm"><?php echo e($user->email); ?></p>
                        </div>

                        <div class="px-6 py-6 space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Gender</label>
                                <p class="text-sm font-medium text-gray-900 mt-1">
                                    <?php if($user->gender): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo e($user->gender == 'male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'); ?>">
                                            <?php echo e($user->gender == 'male' ? 'Laki-laki' : 'Perempuan'); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Tidak diisi</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Tanggal Lahir</label>
                                <p class="text-sm font-medium text-gray-900 mt-1">
                                    <?php echo e($user->date_of_birth ? $user->date_of_birth->format('d F Y') : '-'); ?>

                                    <?php if($user->date_of_birth): ?>
                                        <span class="text-xs text-gray-500">(<?php echo e(floor($user->date_of_birth->diffInYears(now()))); ?> tahun)</span>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Institusi</label>
                                <p class="text-sm font-medium text-gray-900 mt-1"><?php echo e($user->institution_name ?? '-'); ?></p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Pekerjaan</label>
                                <p class="text-sm font-medium text-gray-900 mt-1"><?php echo e($user->occupation ?? '-'); ?></p>
                            </div>

                            <div class="pt-4 border-t border-gray-200">
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Terdaftar Sejak</label>
                                <p class="text-sm font-medium text-gray-900 mt-1"><?php echo e($user->created_at->format('d F Y')); ?></p>
                                <p class="text-xs text-gray-500"><?php echo e($user->created_at->diffForHumans()); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Course Enrollment -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-900">Kursus yang Diikuti</h3>
                            <p class="text-sm text-gray-600 mt-1">Total <?php echo e($enrolledCourses->count()); ?> kursus</p>
                        </div>

                        <div class="p-6">
                            <?php $__empty_1 = true; $__currentLoopData = $enrolledCourses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $course = $enrollment['course'];
                                    $progress = $enrollment['progress'];
                                ?>
                                <div class="mb-6 last:mb-0 border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
                                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 py-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="font-semibold text-gray-900"><?php echo e($course->title); ?></h4>
                                            <span class="text-sm font-medium text-blue-600"><?php echo e($progress['progress_percentage']); ?>%</span>
                                        </div>
                                        <div class="mt-2 bg-gray-200 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-300" style="width: <?php echo e($progress['progress_percentage']); ?>%"></div>
                                        </div>
                                    </div>

                                    <div class="px-4 py-3 bg-white">
                                        <div class="grid grid-cols-3 gap-4 text-center">
                                            <div>
                                                <p class="text-xs text-gray-500 mb-1">Lessons</p>
                                                <p class="text-lg font-bold text-gray-900"><?php echo e($progress['completed_lessons']); ?>/<?php echo e($progress['total_lessons']); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 mb-1">Quiz</p>
                                                <p class="text-lg font-bold text-gray-900"><?php echo e($progress['completed_quizzes']); ?>/<?php echo e($progress['total_quizzes']); ?></p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-gray-500 mb-1">Rata-rata</p>
                                                <p class="text-lg font-bold <?php echo e($progress['average_quiz_score'] >= 70 ? 'text-green-600' : 'text-orange-600'); ?>">
                                                    <?php echo e(number_format($progress['average_quiz_score'], 1)); ?>%
                                                </p>
                                            </div>
                                        </div>

                                        <div class="mt-3 flex justify-end">
                                            <a href="<?php echo e(route('courses.participant.progress', ['course' => $course, 'user' => $user])); ?>" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                </svg>
                                                Lihat Detail Progress
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-1">Belum Mengikuti Kursus</h3>
                                    <p class="text-sm text-gray-500">Peserta ini belum terdaftar di kursus apapun.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $attributes = $__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__attributesOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54)): ?>
<?php $component = $__componentOriginal9ac128a9029c0e4701924bd2d73d7f54; ?>
<?php unset($__componentOriginal9ac128a9029c0e4701924bd2d73d7f54); ?>
<?php endif; ?>
<?php /**PATH C:\Users\PC5\Music\lms\LMS_LARAVEL\resources\views/admin/participants/show.blade.php ENDPATH**/ ?>