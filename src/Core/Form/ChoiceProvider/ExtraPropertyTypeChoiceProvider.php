<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Form\ChoiceProvider;

use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyType;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides human-readable choices for ExtraPropertyType, shared by the extra property
 * definition form and grid filter so the translated labels are not duplicated.
 */
final class ExtraPropertyTypeChoiceProvider implements FormChoiceProviderInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getChoices(): array
    {
        return [
            $this->trans('Integer') => ExtraPropertyType::INT->value,
            $this->trans('Boolean') => ExtraPropertyType::BOOL->value,
            $this->trans('Text') => ExtraPropertyType::STRING->value,
            $this->trans('Decimal number') => ExtraPropertyType::FLOAT->value,
            $this->trans('Date') => ExtraPropertyType::DATE->value,
            $this->trans('Rich text (HTML)') => ExtraPropertyType::HTML->value,
            $this->trans('JSON') => ExtraPropertyType::JSON->value,
            $this->trans('Choice list') => ExtraPropertyType::CHOICE->value,
        ];
    }

    private function trans(string $key): string
    {
        return $this->translator->trans($key, [], 'Admin.Advparameters.Feature');
    }
}
