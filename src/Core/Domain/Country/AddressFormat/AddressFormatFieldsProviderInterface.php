<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\Country\AddressFormat;

/**
 * Lists the public properties of the ObjectModel classes that the address-format
 * picker exposes (Customer, Warehouse, Country, State, Address). Used by the form
 * type to populate the Vue builder's picker pills.
 */
interface AddressFormatFieldsProviderInterface
{
    /**
     * @param string $className One of the picker's object names
     *
     * @return list<string>
     */
    public function getFieldsForClass(string $className): array;
}
