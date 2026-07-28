<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $checksum
 * @property string|null $reason
 * @property Carbon|null $created_at
 */
class BlockedChecksum extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['checksum', 'reason'];

    /**
     * Whether the given checksum has been blocked.
     */
    public static function isBlocked(?string $checksum): bool
    {
        return $checksum !== null && static::where('checksum', $checksum)->exists();
    }
}
