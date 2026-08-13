<?php

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$userField = $arResult['userField'] ?? [];
$additionalParameters = $arResult['additionalParameters'] ?? [];

$fieldName = (string)($userField['FIELD_NAME'] ?? '');

if ($fieldName !== 'UF_NUM')
{
	$defaultTemplate = $_SERVER['DOCUMENT_ROOT']
		. BX_ROOT
		. '/components/bitrix/main.field.double/templates/main.edit/.default.php';

	if (is_file($defaultTemplate))
	{
		require $defaultTemplate;
	}

	return;
}

if (($userField['MULTIPLE'] ?? 'N') === 'Y')
{
	$defaultTemplate = $_SERVER['DOCUMENT_ROOT']
		. BX_ROOT
		. '/components/bitrix/main.field.double/templates/main.edit/.default.php';

	if (is_file($defaultTemplate))
	{
		require $defaultTemplate;
	}

	return;
}

Extension::load('company.uf-number');

$precision = max(
	0,
	(int)($userField['SETTINGS']['PRECISION'] ?? 2)
);

$value = $userField['VALUE'] ?? '';

if (is_array($value))
{
	$value = reset($value);
}

$value = $value === null
	? ''
	: trim((string)$value);

$inputName = (string)(
	$additionalParameters['NAME']
	?? $fieldName
);

$canonicalValue = '';
$displayValue = '';

if ($value !== '')
{
	$normalizedValue = str_replace(
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

	if (is_numeric($normalizedValue))
	{
		$numericValue = (float)$normalizedValue;

		$canonicalValue = number_format(
			$numericValue,
			$precision,
			'.',
			''
		);

		$displayValue = number_format(
			$numericValue,
			$precision,
			'.',
			' '
		);
	}
}

$uniqueId = 'uf_num_' . bin2hex(random_bytes(8));

$valueInputId = $uniqueId . '_value';
$displayInputId = $uniqueId . '_display';

$isRequired = ($userField['MANDATORY'] ?? 'N') === 'Y';
?>

<input
	type="hidden"
	id="<?= HtmlFilter::encode($valueInputId) ?>"
	name="<?= HtmlFilter::encode($inputName) ?>"
	value="<?= HtmlFilter::encode($canonicalValue) ?>"
	data-role="company-uf-number-value"
>

<div class="ui-ctl ui-ctl-textbox ui-ctl-w100">
	<input
		type="text"
		id="<?= HtmlFilter::encode($displayInputId) ?>"
		class="ui-ctl-element"
		value="<?= HtmlFilter::encode($displayValue) ?>"
		inputmode="decimal"
		autocomplete="off"
		data-role="company-uf-number-display"
		<?= $isRequired ? 'required' : '' ?>
	>
</div>

<script>
BX.ready(function()
{
	if (
		typeof BX.Company === 'undefined'
		|| typeof BX.Company.UfNumber === 'undefined'
		|| typeof BX.Company.UfNumber.init !== 'function'
	)
	{
		console.error('JS extension company.uf-number is not loaded');
		return;
	}

	BX.Company.UfNumber.init({
		displayInputId: <?= Json::encode($displayInputId) ?>,
		valueInputId: <?= Json::encode($valueInputId) ?>,
		precision: <?= $precision ?>,
		allowNegative: true
	});
});
</script>
