<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Presenter\Category;

use Category;
use Context;
use Hook;
use Language;
use Link;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;
use Throwable;

class CategoryPresenter
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
     * @param array|Category $category Category object or an array
     * @param Language $language
     *
     * @return CategoryLazyArray
     */
    public function present(array|Category $category, Language $language): CategoryLazyArray
    {
        // Convert to array if a Category object was passed
        if (is_object($category)) {
            $category = (array) $category;
        }

        // Normalize IDs
        if (empty($category['id_category'])) {
            $category['id_category'] = $category['id'];
        }
        if (empty($category['id'])) {
            $category['id'] = $category['id_category'];
        }

        $categoryLazyArray = new CategoryLazyArray(
            $category,
            $language,
            $this->imageRetriever,
            $this->link,
            $this->extraPropertyValueProvider
        );

        Hook::exec('actionPresentCategory',
            ['presentedCategory' => &$categoryLazyArray]
        );

        return $categoryLazyArray;
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
