<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App\Command;

use Shopware\Core\Framework\App\AppStateService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * @internal only for use by the app-system, will be considered internal from v6.4.0 onward
 */
#[AsCommand(
    name: 'app:deactivate',
    description: 'Deactivates an app',
)]
#[Package('core')]
class DeactivateAppCommand extends AbstractAppActivationCommand
{
    private const ACTION = 'deactivate';

    public function __construct(EntityRepository $appRepo, private readonly AppStateService $appStateService)
    {
        parent::__construct($appRepo, self::ACTION);
    }

    public function runAction(string $appId, Context $context): void
    {
        $this->appStateService->deactivateApp($appId, $context);
    }
}
