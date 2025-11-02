<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Layer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        User::factory()->create([
            'name' => 'Diego Pereira',
            'email' => 'diego.pereira@acto.com.br',
            'password' => bcrypt('password'),
        ]);

        Layer::factory()->count(5)->create();
    }
}
