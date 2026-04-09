---
name: register-behat-context
brick: "—"
component: BehatTesting
step: 3
needs: [B2]
produces: "behat.yml or suite configuration updated with new context class"
conditional: false
---

# register-behat-context

## Description
Register the new `{Domain}FeatureContext` in the Behat suite configuration so the step definitions are discovered. Without this step, all step definitions in the new context class are invisible to Behat.

## Context
- **Brick:** — — Step 3
- **Reads from:** B2 (context class FQCN)
- **Writes to:** — (enables all B3–B6 scenarios to run)
- **Artifact:** `tests/Integration/Behaviour/behat.yml` or suite config (edit)
- **PS example:** Check how CarrierFeatureContext is registered in behat.yml

## Instructions

1. Open `tests/Integration/Behaviour/behat.yml`.
2. Find the correct suite (usually `domain` suite).
3. Add the context class: `- PrestaShop\Tests\Integration\Behaviour\Features\Context\Domain\{Domain}FeatureContext`.
4. Verify the feature file path is discoverable by the suite's `paths` configuration.
5. Run `php vendor/bin/behat --dry-run` to confirm all steps are matched.

## Rules

- Add to the correct suite — not the default suite
- Run dry-run before running actual tests to catch unimplemented steps
- Context class FQCN must match the file path exactly (PSR-4)
