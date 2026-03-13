<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Adapter\Presenter\Order;

use Context;
use Exception;
use Hook;
use Link;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Presenter\PresenterInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;
use ReflectionException;
use Throwable;

class OrderReturnPresenter implements PresenterInterface
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var Link
     */
    private $link;

    /**
     * @var ExtraPropertyValueProviderInterface|null
     */
    protected $extraPropertyValueProvider;

    /**
     * OrderReturnPresenter constructor.
     *
     * @param string $prefix
     * @param Link $link
     */
    public function __construct($prefix, Link $link)
    {
        $this->prefix = $prefix;
        $this->link = $link;
        $this->extraPropertyValueProvider = $this->resolveExtraPropertyValueProvider();
    }

    /**
     * @param array $orderReturn
     *
     * @return OrderReturnLazyArray
     *
     * @throws ReflectionException
     */
    public function present($orderReturn)
    {
        if (!is_array($orderReturn)) {
            throw new Exception('orderReturnPresenter can only present order_return passed as array');
        }

        $orderReturnLazyArray = new OrderReturnLazyArray(
            $this->prefix,
            $this->link,
            $orderReturn,
            $this->extraPropertyValueProvider
        );

        Hook::exec('actionPresentOrderReturn',
            ['presentedOrderReturn' => &$orderReturnLazyArray]
        );

        return $orderReturnLazyArray;
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
