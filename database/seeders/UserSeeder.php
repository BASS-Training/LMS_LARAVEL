<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default password untuk semua user seeder
        $defaultPassword = 'password';

        // Buat Super Admin
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => $defaultPassword, // Menetapkan password secara eksplisit
        ]);
        $admin->assignRole('super-admin'); // Berikan peran super-admin

        // Buat Instructor
        $instructor = User::factory()->create([
            'name' => 'Instructor User',
            'email' => 'instructor@example.com',
            'password' => $defaultPassword, // Menetapkan password secara eksplisit
        ]);
        $instructor->assignRole('instructor'); // Berikan peran instructor

        // Buat Participant
        $participant = User::factory()->create([
            'name' => 'Participant User',
            'email' => 'participant@example.com',
            'password' => $defaultPassword, // Menetapkan password secara eksplisit
        ]);
        $participant->assignRole('participant'); // Berikan peran participant

        // Buat Event Organizer
        $eventorganizer = User::factory()->create([
            'name' => 'Event Organizer',
            'email' => 'eo@example.com',
            'password' => $defaultPassword,
        ]);
        $eventorganizer->assignRole('event-organizer');
    }
}
