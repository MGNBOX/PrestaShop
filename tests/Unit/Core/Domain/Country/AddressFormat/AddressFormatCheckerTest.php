<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Core\Domain\Country\AddressFormat;

use PHPUnit\Framework\TestCase;
use PrestaShop\PrestaShop\Adapter\Country\AddressFormat\LegacyAddressFormatChecker;
use PrestaShop\PrestaShop\Core\Domain\Country\AddressFormat\AddressFormatCheckerInterface;

/**
 * @group address-format
 *
 * Tests the legacy-backed implementation of AddressFormatCheckerInterface. The adapter delegates
 * to the legacy AddressFormat ObjectModel which uses reflection on Address/Customer/Country/State
 * classes — the test environment must be able to autoload those classes (full PrestaShop
 * bootstrap), which is the case for tests/Unit configured under tests/phpunit-unit.xml.
 */
class AddressFormatCheckerTest extends TestCase
{
    private AddressFormatCheckerInterface $checker;

    protected function setUp(): void
    {
        $this->checker = new LegacyAddressFormatChecker();
    }

    public function testValidFormatReturnsNoErrors(): void
    {
        $format = "firstname lastname\naddress1\npostcode city\nCountry:name";
        $errors = $this->checker->validate($format);

        $this->assertSame([], $errors, 'A format containing all required fields should validate.');
    }

    public function testFormatWithBareTokensIsValid(): void
    {
        $format = "firstname lastname\naddress1\ncity\nCountry:name";
        $errors = $this->checker->validate($format);

        $this->assertSame([], $errors);
    }

    public function testMissingRequiredFieldProducesError(): void
    {
        $format = "lastname\naddress1\ncity\nCountry:name";
        $errors = $this->checker->validate($format);

        $this->assertNotEmpty($errors, 'Missing firstname should produce a validation error.');
    }

    public function testDuplicateTokenProducesError(): void
    {
        $format = "firstname firstname\nlastname\naddress1\ncity\nCountry:name";
        $errors = $this->checker->validate($format);

        $this->assertNotEmpty($errors);
    }

    public function testUnknownFieldProducesError(): void
    {
        $format = "firstname lastname\naddress1\ncity\nCountry:name\ntotally_not_a_field";
        $errors = $this->checker->validate($format);

        $this->assertNotEmpty($errors);
    }

    public function testForbiddenClassProducesError(): void
    {
        $format = "firstname lastname\naddress1\ncity\nCountry:name\nManufacturer:name";
        $errors = $this->checker->validate($format);

        $this->assertNotEmpty($errors);
    }

    public function testEmptyFormatReturnsErrors(): void
    {
        // An empty format has no required fields → produces errors for all of them.
        $errors = $this->checker->validate('');

        $this->assertNotEmpty($errors);
    }
}
