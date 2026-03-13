<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Adapter\Presenter\Store;

use Context;
use Hook;
use Language;
use Link;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;
use Store;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

class StorePresenter
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ExtraPropertyValueProviderInterface|null
     */
    protected $extraPropertyValueProvider;

    public function __construct(
        Link $link,
        TranslatorInterface $translator
    ) {
        $this->link = $link;
        $this->imageRetriever = new ImageRetriever($link);
        $this->translator = $translator;
        $this->extraPropertyValueProvider = $this->resolveExtraPropertyValueProvider();
    }

    /**
     * @param array|Store $store Store object or an array
     * @param Language $language
     *
     * @return StoreLazyArray
     */
    public function present($store, $language)
    {
        // Convert to array if a Store object was passed
        if (is_object($store)) {
            $store = (array) $store;
        }

        // Normalize IDs
        if (empty($store['id_store'])) {
            $store['id_store'] = $store['id'];
        }
        if (empty($store['id'])) {
            $store['id'] = $store['id_store'];
        }

        $storeLazyArray = new StoreLazyArray(
            $store,
            $language,
            $this->imageRetriever,
            $this->translator,
            $this->extraPropertyValueProvider
        );

        Hook::exec('actionPresentStore',
            ['presentedStore' => &$storeLazyArray]
        );

        return $storeLazyArray;
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
