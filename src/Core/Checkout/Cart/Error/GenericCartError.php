<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Cart\Error;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class GenericCartError extends Error
{
    public function __construct(protected string $id, protected string $messageKey, protected array $parameters, protected int $level, protected bool $blockOrder, protected bool $persistent)
    {
        parent::__construct();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMessageKey(): string
    {
        return $this->messageKey;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function blockOrder(): bool
    {
        return $this->blockOrder;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getRoute(): ?ErrorRoute
    {
        return null;
    }

    public function isPersistent(): bool
    {
        return $this->persistent;
    }
}
