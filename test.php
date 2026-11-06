<?php

use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

function getMinDate(?Date $date1, ?Date $date2): ?DateTime
{
    $dates = array_filter([$date1, $date2]);

    if (empty($dates)) {
        return null;
    }

    $timestamps = array_map(
        static fn(Date $date): int => (new DateTime(
            $date->format('d.m.Y') . ' 00:00:00',
            'd.m.Y H:i:s'
        ))->getTimestamp(),
        $dates
    );

    return DateTime::createFromTimestamp(min($timestamps));
}


function findContactId(string $fio, string $email): ?int
{
    if (!\Bitrix\Main\Loader::includeModule('crm')) {
        throw new \RuntimeException('Не удалось подключить модуль CRM');
    }

    $fio = trim($fio);
    $email = trim($email);

    if ($fio === '' || $email === '') {
        return null;
    }

    $fioParts = preg_split('/\s+/u', $fio);

    $lastName = trim((string)($fioParts[0] ?? ''));
    $name = trim((string)($fioParts[1] ?? ''));
    $secondName = trim((string)($fioParts[2] ?? ''));

    if ($lastName === '' || $name === '') {
        return null;
    }

    $filter = [
        '=LAST_NAME' => $lastName,
        '=NAME' => $name,
        '=UF_CRM_CONTACT_EMAIL' => $email,
    ];

    if ($secondName !== '') {
        $filter['=SECOND_NAME'] = $secondName;
    }

    $result = \CCrmContact::GetListEx(
        ['ID' => 'ASC'],
        $filter,
        false,
        [
            'nTopCount' => 1,
        ],
        [
            'ID',
        ]
    );

    $contact = $result->Fetch();

    return $contact !== false
        ? (int)$contact['ID']
        : null;
}