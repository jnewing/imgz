<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\PublicIdCast;
use App\Enums\MediaType;
use App\ValueObjects\MimeType;
use App\ValueObjects\PublicId;
use Database\Factories\ImageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property PublicId|null $public_id
 * @property int|null $user_id
 * @property string $disk
 * @property string $path
 * @property string $filename
 * @property string|null $original_filename
 * @property string $mime_type
 * @property MediaType $media_type
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $checksum
 * @property string|null $title
 * @property string|null $description
 * @property Carbon|null $expires_at
 * @property string|null $delete_token
 * @property int $views
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read string $url
 * @property-read string $direct_url
 * @property-read string $thumbnail_url
 * @property-read string $delete_url
 * @property-read string $human_size
 */
class Image extends Model
{
    /** @use HasFactory<ImageFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'public_id',
        'disk',
        'path',
        'filename',
        'original_filename',
        'mime_type',
        'media_type',
        'size',
        'width',
        'height',
        'checksum',
        'title',
        'description',
        'expires_at',
        'delete_token',
        'views',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Image $image): void {
            if (empty($image->public_id)) {
                $image->public_id = self::uniquePublicId();
            }

            if (empty($image->delete_token)) {
                $image->delete_token = Str::random(64);
            }
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'public_id' => PublicIdCast::class,
            'media_type' => MediaType::class,
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'expires_at' => 'datetime',
            'views' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public static function uniquePublicId(): PublicId
    {
        do {
            $publicId = PublicId::generate();
        } while (self::where('public_id', (string) $publicId)->exists());

        return $publicId;
    }

    /**
     * The user that owns the image (null for anonymous uploads).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Abuse reports filed against the image.
     *
     * @return HasMany<ImageReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ImageReport::class);
    }

    public function getUrlAttribute(): string
    {
        return url("/i/{$this->public_id}");
    }

    public function getDirectUrlAttribute(): string
    {
        return $this->directUrl("{$this->public_id}.{$this->getExtension()}");
    }

    public function getThumbnailUrlAttribute(): string
    {
        return $this->directUrl(basename($this->thumbnailPath()));
    }

    /**
     * Direct file URL. Uses the configured direct-URL base (a cookieless
     * subdomain or CDN) when set, otherwise a same-origin /i/{filename} link.
     */
    private function directUrl(string $filename): string
    {
        $base = config('imgz.direct_url');

        return $base
            ? rtrim((string) $base, '/')."/{$filename}"
            : url("/i/{$filename}");
    }

    public function getDeleteUrlAttribute(): string
    {
        return url("/i/{$this->public_id}/delete/{$this->delete_token}");
    }

    /**
     * Storage path of the thumbnail variant, sitting next to the original.
     */
    public function thumbnailPath(): string
    {
        $format = strtolower((string) config('imgz.variants.thumbnail_format', 'webp'));
        $extension = $format === 'jpeg' ? 'jpg' : $format;

        $directory = trim(dirname($this->path), '.');
        $directory = $directory === '' ? '' : "{$directory}/";

        return "{$directory}{$this->public_id}_thumb.{$extension}";
    }

    public function getExtension(): string
    {
        return MimeType::tryFromString($this->mime_type)?->extension() ?? 'jpg';
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isAnonymous(): bool
    {
        return $this->user_id === null;
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (float) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Scope to images owned by a specific user.
     *
     * @param  Builder<Image>  $query
     * @return Builder<Image>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to images that have not expired.
     *
     * @param  Builder<Image>  $query
     * @return Builder<Image>
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', Carbon::now());
        });
    }
}
