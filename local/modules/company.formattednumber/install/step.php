<?php

if (!check_bitrix_sessid())
{
	return;
}
?>

<div class="adm-info-message-wrap">
	<div class="adm-info-message">
		Модуль установлен. Новый тип пользовательского поля:
		<strong>«Число с разделением разрядов»</strong>.
	</div>
</div>

<form action="<?= htmlspecialcharsbx($APPLICATION->GetCurPage()) ?>">
	<input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
	<input type="submit" value="Вернуться к списку модулей">
</form>
