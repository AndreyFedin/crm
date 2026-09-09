<?php
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