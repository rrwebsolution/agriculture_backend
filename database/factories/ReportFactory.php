<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = fake()->dateTimeBetween('-6 months', '-1 month');
        $to = fake()->dateTimeBetween($from, 'now');

        return [
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(['Production', 'Fishery', 'Financial', 'Census', 'Inventory']),
            'module' => fake()->word(),
            'period_from' => $from->format('Y-m-d'),
            'period_to' => $to->format('Y-m-d'),
            'generated_by' => fake()->name(),
            'generated_at' => now(),
            'format' => fake()->randomElement(['PDF', 'XLSX']),
            'status' => fake()->randomElement(['Published', 'Pending Review', 'Draft']),
            'notes' => fake()->optional()->sentence(),
            'file_path' => null,
        ];
    }
}
