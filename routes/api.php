<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourseApiController;

Route::get('/mobile/courses', [CourseApiController::class, 'index']);