<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course; // Memanggil kerangka tabel Course

class CourseApiController extends Controller
{
    public function index()
    {
        // Mengambil semua data dari tabel courses
        $courses = Course::all(); 

        // Mengirimkannya kembali dalam bentuk JSON ke Flutter
        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil mengambil daftar course',
            'data' => $courses
        ]);
    }
}