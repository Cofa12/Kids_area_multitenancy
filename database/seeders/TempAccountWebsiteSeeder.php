<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class TempAccountWebsiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public static function run(): void
    {
        User::create([
            'name'=>'Ahmed',
            'phone'=>'+2010123456789',
            'password'=>'mK5lj2jlk##',
        ]);
    }
}
