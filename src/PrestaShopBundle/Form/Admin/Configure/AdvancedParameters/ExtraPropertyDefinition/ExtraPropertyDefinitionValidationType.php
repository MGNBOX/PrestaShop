<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShopBundle\Form\Admin\Configure\AdvancedParameters\ExtraPropertyDefinition;

use PrestaShop\PrestaShop\Core\ExtraProperty\Validation\ExtraPropertyConstraintMapper;
use PrestaShopBundle\Form\Admin\Type\CardType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * "Validation" card: Symfony Constraint(s) applied to the value before persistence.
 *
 * Limited to a whitelist of constraints that take no constructor option (see
 * ExtraPropertyConstraintMapper) — constraints needing options (Length, Range, Regex…) are not
 * configurable from this minimal textarea and must be attached by a module directly in PHP.
 */
class ExtraPropertyDefinitionValidationType extends TranslatorAwareType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('constraints', TextareaType::class, [
            'label' => $this->trans('Constraints', 'Admin.Advparameters.Feature'),
            'help' => $this->trans('One constraint per line, applied before persistence. Allowed: %names%. Leave empty to skip validation.', 'Admin.Advparameters.Help', ['%names%' => implode(', ', ExtraPropertyConstraintMapper::getAllowedNames())]),
            'required' => false,
            'attr' => ['rows' => 3],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->trans('Validation', 'Admin.Global'),
            'icon' => 'check_circle',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return CardType::class;
    }
}
