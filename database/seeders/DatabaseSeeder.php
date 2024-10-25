<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Hash;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        User::factory()->create([
            'name' => 'Ahmed Osama',
            'email' => 'hello@ahmedosama-st.com',
            'password' => Hash::make('password'),
        ]);

        Product::factory()->count(250)->create();
        Product::factory()->count(250)->withoutStock()->create();
    }
}
