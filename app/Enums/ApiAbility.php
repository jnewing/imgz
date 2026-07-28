<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiAbility: string
{
    case ReadImages = 'images:read';
    case WriteImages = 'images:write';
    case DeleteImages = 'images:delete';

    public function label(): string
    {
        return match ($this) {
            self::ReadImages => 'List and view images',
            self::WriteImages => 'Upload images',
            self::DeleteImages => 'Delete images',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $ability): array => [
            'value' => $ability->value,
            'label' => $ability->label(),
        ], self::cases());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $ability): string => $ability->value, self::cases());
    }
}
