<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = \App\Models\Schedule::class;

    public function definition()
    {
        $start = $this->faker->dateTimeBetween('+1 day', '+1 week');
        return [
            'started_at' => $start,
            'ended_at' => (clone $start)->modify('+60 minutes'),
            'consultant_id' => \App\Models\Consultant::factory(),
        ];
    }
}
