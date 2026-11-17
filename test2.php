<?php

public function calculatePaymentsCount(
    ?DateTime $dateStart,
    ?DateTime $dateEnd,
    int $period = 1
): int {
    if ($dateStart === null || $dateEnd === null) {
        return 1;
    }

    try {
        if ($period <= 0) {
            throw new \InvalidArgumentException(
                'Период должен быть больше нуля'
            );
        }

        $days = $dateStart->getDiff($dateEnd)->days;

        return max(1, intdiv($days, $period));

    } catch (\Throwable $e) {

        return 1;
    }
}