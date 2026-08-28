<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (User::where('email', 'admin@kkconverter.local')->exists()) {
            return;
        }

        $password = Str::password(16);

        User::create([
            'name' => 'Admin',
            'email' => 'admin@kkconverter.local',
            'email_verified_at' => now(),
            'password' => $password,
        ]);

        $this->command->info("Admin account created — email: admin@kkconverter.local / password: {$password}");
    }
}
