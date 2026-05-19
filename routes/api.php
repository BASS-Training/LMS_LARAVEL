<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourseApiController;
use App\Http\Controllers\Api\QuizApiController;

Route::get('/mobile/courses', [CourseApiController::class, 'index']);

// Quiz API for mobile
Route::get('/quizzes/by-lesson/{lessonId}', [QuizApiController::class, 'getByLesson']);
Route::post('/quizzes/{quiz}/attempts', [QuizApiController::class, 'startAttempt']);
Route::post('/quizzes/{quiz}/attempts/{attempt}/submit', [QuizApiController::class, 'submitAttempt']);

// Legacy aliases for older clients
Route::get('/mobile/quizzes/by-lesson/{lessonId}', [QuizApiController::class, 'getByLesson']);
Route::post('/mobile/quizzes/{quiz}/attempts', [QuizApiController::class, 'startAttempt']);
Route::post('/mobile/quizzes/{quiz}/attempts/{attempt}/submit', [QuizApiController::class, 'submitAttempt']);
