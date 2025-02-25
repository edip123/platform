<?php declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Checkout\Cart\Order;

use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
class TestError extends Error
{
    final public const LEVEL_UNKNOWN = \PHP_INT_MAX;

    private function __construct(private readonly int $level)
    {
    }

    public static function error(): self
    {
        return new self(self::LEVEL_ERROR);
    }

    public static function warn(): self
    {
        return new self(self::LEVEL_WARNING);
    }

    public static function notice(): self
    {
        return new self(self::LEVEL_NOTICE);
    }

    public static function unknown(): self
    {
        return new self(self::LEVEL_UNKNOWN);
    }

    public function getId(): string
    {
        return \sha1('foo_' . $this->level . Uuid::randomHex());
    }

    public function getMessageKey(): string
    {
        return 'LoremIpsumDolorSit';
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function blockOrder(): bool
    {
        return true;
    }

    public function getParameters(): array
    {
        return [];
    }
}
