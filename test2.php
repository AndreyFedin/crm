<?php

/**
 * Полное сторнирование платежа.
 *
 * @param int $paymentId ID платежа
 * @param string $date Дата сторно (d.m.Y)
 *
 * @return array
 */
public function stornoPayment(int $paymentId, string $date): array
{
    $fields = [];

    try {
        [$item, $payment, $deal] = $this->getStornoContext($paymentId);

        $fields = $this->buildStornoFields($deal, $date);

        $item->setFromCompatibleData($fields);

        $this->saveStornoItem(
            $item,
            $fields,
            $paymentId,
            'Полное сторно'
        );

    } catch (\Throwable $e) {
        $this->obLogger->exception($e);
    }

    return $fields;
}

/**
 * Получает платёж и связанную сделку.
 *
 * @param int $paymentId
 *
 * @return array{0: \Bitrix\Crm\Item, 1: array, 2: array}
 */
private function getStornoContext(int $paymentId): array
{
    $item = $this->obFactory->getItem($paymentId);

    if ($item === null) {
        throw new \RuntimeException(
            "Платёж {$paymentId} не найден"
        );
    }

    $payment = $item->getData();

    $parentField = $this->obFactory->getParentField(
        \CCrmOwnerType::DealName
    );

    $dealId = (int)($payment[$parentField] ?? 0);

    if ($dealId <= 0) {
        throw new \RuntimeException(
            "У платежа {$paymentId} отсутствует привязка к сделке"
        );
    }

    $deal = Deal::getByID($dealId);

    if (!is_array($deal) || empty($deal)) {
        throw new \RuntimeException(
            "Сделка {$dealId} не найдена"
        );
    }

    return [$item, $payment, $deal];
}

/**
 * Получает платёж и связанную сделку.
 *
 * @param int $paymentId
 *
 * @return array{0: \Bitrix\Crm\Item, 1: array, 2: array}
 */
private function getStornoContext(int $paymentId): array
{
    $item = $this->obFactory->getItem($paymentId);

    if ($item === null) {
        throw new \RuntimeException(
            "Платёж {$paymentId} не найден"
        );
    }

    $payment = $item->getData();

    $parentField = $this->obFactory->getParentField(
        \CCrmOwnerType::DealName
    );

    $dealId = (int)($payment[$parentField] ?? 0);

    if ($dealId <= 0) {
        throw new \RuntimeException(
            "У платежа {$paymentId} отсутствует привязка к сделке"
        );
    }

    $deal = Deal::getByID($dealId);

    if (!is_array($deal) || empty($deal)) {
        throw new \RuntimeException(
            "Сделка {$dealId} не найдена"
        );
    }

    return [$item, $payment, $deal];
}

/**
 * Получает поля исходного платежа
 * для создания частичного сторно.
 *
 * @param array $payment
 *
 * @return array
 */
private function getPartialStornoFields(array $payment): array
{
    $copyFields = [
        'TITLE',
        'CATEGORY_ID',
        'STAGE_ID',
        'UF_CRM_DATE_PAYED',
        'UF_CRM_DATE_EXPECTED',
        'ASSIGNED_BY_ID',
        'UF_CRM_STORNO_COLLATION_ID',
        'UF_CRM_ACT_DATE',
        'UF_CRM_ACT_NUMBER',
        'UF_CRM_STORNO_ACT_NUMBER',
        'UF_CRM_STORNO_ACT_DATE',
        'UF_CRM_INDEX',

        $this->obFactory->getParentField(
            \CCrmOwnerType::DealName
        ),
    ];

    $fields = array_intersect_key(
        $payment,
        array_fill_keys($copyFields, true)
    );

    foreach ($fields as $key => $value) {
        if ($value instanceof Date) {
            $fields[$key] = $value->format('d.m.Y');
        }
    }

    return $fields;
}

/**
 * Сохраняет платёж и логирует результат.
 *
 * @param \Bitrix\Crm\Item $item
 * @param array $fields
 * @param int $paymentId ID исходного платежа
 * @param string $operation Название операции
 *
 * @return bool
 */
private function saveStornoItem(
    \Bitrix\Crm\Item $item,
    array $fields,
    int $paymentId,
    string $operation
): bool {
    $result = $item->fullSave();

    if (!$result->isSuccess()) {
        $this->obLogger->error(
            'Ошибка сторнирования платежа',
            [
                'paymentId' => $paymentId,
                'operation' => $operation,
                'errors' => $result->getErrorMessages(),
                'fields' => $fields,
            ]
        );

        return false;
    }

    $this->obLogger->debug(
        'Сторнирование платежа выполнено',
        [
            'paymentId' => $paymentId,
            'operation' => $operation,
            'stornoPaymentId' => $item->getId(),
        ]
    );

    return true;
}
