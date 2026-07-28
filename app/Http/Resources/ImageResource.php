<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Image
 */
class ImageResource extends JsonResource
{
    /**
     * @param  Image  $resource
     */
    public function __construct($resource, private readonly bool $includeDeleteUrl = false)
    {
        parent::__construct($resource);
    }

    /**
     * Variant that includes the guest delete URL. Only for the uploader's own
     * response, never public payloads, since it exposes the delete token.
     */
    public static function owned(Image $image): self
    {
        return new self($image, includeDeleteUrl: true);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => (string) $this->public_id,
            'url' => $this->url,
            'direct_url' => $this->direct_url,
            'thumbnail_url' => $this->thumbnail_url,
            'width' => $this->width,
            'height' => $this->height,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'mime_type' => $this->mime_type,
            'media_type' => $this->media_type->value,
            'title' => $this->title,
            'views' => $this->views,
            'created_at' => $this->created_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            ...$this->includeDeleteUrl ? ['delete_url' => $this->delete_url] : [],
        ];
    }
}
