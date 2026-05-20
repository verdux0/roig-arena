<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador
        User::create([
            'nombre' => 'admin',
            'apellido' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
            'is_admin' => true,
        ]);
        $faker = Faker::create('es_ES');

        // Crear varios usuarios de prueba
        $count = 0;
        for ($i = 0; $i < 6; $i++) {
            $first = $faker->firstName();
            $last = $faker->lastName();
            User::create([
                'nombre' => $first,
                'apellido' => $last,
                'email' => strtolower($first . '.' . $last . $i . '@example.com'),
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]);
            $count++;
        }

        $this->command->info('✅ Usuarios creados: ' . (1 + $count) . ' (1 admin + ' . $count . ' normales)');
    }
}
