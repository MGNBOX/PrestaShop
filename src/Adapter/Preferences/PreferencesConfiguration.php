<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

namespace PrestaShop\PrestaShop\Adapter\Preferences;

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Configuration\DataConfigurationInterface;
use PrestaShop\PrestaShop\Core\Feature\B2BModeFeature;
use PrestaShop\PrestaShop\Core\Feature\B2CModeFeature;
use PrestaShop\PrestaShop\Core\Http\CookieOptions;
use PrestaShopBundle\Form\Admin\Configure\ShopParameters\General\PreferencesType;
use PrestaShopLogger;

/**
 * This class will provide Shop Preferences configuration.
 */
class PreferencesConfiguration implements DataConfigurationInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(
        Configuration $configuration,
    ) {
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        $b2cEnabled = $this->configuration->getBoolean(B2CModeFeature::CONFIGURATION_NAME);
        $b2bEnabled = $this->configuration->getBoolean(B2BModeFeature::CONFIGURATION_NAME);

        $shopMode = match (true) {
            $b2cEnabled && $b2bEnabled => PreferencesType::SHOP_MODE_B2B_AND_B2C,
            $b2bEnabled => PreferencesType::SHOP_MODE_B2B_ONLY,
            default => PreferencesType::SHOP_MODE_B2C_ONLY,
        };

        return [
            'enable_ssl' => $this->configuration->getBoolean('PS_SSL_ENABLED'),
            'enable_token' => $this->configuration->getBoolean('PS_TOKEN_ENABLE'),
            PreferencesType::SHOP_MODE => $shopMode,
            'allow_html_iframes' => $this->configuration->getBoolean('PS_ALLOW_HTML_IFRAME'),
            'use_htmlpurifier' => $this->configuration->getBoolean('PS_USE_HTMLPURIFIER'),
            'price_round_mode' => $this->configuration->get('PS_PRICE_ROUND_MODE'),
            'price_round_type' => $this->configuration->get('PS_ROUND_TYPE'),
            'display_suppliers' => $this->configuration->getBoolean('PS_DISPLAY_SUPPLIERS'),
            'display_manufacturers' => $this->configuration->getBoolean('PS_DISPLAY_MANUFACTURERS'),
            'display_best_sellers' => $this->configuration->getBoolean('PS_DISPLAY_BEST_SELLERS'),
            'multishop_feature_active' => $this->configuration->getBoolean('PS_MULTISHOP_FEATURE_ACTIVE'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function updateConfiguration(array $configuration)
    {
        if (false === $this->validateConfiguration($configuration)) {
            return [
                [
                    'key' => 'Invalid configuration',
                    'domain' => 'Admin.Notifications.Warning',
                    'parameters' => [],
                ],
            ];
        }

        if ($this->validateSameSiteConfiguration($configuration)) {
            return [
                [
                    'key' => 'Cannot disable SSL configuration due to the Cookie SameSite=None.',
                    'domain' => 'Admin.Advparameters.Notification',
                    'parameters' => [],
                ],
            ];
        }

        $shopMode = (string) $configuration[PreferencesType::SHOP_MODE];

        switch ($shopMode) {
            case PreferencesType::SHOP_MODE_B2C_ONLY:
                $newB2cModeValue = true;
                $newB2bModeValue = false;
                break;

            case PreferencesType::SHOP_MODE_B2B_ONLY:
                $newB2cModeValue = false;
                $newB2bModeValue = true;
                break;

            case PreferencesType::SHOP_MODE_B2B_AND_B2C:
                $newB2cModeValue = true;
                $newB2bModeValue = true;
                break;

            default:
                return [[
                    'key' => 'Invalid shop mode value.',
                    'domain' => 'Admin.Notifications.Warning',
                    'parameters' => [],
                ]];
        }

        $oldB2cModeValue = $this->configuration->getBoolean(B2CModeFeature::CONFIGURATION_NAME);
        $oldB2bModeValue = $this->configuration->getBoolean(B2BModeFeature::CONFIGURATION_NAME);

        if ($oldB2cModeValue !== $newB2cModeValue) {
            PrestaShopLogger::addLog(
                sprintf('B2C mode updated: from %s to %s', $oldB2cModeValue ? '1' : '0', $newB2cModeValue ? '1' : '0'),
                1,
                null,
                'Configuration',
                0,
                true
            );
        }

        if ($oldB2bModeValue !== $newB2bModeValue) {
            PrestaShopLogger::addLog(
                sprintf('B2B mode updated: from %s to %s', $oldB2bModeValue ? '1' : '0', $newB2bModeValue ? '1' : '0'),
                1,
                null,
                'Configuration',
                0,
                true
            );
        }

        $this->configuration->set('PS_SSL_ENABLED', $configuration['enable_ssl']);
        $this->configuration->set('PS_TOKEN_ENABLE', $configuration['enable_token']);
        $this->configuration->set(B2CModeFeature::CONFIGURATION_NAME, $newB2cModeValue);
        $this->configuration->set(B2BModeFeature::CONFIGURATION_NAME, $newB2bModeValue);
        $this->configuration->set('PS_ALLOW_HTML_IFRAME', $configuration['allow_html_iframes']);
        $this->configuration->set('PS_USE_HTMLPURIFIER', $configuration['use_htmlpurifier']);
        $this->configuration->set('PS_PRICE_ROUND_MODE', $configuration['price_round_mode']);
        $this->configuration->set('PS_ROUND_TYPE', $configuration['price_round_type']);
        $this->configuration->set('PS_DISPLAY_SUPPLIERS', $configuration['display_suppliers']);
        $this->configuration->set('PS_DISPLAY_MANUFACTURERS', $configuration['display_manufacturers']);
        $this->configuration->set('PS_DISPLAY_BEST_SELLERS', $configuration['display_best_sellers']);
        $this->configuration->set('PS_MULTISHOP_FEATURE_ACTIVE', $configuration['multishop_feature_active']);

        return [];
    }

    /**
     * Validate the SSL configuration can be disabled if the SameSite Cookie
     * is not settled to None
     *
     * @param array $configuration
     *
     * @return bool
     */
    protected function validateSameSiteConfiguration(array $configuration): bool
    {
        return $configuration['enable_ssl'] === false && $this->configuration->get('PS_COOKIE_SAMESITE') === CookieOptions::SAMESITE_NONE;
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfiguration(array $configuration)
    {
        return isset(
            $configuration['enable_ssl'],
            $configuration['enable_token'],
            $configuration[PreferencesType::SHOP_MODE],
            $configuration['allow_html_iframes'],
            $configuration['use_htmlpurifier'],
            $configuration['price_round_mode'],
            $configuration['price_round_type'],
            $configuration['display_suppliers'],
            $configuration['display_manufacturers'],
            $configuration['display_best_sellers'],
            $configuration['multishop_feature_active']
        );
    }
}
