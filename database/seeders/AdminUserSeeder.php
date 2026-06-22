<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['user' => 'xinergia'],
            [
                'name' => 'Admin Xinergia',
                'password' => Hash::make('xinergia'),
                'role' => 'admin',
            ]
        );
    }
}
