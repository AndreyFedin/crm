<?php


use Bitrix\Main\Type\DateTime;

public function getSchedule(
        ?DateTime $dateStart,
        int $count,
        int $period,
        array $currentNumbers = []
): array {
    if ($dateStart === null || $count <= 0) {
        return [];
    }

    $schedule = [];

    try {
        if ($period <= 0) {
            throw new \InvalidArgumentException(
                    'Период должен быть больше нуля'
            );
        }

        $date = clone $dateStart;

        $interval = match ($period) {
            30 => '+1 month',
            90 => '+3 months',
            180 => '+6 months',
            360 => '+12 months',
            default => "+{$period} days",
        };

        $excludedNumbers = array_fill_keys(
                array_map('intval', $currentNumbers),
                true
        );

        $number = 1;

        while (count($schedule) < $count) {
            if (!isset($excludedNumbers[$number])) {
                $schedule[$number] = $date->format('d.m.Y');
                $date->add($interval);
            }

            $number++;
        }

    } catch (\Throwable $e) {
        $this->obLogger->exception($e);
    }

    return $schedule;
}
