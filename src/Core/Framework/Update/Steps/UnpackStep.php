<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Update\Steps;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Update\Exception\UpdateFailedException;
use Shopware\Core\Framework\Update\Services\Archive\Zip;
use Symfony\Component\Filesystem\Filesystem;

#[Package('system-settings')]
class UnpackStep
{
    private readonly string $destinationDir;

    public function __construct(private readonly string $source, $destinationDir, private readonly bool $testMode = false)
    {
        $this->destinationDir = rtrim((string) $destinationDir, '/') . '/';
    }

    /**
     * @throws UpdateFailedException
     */
    public function run(int $offset): FinishResult|ValidResult
    {
        $fs = new Filesystem();
        $requestTime = time();

        // TestMode
        if ($this->testMode === true && $offset >= 90) {
            return new FinishResult(100, 100);
        }

        if ($this->testMode === true) {
            return new ValidResult($offset + 10, 100);
        }

        try {
            $source = new Zip($this->source);
            $count = $source->count();
            $source->seek($offset);
        } catch (\Exception $e) {
            @unlink($this->source);

            throw new UpdateFailedException(sprintf('Could not open update package:<br>%s', $e->getMessage()), 0, $e);
        }

        while (true) {
            $iter = $source->each();
            if ($iter === []) {
                break;
            }

            [$position, $entry] = $iter;

            $name = $entry->getName();
            $targetName = $this->destinationDir . $name;

            if (!$entry->isDir()) {
                $fs->dumpFile($targetName, $entry->getContents());
            }

            if (time() - $requestTime >= 20 || ($position + 1) % 1000 === 0) {
                $source->close();

                return new ValidResult($position + 1, $count);
            }
        }

        $source->close();
        unlink($this->source);

        return new FinishResult($count, $count);
    }
}
