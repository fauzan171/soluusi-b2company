<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Consultant>
 */
class ConsultantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'about' => $this->faker->text,
            'profit_sharing' => $this->faker->randomFloat(2, 0, 100),
            'user_id' => \App\Models\User::factory(),
            'status' => $this->faker->randomElement(['accept', 'reject', 'pending']),
            'uuid' => $this->faker->uuid,
            'reject_reason' => $this->faker->text,
        ];
    }
}
