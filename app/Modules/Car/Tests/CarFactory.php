<?php

namespace Modules\Car\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Car\Car;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Car\Car>
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name()
        ];
    }
}
