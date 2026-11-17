<?php


use Bitrix\Main\Type\DateTime;

/**
 * Добавляет платёж по сделке, создаёт задачу и уведомление.
 *
 * @param array $arDeal Данные сделки
 * @param array $arSums Данные суммы платежа
 * @param int $number Номер платежа
 * @param string $dateModify Дата платежа
 *
 * @return bool
 */
public function addPaymentsByDeal(
        array $arDeal,
        array $arSums,
        int $number = 1,
        string $dateModify = ''
): bool {
    try {
        $dateModify = trim($dateModify);

        $date = $this->preparePaymentDate($dateModify);

        $fields = $this->buildPaymentFields(
                $arDeal,
                $arSums,
                $number,
                $date
        );

        if (!$this->savePayment($fields, (int)$arDeal['ID'], $number)) {
            return false;
        }

        if ($dateModify !== '') {
            $this->createPaymentTask($arDeal, $number, $date);
        }

        self::addNotify($arDeal, $dateModify, $number);

        $this->obLogger->info('Платёж добавлен', [
                'dealId' => $arDeal['ID'],
                'number' => $number,
                'date' => $dateModify,
        ]);

        return true;

    } catch (\Throwable $e) {
        $this->obLogger->exception($e);

        return false;
    }
}

/**
 * Подготавливает дату платежа.
 */
private function preparePaymentDate(string $date): DateTime
{
    if ($date === '') {
        return new DateTime();
    }

    if (DateTime::isCorrect($date, 'd.m.Y')) {
        return new DateTime($date . ' 00:00:00', 'd.m.Y H:i:s');
    }

    if (!DateTime::isCorrect($date, 'd.m.Y H:i:s')) {
        throw new \InvalidArgumentException(
                'Некорректная дата платежа: ' . $date
        );
    }

    return new DateTime($date, 'd.m.Y H:i:s');
}

/**
 * Формирует поля платежа.
 */
private function buildPaymentFields(
        array $arDeal,
        array $arSums,
        int $number,
        DateTime $date
): array {
    $dealId = (int)$arDeal['ID'];

    return [
            'UF_CRM_DATE_EXPECTED' => $date,
            'UF_CRM_SUM' => $arSums['SUM'],
            'UF_CRM_INDEX' => $number,

            $this->obFactory->getParentField(
                    \CCrmOwnerType::DealName
            ) => $dealId,

            'CATEGORY_ID' => $this->obFactory
                    ->getCategoryIdByDealId($dealId),

            'ASSIGNED_BY_ID' => $arDeal['ASSIGNED_BY_ID'],
            'CREATED_BY' => $this->userId,
    ];
}

/**
 * Сохраняет платёж.
 */
private function savePayment(
        array $fields,
        int $dealId,
        int $number
): bool {
    $result = $this->addPayment($fields);

    if ($result->isSuccess()) {
        return true;
    }

    $this->obLogger->error('Ошибка добавления платежа', [
            'dealId' => $dealId,
            'number' => $number,
            'errors' => $result->getErrorMessages(),
    ]);

    return false;
}

/**
 * Создаёт задачу-напоминание по платежу.
 */
private function createPaymentTask(
        array $arDeal,
        int $number,
        DateTime $date
): void {
    $dateFormatted = $date->format('d.m.Y H:i:s');

    $task = new Task();

    $task->createTask([
            'TITLE' => sprintf(
                    'Платеж по сделке %s_%d',
                    $arDeal['TITLE'],
                    $number
            ),
            'RESPONSIBLE_ID' => $arDeal['ASSIGNED_BY_ID'],
            'DEADLINE' => $dateFormatted,
            'START_DATE_PLAN' => $dateFormatted,
            'UF_CRM_TASK' => [
                    'D_' . $arDeal['ID'],
            ],
            'TAGS' => ['crm'],
    ]);
}
