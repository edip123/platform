<?php declare(strict_types=1);

namespace Shopware\Storefront\Framework\Twig;

use Shopware\Core\Framework\Adapter\Twig\TemplateFinder;
use Shopware\Core\Framework\Log\Package;
use Shopware\Storefront\Framework\Twig\TokenParser\IconTokenParser;
use Twig\Extension\AbstractExtension;

#[Package('storefront')]
class IconExtension extends AbstractExtension
{
    /**
     * @internal
     */
    public function __construct(private readonly TemplateFinder $finder)
    {
    }

    public function getTokenParsers(): array
    {
        return [
            new IconTokenParser(),
        ];
    }

    public function getFinder(): TemplateFinder
    {
        return $this->finder;
    }
}
