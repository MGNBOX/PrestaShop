<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Adapter\Presenter\Supplier;

use Context;
use Hook;
use Language;
use Link;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;
use Supplier;
use Throwable;

class SupplierPresenter
{
    /**
     * @var ImageRetriever
     */
    protected $imageRetriever;

    /**
     * @var Link
     */
    protected $link;

    /**
     * @var ExtraPropertyValueProviderInterface|null
     */
    protected $extraPropertyValueProvider;

    public function __construct(Link $link)
    {
        $this->link = $link;
        $this->imageRetriever = new ImageRetriever($link);
        $this->extraPropertyValueProvider = $this->resolveExtraPropertyValueProvider();
    }

    /**
     * @param array|Supplier $supplier Supplier object or an array
     * @param Language $language
     *
     * @return SupplierLazyArray
     */
    public function present(array|Supplier $supplier, Language $language)
    {
        // Convert to array if a Supplier object was passed
        if (is_object($supplier)) {
            $supplier = (array) $supplier;
        }

        // Normalize IDs
        if (empty($supplier['id_supplier'])) {
            $supplier['id_supplier'] = $supplier['id'];
        }
        if (empty($supplier['id'])) {
            $supplier['id'] = $supplier['id_supplier'];
        }

        $supplierLazyArray = new SupplierLazyArray(
            $supplier,
            $language,
            $this->imageRetriever,
            $this->link,
            $this->extraPropertyValueProvider
        );

        Hook::exec('actionPresentSupplier',
            ['presentedSupplier' => &$supplierLazyArray]
        );

        return $supplierLazyArray;
    }

    /**
     * Resolves the front-office extra property provider from the service container when available.
     */
    protected function resolveExtraPropertyValueProvider(): ?ExtraPropertyValueProviderInterface
    {
        try {
            $containerFinder = new ContainerFinder(Context::getContext());

            /** @var ExtraPropertyValueProviderInterface $extraPropertyValueProvider */
            $extraPropertyValueProvider = $containerFinder->getContainer()->get(ExtraPropertyValueProviderInterface::class);

            return $extraPropertyValueProvider;
        } catch (Throwable $e) {
            return null;
        }
    }
}
