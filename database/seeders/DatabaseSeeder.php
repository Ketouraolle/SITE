<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@maboutique.cm')],
            [
                'name' => env('ADMIN_NAME', 'Administrateur'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ]
        );

        $this->call([CatalogSeeder::class, PhotoCatalogSeeder::class]);
    }
}
