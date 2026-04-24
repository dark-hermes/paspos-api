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
        // Main Admin
        User::factory()->assignStore(1)->create([
            'name' => 'Main Admin',
            'email' => 'mainadmin@paspos.local',
            'role' => 'main_admin',
        ]);

        // Branch Admin
        User::factory()->assignStore(2)->create([
            'name' => 'Branch Admin',
            'email' => 'branchadmin@paspos.local',
            'role' => 'branch_admin',
        ]);

        // Cashier
        User::factory()->assignStore(2)->create([
            'name' => 'Cashier',
            'email' => 'cashier@paspos.local',
            'role' => 'cashier',
        ]);

        // Member
        User::factory()->assignStore(2)->withAddress()->create([
            'name' => 'Member',
            'email' => 'member@paspos.local',
            'role' => 'member',
        ]);

        // Additional users
        User::factory()->count(5)->assignStore(2)->create([
            'role' => 'member',
        ]);
    }
}
