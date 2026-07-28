<?php

declare(strict_types=1);

use App\Exceptions\InvalidPublicIdException;
use App\ValueObjects\PublicId;

it('generates a 10-character alphanumeric id', function () {
    $id = PublicId::generate();

    expect((string) $id)->toMatch('/^[0-9A-Za-z]{10}$/');
});

it('generates unique ids', function () {
    $ids = collect(range(1, 100))->map(fn () => (string) PublicId::generate());

    expect($ids->unique())->toHaveCount(100);
});

it('creates from a valid string', function () {
    $id = PublicId::fromString('aB3dE5gH7j');

    expect((string) $id)->toBe('aB3dE5gH7j');
});

it('throws on an invalid string', function () {
    PublicId::fromString('too-short');
})->throws(InvalidPublicIdException::class);

it('returns null from tryFromString on invalid input', function () {
    expect(PublicId::tryFromString('nope!'))->toBeNull();
});

it('validates format', function () {
    expect(PublicId::isValid('aB3dE5gH7j'))->toBeTrue()
        ->and(PublicId::isValid('short'))->toBeFalse();
});

it('compares equality by value', function () {
    expect(PublicId::fromString('aB3dE5gH7j')->equals(PublicId::fromString('aB3dE5gH7j')))->toBeTrue()
        ->and(PublicId::fromString('aB3dE5gH7j')->equals(PublicId::fromString('zZ9yX8wV7u')))->toBeFalse();
});
