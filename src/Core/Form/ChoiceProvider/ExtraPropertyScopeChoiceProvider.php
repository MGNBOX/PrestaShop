<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Form\ChoiceProvider;

use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertyScope;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides human-readable choices for ExtraPropertyScope, shared by the extra property
 * definition form and grid filter so the translated labels are not duplicated.
 */
final class ExtraPropertyScopeChoiceProvider implements FormChoiceProviderInterface
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
            $this->trans('Common (one value per entity)') => ExtraPropertyScope::COMMON->value,
            $this->trans('Per language') => ExtraPropertyScope::LANG->value,
            $this->trans('Per shop') => ExtraPropertyScope::SHOP->value,
        ];
    }

    private function trans(string $key): string
    {
        return $this->translator->trans($key, [], 'Admin.Advparameters.Feature');
    }
}
