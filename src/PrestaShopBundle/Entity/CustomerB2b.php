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

namespace PrestaShopBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PrestaShopBundle\Entity\Enum\CustomerB2bStatus;

/**
 * CustomerB2b.
 *
 * @ORM\Table(
 *     indexes={
 *
 *         @ORM\Index(name="customer_b2b_customer_idx", columns={"id_customer"})
 *     },
 *     uniqueConstraints={
 *
 *         @ORM\UniqueConstraint(name="uniq_customer_b2b_customer", columns={"id_customer"})
 *     }
 * )
 *
 * @ORM\HasLifecycleCallbacks
 *
 * @ORM\Entity()
 */
class CustomerB2b
{
    /**
     * @ORM\Id
     *
     * @ORM\Column(name="id_customer_b2b", type="integer", options={"unsigned"=true})
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(name="id_customer", type="integer", options={"unsigned"=true})
     */
    private int $idCustomer;

    /**
     * @ORM\Column(name="status", enumType=CustomerB2bStatus::class, options={"default"="pending"})
     */
    private CustomerB2bStatus $status;

    /**
     * @ORM\Column(name="external_ref", type="string", length=255, nullable=true)
     */
    private ?string $externalRef = null;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private DateTime $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     */
    private DateTime $updatedAt;

    /**
     * @ORM\OneToMany(
     *     targetEntity="PrestaShopBundle\Entity\BusinessEntityCustomerB2b",
     *     mappedBy="customerB2b",
     *     cascade={"persist"}
     * )
     *
     * @var Collection<int, BusinessEntityCustomerB2b>
     */
    private Collection $businessEntityCustomerB2bs;

    public function __construct()
    {
        $this->businessEntityCustomerB2bs = new ArrayCollection();
        $this->status = CustomerB2bStatus::PENDING;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdCustomer(): int
    {
        return $this->idCustomer;
    }

    public function setIdCustomer(int $idCustomer): self
    {
        $this->idCustomer = $idCustomer;

        return $this;
    }

    public function getStatus(): CustomerB2bStatus
    {
        return $this->status;
    }

    public function setStatus(CustomerB2bStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getExternalRef(): ?string
    {
        return $this->externalRef;
    }

    public function setExternalRef(?string $externalRef): self
    {
        $this->externalRef = $externalRef;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, BusinessEntityCustomerB2b>
     */
    public function getBusinessEntityCustomerB2bs(): Collection
    {
        return $this->businessEntityCustomerB2bs;
    }

    public function addBusinessEntityCustomerB2b(BusinessEntityCustomerB2b $businessEntityCustomerB2b): self
    {
        if (!$this->businessEntityCustomerB2bs->contains($businessEntityCustomerB2b)) {
            $this->businessEntityCustomerB2bs[] = $businessEntityCustomerB2b;
            $businessEntityCustomerB2b->setCustomerB2b($this);
        }

        return $this;
    }

    public function removeBusinessEntityCustomerB2b(BusinessEntityCustomerB2b $businessEntityCustomerB2b): self
    {
        $this->businessEntityCustomerB2bs->removeElement($businessEntityCustomerB2b);

        return $this;
    }

    /**
     * @ORM\PrePersist
     *
     * @ORM\PreUpdate
     */
    public function updateTimestamps(): void
    {
        $this->updatedAt = new DateTime();

        if (!isset($this->createdAt)) {
            $this->createdAt = new DateTime();
        }
    }
}
