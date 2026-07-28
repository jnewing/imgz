<?php

declare(strict_types=1);

use App\Models\BlockedChecksum;
use App\Models\Image;
use App\Models\ImageReport;
use App\Models\User;
use App\Services\ImageStorer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('images');
    Queue::fake();
});

it('lets anyone report an image', function () {
    $image = Image::factory()->create();

    $this->post("/i/{$image->public_id}/report", ['reason' => 'spam'])
        ->assertRedirect();

    expect($image->reports()->count())->toBe(1)
        ->and($image->reports()->first()->reason->value)->toBe('spam');
});

it('deduplicates reports from the same ip', function () {
    $image = Image::factory()->create();

    $this->post("/i/{$image->public_id}/report", ['reason' => 'spam']);
    $this->post("/i/{$image->public_id}/report", ['reason' => 'violence']);

    expect($image->reports()->count())->toBe(1);
});

it('validates the report reason', function () {
    $image = Image::factory()->create();

    $this->post("/i/{$image->public_id}/report", ['reason' => 'nonsense'])
        ->assertSessionHasErrors('reason');
});

it('keeps the moderation queue behind the admin gate', function () {
    $this->get('/admin/moderation')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/admin/moderation')
        ->assertForbidden();
});

it('lists only reported images for admins', function () {
    $reported = Image::factory()->create();
    ImageReport::factory()->for($reported)->create();
    Image::factory()->create(); // not reported

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/moderation')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Moderation')
            ->has('images.data', 1)
        );
});

it('removes an image and blocks its checksum', function () {
    $admin = User::factory()->admin()->create();
    $image = app(ImageStorer::class)->store(UploadedFile::fake()->image('bad.jpg'));
    ImageReport::factory()->for($image)->create();

    $this->actingAs($admin)
        ->delete("/admin/moderation/{$image->public_id}")
        ->assertRedirect();

    expect(Image::withTrashed()->count())->toBe(0)
        ->and(BlockedChecksum::where('checksum', $image->checksum)->exists())->toBeTrue();
    Storage::disk('images')->assertMissing($image->path);
});

it('dismisses reports without removing the image', function () {
    $admin = User::factory()->admin()->create();
    $image = Image::factory()->create();
    ImageReport::factory()->for($image)->count(2)->create();

    $this->actingAs($admin)
        ->post("/admin/moderation/{$image->public_id}/dismiss")
        ->assertRedirect();

    expect($image->fresh())->not->toBeNull()
        ->and($image->reports()->count())->toBe(0);
});
