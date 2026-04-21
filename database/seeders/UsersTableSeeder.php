<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\Fluent\Concerns\Has;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Иван Петрович',
            'email' => 'ivan@example.com',
            'password' => Hash::make('password'),
            'registration_date' => now(),
        ]);

        User::create([
            'name' => 'Мария Кузьмина',
            'email' => 'anna23@example.com',
            'password' => Hash::make('password'),
            'registration_date' => now(),
        ]);

        User::create([
            'name' => 'Алексей Иванов',
            'email' => 'pettr@example.com',
            'password' => Hash::make('password'),
            'registration_date' => now(),
        ]);
    }
}
