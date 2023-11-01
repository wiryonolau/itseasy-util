<?php

declare(strict_types=1);

namespace Itseasy\Model\Plugin;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Throwable;

class DateToObjectPlugin implements PluginInterface
{
    public static $name = "dateToObject";

    // Common date format
    private $dateFormat = [
        "Y-m-d H:i:s",
        "Y-m-d",
        DateTimeInterface::W3C
    ];

    public function getName(): string
    {
        return self::$name;
    }

    public function __invoke(
        $date = null,
        $timezone = "UTC",
        string $format = "Y-m-d H:i:s",
        bool $immutable = false
    ): DateTimeInterface {
        $dateClass = ($immutable ? DateTimeImmutable::class : DateTime::class);

        if ($date instanceof DateTimeInterface) {
            return $date;
        }

        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        if (empty($date)) {
            return new $dateClass("now", $timezone);
        }

        return $this->createFromFormat(
            $date,
            $timezone,
            $format,
            $dateClass
        );
    }

    /**
     * Retry creating date object using string and multiple format
     */
    private function createFromFormat(
        string $dateString,
        DateTimeZone $timezone,
        string $priorityFormat = "Y-m-d H:i:s",
        string $dateClass = DateTime::class
    ): DateTimeInterface {
        $dateFormat = array_merge([$priorityFormat], $this->dateFormat);
        $dateObject = null;

        foreach ($dateFormat as $format) {
            try {
                $dateObject = $dateClass::createFromFormat($format, $dateString, $timezone);
                if ($dateObject instanceof DateTimeInterface) {
                    return $dateObject;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        throw new Exception("Invalid date format given");
    }
}
