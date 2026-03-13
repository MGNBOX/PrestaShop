<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Adapter\Presenter\Order;

use Context;
use Exception;
use Hook;
use Order;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Adapter\Presenter\PresenterInterface;
use PrestaShop\PrestaShop\Core\ExtraProperty\Storage\ExtraPropertyValueProviderInterface;
use Throwable;

class OrderPresenter implements PresenterInterface
{
    /**
     * @var ExtraPropertyValueProviderInterface|null
     */
    protected $extraPropertyValueProvider;

    public function __construct()
    {
        $this->extraPropertyValueProvider = $this->resolveExtraPropertyValueProvider();
    }

    /**
     * @param Order $order
     *
     * @return OrderLazyArray
     *
     * @throws Exception
     */
    public function present($order)
    {
        if (!($order instanceof Order)) {
            throw new Exception('OrderPresenter can only present instance of Order');
        }

        $orderLazyArray = new OrderLazyArray($order, $this->extraPropertyValueProvider);

        Hook::exec('actionPresentOrder',
            ['presentedOrder' => &$orderLazyArray]
        );

        return $orderLazyArray;
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
