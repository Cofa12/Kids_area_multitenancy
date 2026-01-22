<?php

namespace Database\Seeders;

use App\Models\LandlordUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LandlordUser::create([
            'name'=>'Admin',
            'email'=>'ynsglobalcompany@gmail.com',
            'password'=>Hash::make('Ju>yg]G9SMt*#$BW105;'),
        ]);

    }
}
