<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SectorSeeder::class,
            EventoSeeder::class,
            AsientoSeeder::class,
            UserSeeder::class,
            PrecioSeeder::class,
            ArtistaSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🎉 Base de datos poblada correctamente');
    }
}