<?php

namespace App\Domain\Releases;

use InvalidArgumentException;
use OverflowException;

final readonly class SemanticVersion
{
    private const MAX_SEGMENT = 9999;

    private const FORMAT = '/^(?<major>\d+)\.(?<minor>\d+)\.(?<patch>\d+)$/D';

    public function __construct(
        public int $major,
        public int $minor,
        public int $patch,
    ) {
        foreach ([$major, $minor, $patch] as $segment) {
            if ($segment < 0 || $segment > self::MAX_SEGMENT) {
                throw new InvalidArgumentException('Semantic version segments must be between 0 and 9999.');
            }
        }
    }

    public static function fromString(string $value): self
    {
        if (preg_match(self::FORMAT, $value, $matches) !== 1) {
            throw new InvalidArgumentException('Semantic versions must use the major.minor.patch format.');
        }

        return new self(
            (int) $matches['major'],
            (int) $matches['minor'],
            (int) $matches['patch'],
        );
    }

    public static function normalize(string $value): string
    {
        return self::fromString($value)->toString();
    }

    public function incrementMajor(): self
    {
        return new self($this->increment($this->major), 0, 0);
    }

    public function incrementMinor(): self
    {
        return new self($this->major, $this->increment($this->minor), 0);
    }

    public function incrementPatch(): self
    {
        return new self($this->major, $this->minor, $this->increment($this->patch));
    }

    public function toString(): string
    {
        return sprintf('%d.%d.%d', $this->major, $this->minor, $this->patch);
    }

    private function increment(int $segment): int
    {
        if ($segment === self::MAX_SEGMENT) {
            throw new OverflowException('Semantic version segments cannot exceed 9999.');
        }

        return $segment + 1;
    }
}
