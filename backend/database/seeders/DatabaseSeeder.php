<?php

namespace Database\Seeders;

<<<<<<< Updated upstream
use App\Models\User;
=======
>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
=======
        $this->call(SemsDemoSeeder::class);
>>>>>>> Stashed changes
    }
}
