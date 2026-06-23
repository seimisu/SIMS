<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scholar>
 */
class ScholarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spas_no' => 'U-' . $this->faker->unique()->numberBetween(10000, 99999),
            'status_id' => rand(1, 3),
            'academic_status' => 'Ongoing',
        ];
    }
}
