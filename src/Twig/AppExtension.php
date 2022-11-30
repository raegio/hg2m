<?php

namespace App\Twig;

use App\Util\DateUtil;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('abbreviated_date', [DateUtil::class, 'formatAbbreviatedDate']),
        ];
    }
}
