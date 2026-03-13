<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\ExtraProperty\CommandHandler;

use PrestaShop\PrestaShop\Core\CommandBus\Attributes\AsCommandHandler;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Command\UpdateExtraPropertyValuesCommand;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\CommandHandler\UpdateExtraPropertyValuesCommandHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Exception\ExtraPropertyDomainException;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyWriterInterface;
use Throwable;

/**
 * Persists extra property values by delegating to ExtraPropertyWriterInterface.
 *
 * Handles all three scopes carried by UpdateExtraPropertyValuesCommand:
 *  - common scope : one writeAll() call with entityValues
 *  - lang scope   : one writeAll() call with langValuesByIdLang (all langs at once)
 *  - shop scope   : one writeAll() call per shop in shopValuesByShopId
 *
 * Only non-empty scope payloads generate a write call to avoid unnecessary UPSERTs.
 */
#[AsCommandHandler]
class UpdateExtraPropertyValuesCommandHandler implements UpdateExtraPropertyValuesCommandHandlerInterface
{
    public function __construct(
        protected readonly ExtraPropertyWriterInterface $writer,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function handle(UpdateExtraPropertyValuesCommand $command): void
    {
        if ($command->getEntityId() <= 0) {
            return;
        }

        $entityName = $command->getEntityName();
        $primaryKeyName = $command->getPrimaryKeyName();
        $entityId = $command->getEntityId();

        try {
            // Common + lang scope in a single writeAll() call
            if (!empty($command->getEntityValues()) || !empty($command->getLangValuesByIdLang())) {
                $this->writer->writeAll(
                    $entityName,
                    $primaryKeyName,
                    $entityId,
                    $command->getEntityValues(),
                    $command->getLangValuesByIdLang(),
                    [],
                    $command->getLangShopId()
                );
            }

            // Shop scope: one writeAll() per shop to keep the writer interface unchanged
            foreach ($command->getShopValuesByShopId() as $shopId => $shopValues) {
                if (empty($shopValues)) {
                    continue;
                }
                $this->writer->writeAll(
                    $entityName,
                    $primaryKeyName,
                    $entityId,
                    [],
                    [],
                    $shopValues,
                    (int) $shopId
                );
            }
        } catch (Throwable $e) {
            throw new ExtraPropertyDomainException(
                sprintf(
                    'Failed to persist extra property values for entity "%s" (id=%d): %s',
                    $entityName,
                    $entityId,
                    $e->getMessage()
                ),
                0,
                $e
            );
        }
    }
}
