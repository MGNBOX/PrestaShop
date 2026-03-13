<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\ExtraProperty\CommandHandler;

use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Command\UpdateExtraPropertyValuesCommand;
use PrestaShop\PrestaShop\Core\Domain\ExtraProperty\Exception\ExtraPropertyDomainException;

/**
 * Contract for the handler that persists extra property values.
 *
 * Implementations must write all three scopes (common, lang, shop) carried
 * by the command to their respective *_extra / *_extra_lang / *_extra_shop tables.
 */
interface UpdateExtraPropertyValuesCommandHandlerInterface
{
    /**
     * Persists extra property values for a single entity instance.
     *
     * @param UpdateExtraPropertyValuesCommand $command
     *
     * @throws ExtraPropertyDomainException when the write cannot be completed
     */
    public function handle(UpdateExtraPropertyValuesCommand $command): void;
}
