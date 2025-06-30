<?php

namespace Baril\Orderly\Tests\Factories;

use Baril\Orderly\Tests\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'url' => $this->faker->url,
        ];
    }
}
