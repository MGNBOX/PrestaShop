<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Adapter\Presenter\Manufacturer;

use Context;
use Hook;
use Language;
use Link;
use Manufacturer;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;
use Throwable;

class ManufacturerPresenter
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
     * @param array|Manufacturer $manufacturer Manufacturer object or an array
     * @param Language $language
     *
     * @return ManufacturerLazyArray
     */
    public function present(array|Manufacturer $manufacturer, Language $language)
    {
        // Convert to array if a Manufacturer object was passed
        if (is_object($manufacturer)) {
            $manufacturer = (array) $manufacturer;
        }

        // Normalize IDs
        if (empty($manufacturer['id_manufacturer'])) {
            $manufacturer['id_manufacturer'] = $manufacturer['id'];
        }
        if (empty($manufacturer['id'])) {
            $manufacturer['id'] = $manufacturer['id_manufacturer'];
        }

        $manufacturerLazyArray = new ManufacturerLazyArray(
            $manufacturer,
            $language,
            $this->imageRetriever,
            $this->link,
            $this->extraPropertyValueProvider
        );

        Hook::exec('actionPresentManufacturer',
            ['presentedManufacturer' => &$manufacturerLazyArray]
        );

        return $manufacturerLazyArray;
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
