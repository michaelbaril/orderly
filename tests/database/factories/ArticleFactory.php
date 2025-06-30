<?php

namespace Baril\Orderly\Tests\Factories;

use Baril\Orderly\Tests\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(3),
            'body' => $this->faker->text(50),
        ];
    }
}
