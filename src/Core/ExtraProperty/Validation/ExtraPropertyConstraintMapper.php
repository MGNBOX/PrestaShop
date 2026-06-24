<?php

/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\ExtraProperty\Validation;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Maps the BO "Validation" card's plain-text constraint names ("one per line") to real
 * Symfony Constraint instances, and back.
 *
 * Limited to a whitelist of constraints that require no constructor option (Length, Range,
 * Regex, Choice, etc. need a dedicated per-constraint options sub-form, which is a follow-up,
 * not covered by this minimal textarea editor).
 */
class ExtraPropertyConstraintMapper
{
    private const ALLOWED_CONSTRAINTS = [
        'NotBlank' => Assert\NotBlank::class,
        'NotNull' => Assert\NotNull::class,
        'Email' => Assert\Email::class,
        'Url' => Assert\Url::class,
        'Json' => Assert\Json::class,
        'Uuid' => Assert\Uuid::class,
        'Ip' => Assert\Ip::class,
        'Positive' => Assert\Positive::class,
        'PositiveOrZero' => Assert\PositiveOrZero::class,
        'Negative' => Assert\Negative::class,
        'NegativeOrZero' => Assert\NegativeOrZero::class,
        'IsTrue' => Assert\IsTrue::class,
        'IsFalse' => Assert\IsFalse::class,
    ];

    /**
     * Parses a "one constraint name per line" textarea value into Constraint instances.
     * Unrecognized names are silently dropped (the help text lists the allowed names).
     *
     * @param string|null $rawNames
     *
     * @return list<Constraint>|null
     */
    public static function fromNames(?string $rawNames): ?array
    {
        if (null === $rawNames || '' === trim($rawNames)) {
            return null;
        }

        $constraints = [];
        foreach (explode("\n", $rawNames) as $name) {
            $name = trim($name);
            if ('' === $name || !isset(self::ALLOWED_CONSTRAINTS[$name])) {
                continue;
            }

            $fqcn = self::ALLOWED_CONSTRAINTS[$name];
            $constraints[] = new $fqcn();
        }

        return [] !== $constraints ? $constraints : null;
    }

    /**
     * Formats a list of Constraint instances back into the textarea's "one name per line" shape.
     * Constraints outside the whitelist (e.g. attached by a module directly in PHP) are skipped.
     *
     * @param list<Constraint>|null $constraints
     */
    public static function toNames(?array $constraints): ?string
    {
        if (null === $constraints || [] === $constraints) {
            return null;
        }

        $names = [];
        foreach ($constraints as $constraint) {
            $shortName = array_search($constraint::class, self::ALLOWED_CONSTRAINTS, true);
            if (false !== $shortName) {
                $names[] = $shortName;
            }
        }

        return [] !== $names ? implode("\n", $names) : null;
    }

    /**
     * @return list<string>
     */
    public static function getAllowedNames(): array
    {
        return array_keys(self::ALLOWED_CONSTRAINTS);
    }
}
