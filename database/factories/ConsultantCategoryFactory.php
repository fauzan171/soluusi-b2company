<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConsultantCategory>
 */
class ConsultantCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $categoryIds = \App\Models\Category::pluck('id')->toArray();

        return [
            'consultant_id' => \App\Models\Consultant::factory(),
            'category_id' => $this->faker->randomElement($categoryIds),
        ];
    }
}
