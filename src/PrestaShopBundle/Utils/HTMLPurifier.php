<?php
/**
 * For the full copyright and license information, please view the
 * docs/licenses/LICENSE.txt file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PrestaShopBundle\Utils;

use HTMLPurifier_Config;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HTMLPurifier
{
    /**
     * @var \HTMLPurifier
     */
    private $instance;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $config = HTMLPurifier_Config::createDefault();
        // We must keep IDs that are by JS used to target element
        $config->set('Attr.EnableID', true);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        $legacyCacheDir = $parameterBag->get('prestashop.legacy_cache_dir');
        $config->set('Cache.SerializerPath', $legacyCacheDir . 'purifier');

        $purifier = new \HTMLPurifier($config);
        $this->instance = $purifier;
    }

    /**
     * Filters an HTML snippet/document to be XSS-free and standards-compliant.
     *
     * @param string $html String of HTML to purify
     *
     * @return string Purified HTML
     */
    public function purify($html)
    {
        return $this->instance->purify($html);
    }
}
