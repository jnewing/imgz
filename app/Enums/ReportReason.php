<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportReason: string
{
    case Sexual = 'sexual';
    case Violence = 'violence';
    case Illegal = 'illegal';
    case Copyright = 'copyright';
    case Spam = 'spam';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Sexual => 'Sexual or explicit content',
            self::Violence => 'Violence or gore',
            self::Illegal => 'Illegal content',
            self::Copyright => 'Copyright infringement',
            self::Spam => 'Spam',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $reason): array => [
            'value' => $reason->value,
            'label' => $reason->label(),
        ], self::cases());
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $reason): string => $reason->value, self::cases());
    }
}
