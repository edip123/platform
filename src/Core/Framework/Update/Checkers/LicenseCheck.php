<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Update\Checkers;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Store\Services\StoreClient;
use Shopware\Core\Framework\Update\Struct\ValidationResult;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[Package('system-settings')]
class LicenseCheck implements CheckerInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly SystemConfigService $systemConfigService, private readonly StoreClient $storeClient)
    {
    }

    public function supports(string $check): bool
    {
        return $check === 'licensecheck';
    }

    /**
     * @param int|string|array $values
     */
    public function check($values): ValidationResult
    {
        $licenseHost = $this->systemConfigService->get('core.store.licenseHost');

        if (empty($licenseHost) || $this->storeClient->isShopUpgradeable()) {
            return new ValidationResult('validShopwareLicense', self::VALIDATION_SUCCESS, 'validShopwareLicense', []);
        }

        return new ValidationResult('invalidShopwareLicense', self::VALIDATION_ERROR, 'invalidShopwareLicense', []);
    }
}
