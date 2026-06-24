<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Form\ChoiceProvider;

use PrestaShop\PrestaShop\Core\ExtraProperty\Definition\ExtraPropertySqlIndex;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides human-readable choices for ExtraPropertySqlIndex, shared by the extra property
 * definition form and (potentially) grid filters so the translated labels are not duplicated.
 */
final class ExtraPropertySqlIndexChoiceProvider implements FormChoiceProviderInterface
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
            $this->trans('No index') => ExtraPropertySqlIndex::NONE->value,
            $this->trans('Standard index') => ExtraPropertySqlIndex::KEY->value,
            $this->trans('Unique index') => ExtraPropertySqlIndex::UNIQUE->value,
        ];
    }

    private function trans(string $key): string
    {
        return $this->translator->trans($key, [], 'Admin.Advparameters.Feature');
    }
}
