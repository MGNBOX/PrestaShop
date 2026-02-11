<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Adapter\Shipment\CommandHandler;

use Exception;
use PrestaShop\PrestaShop\Adapter\Order\Repository\OrderRepository;
use PrestaShop\PrestaShop\Adapter\Product\Repository\ProductRepository;
use PrestaShop\PrestaShop\Core\CommandBus\Attributes\AsCommandHandler;
use PrestaShop\PrestaShop\Core\Domain\Shipment\Command\CreateShipment;
use PrestaShop\PrestaShop\Core\Domain\Shipment\CommandHandler\CreateShipmentHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Shipment\Exception\ShipmentException;
use PrestaShop\PrestaShop\Core\Domain\Shipment\Exception\ShipmentNotFoundException;
use PrestaShopBundle\Entity\Repository\ShipmentRepository;
use PrestaShopBundle\Entity\Shipment;

#[AsCommandHandler]
class CreateShipmentHandler implements CreateShipmentHandlerInterface
{
    public function __construct(
        private readonly ShipmentRepository $shipmentRepository,
        private readonly OrderRepository $orderRepository,
    ) {
    }

    public function handle(CreateShipment $command): int
    {
        $order = $this->orderRepository->get($command->getOrderId());
        $carrierId = $command->getCarrierId()->getValue();

        if ($order === null) {
            throw new ShipmentNotFoundException(sprintf('No order found with id %s found', $command->getOrderId()->getValue()));
        }

        $shipment = new Shipment();
        $shipment->setOrderId((int) $order->id);
        $shipment->setCarrierId((int) $carrierId);
        $shipment->setAddressId((int) $order->id_address_delivery);
        $shipment->setTrackingNumber(null);
        $shipment->setShippingCostTaxExcluded((float) 0);
        $shipment->setShippingCostTaxIncluded((float) 0);
        $shipment->setDeliveredAt(null);
        $shipment->setShippedAt(null);
        $shipment->setCancelledAt(null);

        try {
            return $this->shipmentRepository->save($shipment);
        } catch (Exception $e) {
            throw new ShipmentException(sprintf('Failed to add products from shipment with id "%s"', $shipment), 0, $e);
        }
    }
}
