<?php

use Bitrix\Main\Type\Date;

/**
 * Формирует общие поля сторнирования.
 *
 * @param array $deal Данные сделки
 * @param string $date Дата сторно
 *
 * @return array
 */
private function buildStornoFields(
    array $deal,
    string $date
): array {
    if (!Date::isCorrect($date, 'd.m.Y')) {
        throw new \InvalidArgumentException(
            "Некорректная дата сторно: {$date}"
        );
    }

    return [
        'UF_CRM_PERIOD_REVERSAL' => (new Date(
            $date,
            'd.m.Y'
        ))->format('d.m.Y'),

        'UF_CRM_STORNO_POLICY_NUMBER' =>
            $deal['UF_CRM_1476258865'] ?? null,

        'UF_CRM_STORNO_DEAL_KV_PERCENT' =>
            (float)($deal['UF_CRM_1476258847'] ?? 0),

        'UF_CRM_STORNO_DEAL_KV' =>
            (float)($deal['OPPORTUNITY'] ?? 0),

        'UF_CRM_STORNO_DEAL_SP' =>
            (float)($deal['UF_CRM_1476258721'] ?? 0),

        'UF_CRM_STORNO_DEAL_SUM_INSURED' =>
            (float)($deal['UF_CRM_1476258434'] ?? 0),

        'UF_CRM_PARTNER_COMMENT_1' => 'Сторно',
    ];
}

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
