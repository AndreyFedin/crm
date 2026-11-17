<?php



/**
 * Возвращает остаток от равномерного распределения
 * суммы между платежами с точностью до копейки.
 *
 * @param float $sum Общая сумма
 * @param int $paymentCount Количество платежей
 *
 * @return float
 */
public function getDelta(float $sum, int $paymentCount): float
{
    if ($paymentCount <= 0) {
        return 0.0;
    }

    $sumKopecks = (int) round($sum * 100);

    $paymentKopecks = (int) floor(
        $sumKopecks / $paymentCount
    );

    $deltaKopecks = $sumKopecks
        - ($paymentKopecks * $paymentCount);

    return $deltaKopecks / 100;
}

