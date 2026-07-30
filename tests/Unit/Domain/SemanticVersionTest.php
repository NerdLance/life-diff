<?php

use App\Domain\Releases\ReleaseVersionSuggester;
use App\Domain\Releases\SemanticVersion;
use App\Enums\ReleaseType;
use InvalidArgumentException;
use OverflowException;

test('semantic versions parse valid values', function (string $value, string $expected): void {
    expect(SemanticVersion::fromString($value)->toString())->toBe($expected);
})->with([
    ['0.0.0', '0.0.0'],
    ['1.2.3', '1.2.3'],
    ['9999.9999.9999', '9999.9999.9999'],
]);

test('semantic versions reject invalid values', function (string $value): void {
    SemanticVersion::fromString($value);
})->with([
    'v1.2.3',
    '1.2',
    '1.2.3.4',
    '1.2.3-beta',
    '1.2.3+build',
    '-1.2.3',
    '1.-2.3',
    '1.2.-3',
    'one.two.three',
    ' 1.2.3',
    '1.2.3 ',
])->throws(InvalidArgumentException::class);

test('semantic version normalization removes segment padding', function (): void {
    expect(SemanticVersion::normalize('0001.002.0003'))->toBe('1.2.3');
});

test('semantic versions reject segments outside the contract limits', function (string $value): void {
    SemanticVersion::fromString($value);
})->with([
    '10000.0.0',
    '0.10000.0',
    '0.0.10000',
])->throws(InvalidArgumentException::class);

test('release version suggestions follow the requested release type', function (ReleaseType $releaseType, string $expected): void {
    $suggestion = (new ReleaseVersionSuggester)->suggest(
        SemanticVersion::fromString('2.4.9'),
        $releaseType,
    );

    expect($suggestion->toString())->toBe($expected);
})->with([
    [ReleaseType::Major, '3.0.0'],
    [ReleaseType::Minor, '2.5.0'],
    [ReleaseType::Patch, '2.4.10'],
    [ReleaseType::Hotfix, '2.4.10'],
    [ReleaseType::Experimental, '2.4.10'],
    [ReleaseType::Rollback, '2.4.10'],
]);

test('release version suggestions start at the contract first release for every release type', function (ReleaseType $releaseType): void {
    $suggestion = (new ReleaseVersionSuggester)->suggest(null, $releaseType);

    expect($suggestion->toString())->toBe('0.1.0');
})->with(ReleaseType::cases());

test('release version suggestions reject segment overflow', function (): void {
    (new ReleaseVersionSuggester)->suggest(SemanticVersion::fromString('9999.0.0'), ReleaseType::Major);
})->throws(OverflowException::class);
