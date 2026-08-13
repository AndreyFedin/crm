<?php

use Bitrix\Main\Text\HtmlFilter;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$userField = $arResult['userField'] ?? [];
$fieldName = (string)($userField['FIELD_NAME'] ?? '');

if ($fieldName !== 'UF_NUM')
{
	$defaultTemplate = $_SERVER['DOCUMENT_ROOT']
		. BX_ROOT
		. '/components/bitrix/main.field.double/templates/main.view/.default.php';

	if (is_file($defaultTemplate))
	{
		require $defaultTemplate;
	}

	return;
}

$value = $userField['VALUE'] ?? null;

if (is_array($value))
{
	$value = reset($value);
}

if ($value === null || $value === '')
{
	return;
}

$precision = max(
	0,
	(int)($userField['SETTINGS']['PRECISION'] ?? 2)
);

$value = trim((string)$value);

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

if (!is_numeric($normalizedValue))
{
	echo HtmlFilter::encode($value);
	return;
}

$formattedValue = number_format(
	(float)$normalizedValue,
	$precision,
	'.',
	' '
);

echo HtmlFilter::encode($formattedValue);
