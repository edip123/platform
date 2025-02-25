<?php declare(strict_types=1);

namespace Shopware\Storefront\Pagelet\Menu\Offcanvas;

use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Content\Category\Service\NavigationLoaderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

#[Package('storefront')]
class MenuOffcanvasPageletLoader implements MenuOffcanvasPageletLoaderInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher, private readonly NavigationLoaderInterface $navigationLoader)
    {
    }

    /**
     * @throws CategoryNotFoundException
     * @throws InconsistentCriteriaIdsException
     * @throws MissingRequestParameterException
     */
    public function load(Request $request, SalesChannelContext $context): MenuOffcanvasPagelet
    {
        $navigationId = (string) $request->query->get('navigationId', $context->getSalesChannel()->getNavigationCategoryId());
        if (!$navigationId) {
            throw new MissingRequestParameterException('navigationId');
        }

        $navigation = $this->navigationLoader->load($navigationId, $context, $navigationId, 1);

        $pagelet = new MenuOffcanvasPagelet($navigation);

        $this->eventDispatcher->dispatch(
            new MenuOffcanvasPageletLoadedEvent($pagelet, $context, $request)
        );

        return $pagelet;
    }
}
