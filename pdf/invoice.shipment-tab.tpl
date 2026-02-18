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
<table width="100%" cellpadding="4" cellspacing="0" style="margin-bottom: 5px;">
	<tr>
		<td style="font-weight: bold; font-size: 9pt; padding: 8px;">
			{$current}/{$totalShipment} - {l s='Delivered by' d='Shop.Pdf' pdf='true'} {if isset($shipment.carrierName) && $shipment.carrierName}{$shipment.carrierName}{else}{l s='Unknown carrier' d='Shop.Pdf' pdf='true'}{/if}{if isset($shipment.trackingNumber) && $shipment.trackingNumber} ({l s='Tracking number:' d='Shop.Pdf' pdf='true'} {$shipment.trackingNumber}){/if}
		</td>
	</tr>
</table>

<!-- Products Table -->
<table class="product" width="100%" cellpadding="4" cellspacing="0">

	{assign var='widthColProduct' value=$layout.product.width}
	{if !$isTaxEnabled}
		{assign var='widthColProduct' value=$widthColProduct+$layout.tax_code.width}
	{/if}
	<thead>
		<tr>
			<th class="product header small" width="{$layout.reference.width}%">{l s='Reference' d='Shop.Pdf' pdf='true'}</th>
			<th class="product header small" width="{$widthColProduct}%">{l s='Product' d='Shop.Pdf' pdf='true'}</th>
			{if $isTaxEnabled}
				<th class="product header small" width="{$layout.tax_code.width}%">{l s='Tax Rate' d='Shop.Pdf' pdf='true'}</th>
			{/if}
			{if isset($layout.before_discount)}
				<th class="product header small" width="{$layout.unit_price_tax_excl.width}%">
					{l s='Base price' d='Shop.Pdf' pdf='true'}{if $isTaxEnabled}<br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true'}{/if}
				</th>
			{/if}

			<th class="product header-right small" width="{$layout.unit_price_tax_excl.width}%">
				{l s='Unit Price' d='Shop.Pdf' pdf='true'}{if $isTaxEnabled}<br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true'}{/if}
			</th>
			<th class="product header small" width="{$layout.quantity.width}%">{l s='Qty' d='Shop.Pdf' pdf='true'}</th>
			<th class="product header-right small" width="{$layout.total_tax_excl.width}%">
				{l s='Total' d='Shop.Pdf' pdf='true'}{if $isTaxEnabled}<br /> {l s='(Tax excl.)' d='Shop.Pdf' pdf='true'}{/if}
			</th>
		</tr>
	</thead>

	<tbody>
		{foreach $shipment['products'] as $product}
			{cycle values=["color_line_even", "color_line_odd"] assign=bgcolor_class}
			<tr class="product {$bgcolor_class}">

				<td class="product center">
					{$product.product_reference}
				</td>
				<td class="product left">
					{if $display_product_images}
						<table width="100%">
							<tr>
								<td width="15%">
									{if isset($product.image) && $product.image->id}
										{$product.image_tag}
									{/if}
								</td>
								<td width="5%">&nbsp;</td>
								<td width="80%">
									{$product.product_name}
								</td>
							</tr>
						</table>
					{else}
						{$product.product_name}
					{/if}

				</td>
				{if $isTaxEnabled}
					<td class="product center">
						{$product.order_detail_tax_label}
					</td>
				{/if}

				{if isset($layout.before_discount)}
					<td class="product center">
						{if isset($product.unit_price_tax_excl_before_specific_price)}
							{displayPrice currency=$order->id_currency price=$product.unit_price_tax_excl_before_specific_price}
						{else}
							--
						{/if}
					</td>
				{/if}

				<td class="product right">
					{displayPrice currency=$order->id_currency price=$product.unit_price_tax_excl_including_ecotax}
					{if $product.ecotax_tax_excl > 0}
						<br>
						<small>{{displayPrice currency=$order->id_currency price=$product.ecotax_tax_excl}|string_format:{l s='ecotax: %s' d='Shop.Pdf' pdf='true'}}</small>
					{/if}
				</td>
				<td class="product center">
					{$product.product_quantity}
				</td>
				<td  class="product right">
					{displayPrice currency=$order->id_currency price=$product.total_price_tax_excl_including_ecotax}
				</td>
			</tr>

			{foreach $product.customizedDatas as $customizationPerAddress}
				{foreach $customizationPerAddress as $customizationId => $customization}
					<tr class="customization_data {$bgcolor_class}">
						<td class="center"> &nbsp;</td>

						<td>
							{if isset($customization.datas[Product::CUSTOMIZE_TEXTFIELD]) && count($customization.datas[Product::CUSTOMIZE_TEXTFIELD]) > 0}
								<table style="width: 100%;">
									{foreach $customization.datas[Product::CUSTOMIZE_TEXTFIELD] as $customization_infos}
										<tr>
											<td>{$customization_infos.name|escape:'html':'UTF-8'|string_format:{l s='%s:' d='Shop.Pdf' pdf='true'}} {if (int)$customization_infos.id_module}{$customization_infos.value nofilter}{else}{$customization_infos.value}{/if}</td>
										</tr>
									{/foreach}
								</table>
							{/if}

							{if isset($customization.datas[Product::CUSTOMIZE_FILE]) && count($customization.datas[Product::CUSTOMIZE_FILE]) > 0}
								<table style="width: 100%;">
									<tr>
										<td style="width: 70%;">{l s='image(s):' d='Shop.Pdf' pdf='true'}</td>
										<td>{count($customization.datas[Product::CUSTOMIZE_FILE])}</td>
									</tr>
								</table>
							{/if}
						</td>

						<td class="center"></td>

						{assign var=end value=($layout._colCount-3)}
						{for $var=0 to $end}
							<td class="center"></td>
						{/for}

					</tr>
				{/foreach}
			{/foreach}
		{/foreach}
	</tbody>
</table>

<!-- Spacing between shipments -->
<table width="100%" cellpadding="0" cellspacing="0">
	<tr>
		<td height="20">&nbsp;</td>
	</tr>
</table>
