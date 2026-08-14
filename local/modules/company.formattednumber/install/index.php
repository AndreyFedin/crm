<?php

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Company\FormattedNumber\UserField\FormattedNumberType;

class company_formattednumber extends CModule
{
	public $MODULE_ID = 'company.formattednumber';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME = 'Форматированное числовое поле';
	public $MODULE_DESCRIPTION = 'Пользовательский тип поля: число с визуальным разделением групп разрядов.';

	public function __construct()
	{
		$version = [];
		include __DIR__ . '/version.php';

		if (isset($arModuleVersion) && is_array($arModuleVersion))
		{
			$version = $arModuleVersion;
		}

		$this->MODULE_VERSION = (string)($version['VERSION'] ?? '1.0.0');
		$this->MODULE_VERSION_DATE = (string)($version['VERSION_DATE'] ?? '');
	}

	public function DoInstall(): void
	{
		global $APPLICATION;

		RegisterModule($this->MODULE_ID);

		EventManager::getInstance()->registerEventHandler(
			'main',
			'OnUserTypeBuildList',
			$this->MODULE_ID,
			FormattedNumberType::class,
			'getUserTypeDescription'
		);

		$this->InstallFiles();

		$APPLICATION->IncludeAdminFile(
			'Установка модуля ' . $this->MODULE_NAME,
			__DIR__ . '/step.php'
		);
	}

	public function DoUninstall(): void
	{
		global $APPLICATION;

		EventManager::getInstance()->unRegisterEventHandler(
			'main',
			'OnUserTypeBuildList',
			$this->MODULE_ID,
			FormattedNumberType::class,
			'getUserTypeDescription'
		);

		$this->UnInstallFiles();
		UnRegisterModule($this->MODULE_ID);

		$APPLICATION->IncludeAdminFile(
			'Удаление модуля ' . $this->MODULE_NAME,
			__DIR__ . '/unstep.php'
		);
	}

	public function InstallFiles(): bool
	{
		CopyDirFiles(
			__DIR__ . '/js',
			Application::getDocumentRoot() . '/local/js',
			true,
			true
		);

		return true;
	}

	public function UnInstallFiles(): bool
	{
		DeleteDirFilesEx('/local/js/company/formatted-number');

		return true;
	}
}
