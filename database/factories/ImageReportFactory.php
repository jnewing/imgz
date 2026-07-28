<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReportReason;
use App\Models\Image;
use App\Models\ImageReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImageReport>
 */
class ImageReportFactory extends Factory
{
    protected $model = ImageReport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_id' => Image::factory(),
            'reason' => fake()->randomElement(ReportReason::cases()),
            'reporter_ip' => fake()->unique()->ipv4(),
        ];
    }
}
