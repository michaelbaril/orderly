<?php

namespace Baril\Orderly\Tests\Factories;

use Baril\Orderly\Tests\Models\Paragraph;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParagraphFactory extends Factory
{
    protected $model = Paragraph::class;

    public function definition()
    {
        return [
            'content' => $this->faker->text(20),
        ];
    }
}
