<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MediaType;
use App\Models\Image;
use App\Models\User;
use App\ValueObjects\PublicId;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Image>
 */
class ImageFactory extends Factory
{
    protected $model = Image::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $publicId = (string) PublicId::generate();
        $extension = fake()->randomElement(['jpg', 'png', 'webp']);
        $width = fake()->numberBetween(320, 4000);
        $height = fake()->numberBetween(320, 4000);
        $mime = $extension === 'jpg' ? 'image/jpeg' : "image/{$extension}";

        return [
            'public_id' => $publicId,
            'user_id' => User::factory(),
            'disk' => 'images',
            'path' => substr($publicId, 0, 2).'/'.substr($publicId, 2, 2)."/{$publicId}.{$extension}",
            'filename' => "{$publicId}.{$extension}",
            'original_filename' => fake()->word().'.'.$extension,
            'mime_type' => $mime,
            'media_type' => MediaType::Image,
            'size' => fake()->numberBetween(10_000, 8_000_000),
            'width' => $width,
            'height' => $height,
            'checksum' => hash('sha256', $publicId),
            'title' => null,
            'description' => null,
            'expires_at' => null,
            'delete_token' => Str::random(64),
            'views' => 0,
        ];
    }

    /**
     * Indicate the image was uploaded anonymously.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes): array => ['user_id' => null]);
    }

    /**
     * Indicate the image has already expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subHour(),
        ]);
    }
}
