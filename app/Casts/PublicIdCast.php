<?php

declare(strict_types=1);

namespace App\Casts;

use App\ValueObjects\PublicId;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<PublicId, PublicId|string>
 */
final class PublicIdCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PublicId
    {
        if ($value === null) {
            return null;
        }

        // tryFrom so malformed stored data doesn't throw
        return PublicId::tryFromString($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof PublicId) {
            return (string) $value;
        }

        return (string) PublicId::fromString($value);
    }
}
