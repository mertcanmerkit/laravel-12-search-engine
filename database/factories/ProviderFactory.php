<?php

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'slug' => $this->faker->unique()->slug(2),
            'name' => $this->faker->company . ' Provider',
            'base_url' => 'https://example.test',
            'rate_per_minute' => 60,
        ];
    }

    public function json(): self
    {
        return $this->state(fn () => [
            'slug' => 'json_provider',
            'name' => 'JSON Provider',
            'base_url' => 'https://raw.githubusercontent.com/WEG-Technology/mock/refs/heads/main/v2/provider1',
            'rate_per_minute' => 60,
        ]);
    }

    public function xml(): self
    {
        return $this->state(fn () => [
            'slug' => 'xml_provider',
            'name' => 'XML Provider',
            'base_url' => 'https://raw.githubusercontent.com/WEG-Technology/mock/refs/heads/main/v2/provider2',
            'rate_per_minute' => 60,
        ]);
    }
}

