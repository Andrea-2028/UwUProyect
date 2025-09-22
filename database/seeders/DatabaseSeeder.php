<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash; 

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([    
            'first_name' => 'Andrea',    
            'last_name'  => 'GuelDesarrollador',    
            'phone'      => '1234567890',    
            'email'      => 'safekidsandrea@gmail.com',    
            'password'   => Hash::make('G615243m20?'),  
            'status'     => 'active',]);
    }
}
