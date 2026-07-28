<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportReason;
use Database\Factories\ImageReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $image_id
 * @property ReportReason $reason
 * @property string|null $reporter_ip
 * @property Carbon|null $created_at
 */
class ImageReport extends Model
{
    /** @use HasFactory<ImageReportFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = ['image_id', 'reason', 'reporter_ip'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
        ];
    }

    /**
     * @return BelongsTo<Image, $this>
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }
}
