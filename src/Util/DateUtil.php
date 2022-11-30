<?php

namespace App\Util;

class DateUtil
{
    public static function formatAbbreviatedDate(\DateTimeInterface $date, string $locale): string
    {
        $fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);

        switch ($locale) {
        case 'fr-fr':
        case 'en-gb':
            $fmt->setPattern('EEE d MMM yyyy');
            break;
        case 'en-us':
            $fmt->setPattern('EEE, MMM d, yyyy');
            break;
        default:
            throw new \UnexpectedValueException(sprintf('Locale "%s" not supported.', $locale));
        }

        if ('fr-fr' === $locale) {
            return str_replace(' 1 ', ' 1er ', $fmt->format($date));
        }

        return $fmt->format($date);
    }
}
