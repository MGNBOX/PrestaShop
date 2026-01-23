{**
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
 *}
<table id="discount-tab" width="100%">
	<tr>
		<td class="discount center small grey bold" width="44%">{l s='Discount' d='Shop.Pdf' pdf='true'}</td>
		<td class="discount left white" width="56%">
			<table width="100%" border="0">
				{assign var="shipping_discount_tax_incl" value="0"}
				{foreach from=$cart_rules item=cart_rule name="cart_rules_loop"}
					<tr>
						<td class="right small">
							{$cart_rule.name}
						</td>
						<td class="right small">
							- {displayPrice currency=$order->id_currency price=$cart_rule.value_tax_excl}
						</td>
					</tr>
				{/foreach}
			</table>
		</td>
	</tr>
</table>
