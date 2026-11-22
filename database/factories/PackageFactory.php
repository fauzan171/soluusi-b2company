<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    protected $model = \App\Models\Package::class;

    public function definition()
    {
        return [
            'name' => 'Package ' . $this->faker->words(2, true),
            'monthly_quota' => 2,
            'is_active' => true,
        ];
    }
}
