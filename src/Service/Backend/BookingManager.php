<?php

namespace App\Service\Backend;

use App\Entity\Booking;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Translation\TranslatorInterface;

class BookingManager
{
    private $doctrine;
    private $translator;

    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    public function validateOverlappings(Booking $booking, string $locale, string &$message): bool
    {
        $bookings = $this->doctrine
            ->getRepository(Booking::class)
            ->getOverlappingBookings($booking);

        if (!$bookings) {
            return true;
        }

        $fmt = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::NONE);

        $chunks = array_map(function($booking) use ($fmt) {
            return $this->translator->trans(
                'booking.id_from_to', [
                    'id' => $booking->getId(),
                    'start_date' => $fmt->format($booking->getStartDate()),
                    'end_date' => $fmt->format($booking->getEndDate()),
                ],
                'validators',
                $fmt->getLocale()
            );
        }, $bookings);

        $list = function(array $chunks, string $separator) {
            return 1 === ($count = count($chunks))
                ? $chunks[0]
                : implode(', ', array_slice($chunks, 0, $count - 1))
                    .' '.$separator.' '.$chunks[$count - 1]
            ;
        };

        $message = $this->translator->trans('booking.overlaps_with', [], 'validators', $locale)
            .' '.$list($chunks, $this->translator->trans('and', [], 'validators', $locale)).'.';

        return false;
    }
}
