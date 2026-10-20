<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConsultantPackage>
 */
class ConsultantPackageFactory extends Factory
{
    protected $model = \App\Models\ConsultantPackage::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->numberBetween(200_000, 500_000),
            'duration' => 60,
            'quota' => 1,
            'consultant_id' => \App\Models\Consultant::factory(),
        ];
    }
}
