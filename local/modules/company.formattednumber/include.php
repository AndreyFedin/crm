<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses(
	'company.formattednumber',
	[
		'Company\\FormattedNumber\\UserField\\FormattedNumberType'
			=> 'lib/userfield/formattednumbertype.php',
	]
);
