<?php

namespace Company\FormattedNumber\UserField;

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\UserField\Types\DoubleType;
use Bitrix\Main\Web\Json;
use CUserTypeManager;

final class FormattedNumberType extends DoubleType
{
	public const USER_TYPE_ID = 'formatted_number';

	public static function getDescription(): array
	{
		return [
			'DESCRIPTION' => 'Число с разделением разрядов',
			'BASE_TYPE' => CUserTypeManager::BASE_TYPE_DOUBLE,
		];
	}

	public static function isMultiplicitySupported(): bool
	{
		return false;
	}

	public static function renderView(
		array $userField,
		?array $additionalParameters = []
	): string
	{
		$value = self::getValue($userField, $additionalParameters);

		if ($value === null || $value === '')
		{
			return '';
		}

		$normalizedValue = self::normalizeValue($value);

		if ($normalizedValue === '' || !is_numeric($normalizedValue))
		{
			return HtmlFilter::encode((string)$value);
		}

		$precision = self::getPrecision($userField);

		return HtmlFilter::encode(
			number_format(
				(float)$normalizedValue,
				$precision,
				'.',
				' '
			)
		);
	}

	public static function renderEdit(
		array $userField,
		?array $additionalParameters = []
	): string
	{
		Extension::load('company.formatted-number');

		$precision = self::getPrecision($userField);
		$value = self::getValue($userField, $additionalParameters);
		$normalizedValue = self::normalizeValue($value);

		$canonicalValue = '';
		$displayValue = '';

		if ($normalizedValue !== '' && is_numeric($normalizedValue))
		{
			$canonicalValue = number_format(
				(float)$normalizedValue,
				$precision,
				'.',
				''
			);

			$displayValue = number_format(
				(float)$normalizedValue,
				$precision,
				'.',
				' '
			);
		}

		$fieldName = (string)(
			$additionalParameters['NAME']
			?? $userField['FIELD_NAME']
			?? ''
		);

		if ($fieldName === '')
		{
			return '';
		}

		$uniqueId = 'formatted_number_' . bin2hex(random_bytes(8));
		$valueInputId = $uniqueId . '_value';
		$displayInputId = $uniqueId . '_display';

		$isRequired = ($userField['MANDATORY'] ?? 'N') === 'Y';

		$valueInputIdEscaped = HtmlFilter::encode($valueInputId);
		$displayInputIdEscaped = HtmlFilter::encode($displayInputId);
		$fieldNameEscaped = HtmlFilter::encode($fieldName);
		$canonicalValueEscaped = HtmlFilter::encode($canonicalValue);
		$displayValueEscaped = HtmlFilter::encode($displayValue);

		$requiredAttribute = $isRequired ? ' required' : '';

		$options = Json::encode([
			'displayInputId' => $displayInputId,
			'valueInputId' => $valueInputId,
			'precision' => $precision,
			'allowNegative' => true,
		]);

		return <<<HTML
<input
	type="hidden"
	id="{$valueInputIdEscaped}"
	name="{$fieldNameEscaped}"
	value="{$canonicalValueEscaped}"
	data-role="formatted-number-value"
>

<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
	<input
		type="text"
		id="{$displayInputIdEscaped}"
		class="ui-ctl-element"
		value="{$displayValueEscaped}"
		inputmode="decimal"
		autocomplete="off"
		data-role="formatted-number-display"{$requiredAttribute}
	>
</div>

<script>
BX.ready(function()
{
	if (
		BX.Company
		&& BX.Company.FormattedNumber
		&& typeof BX.Company.FormattedNumber.init === 'function'
	)
	{
		BX.Company.FormattedNumber.init({$options});
	}
});
</script>
HTML;
	}

	private static function getPrecision(array $userField): int
	{
		$precision = (int)($userField['SETTINGS']['PRECISION'] ?? 0);

		if ($precision < 0)
		{
			return 0;
		}

		if ($precision > 12)
		{
			return 12;
		}

		return $precision;
	}

	private static function getValue(
		array $userField,
		?array $additionalParameters = []
	)
	{
		if (
			is_array($additionalParameters)
			&& array_key_exists('VALUE', $additionalParameters)
		)
		{
			return $additionalParameters['VALUE'];
		}

		return $userField['VALUE'] ?? null;
	}

	private static function normalizeValue($value): string
	{
		if ($value === null || $value === '')
		{
			return '';
		}

		if (is_array($value))
		{
			$value = reset($value);
		}

		$value = trim((string)$value);

		return str_replace(
			[
				"\xC2\xA0",
				"\xE2\x80\xAF",
				' ',
				',',
			],
			[
				'',
				'',
				'',
				'.',
			],
			$value
		);
	}
}
