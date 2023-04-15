<?php

namespace Modules\Car\Tests;

use Illuminate\Database\Seeder;
use Modules\Car\Car;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Car::factory()
            ->count(10)
            ->create();
    }
}
