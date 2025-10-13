<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
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
        //crear roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $visitorRole = Role::firstOrCreate(['name' => 'visitor']);
        //crear user
        $user = User::create([    
            'first_name' => 'Andrea',    
            'last_name'  => 'GuelDesarrollador',    
            'phone'      => '1234567890',    
            'email'      => 'safekidsandrea@gmail.com',    
            'password'   => Hash::make('goku123?'),  
            'status'     => 'active',]);

        // Asignar rol admin al usuario
        $user->roles()->attach($adminRole->id);
    }
}
