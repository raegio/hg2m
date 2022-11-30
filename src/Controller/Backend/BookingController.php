<?php

namespace App\Controller\Backend;

use App\Entity\Booking;
use App\Form\Backend\BookingFilterFormType;
use App\Form\Backend\BookingFormType;
use App\Model\Backend\BookingFilter;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class BookingController extends AbstractController
{
    public const LIST_LENGTH = 6;

    private $doctrine;
    private $translator;
    private $now;

    public function __construct(ManagerRegistry $doctrine, TranslatorInterface $translator)
    {
        $this->doctrine = $doctrine;
        $this->translator = $translator;
        $this->now = new \DateTimeImmutable;
    }

    private static function getFilter(Request $request = null)
    {
        $filter = new BookingFilter;

        if ($request && $request->query->has('bookings_filter')) {
            $filter->fromArray($request->query->all()['bookings_filter']);
        }
        else {
            $filter->setStatus(pow(2, count(Booking::$STATUSES)) - 1);
        }

        return $filter;
    }

    private function getNewPage($repository, int $id, BookingFilter &$filter)
    {
        $newPage = $repository->getPage(self::LIST_LENGTH, $id, $filter, $this->now);

        if (!$newPage) {
            $filter = self::getFilter();
            $newPage = $repository->getPage(self::LIST_LENGTH, $id, $filter, $this->now);
            $this->addFlash(
                'info',
                $this->translator->trans('bookings_filter.reset', [], 'admin')
            );
        }

        return $newPage;
    }

    #[Route('/bookings', name: 'app_backend_booking_index', methods: ['GET'])]
    public function index(): Response
    {
        $filter = self::getFilter();
        $form = $this->createForm(BookingFilterFormType::class, $filter, [
            'parameters' => $this->getParameter('bookings_parameters'),
        ]);

        $repository = $this->doctrine->getRepository(Booking::class);
        $bookingsCount = $repository->countBookings($filter, $this->now);
        $lastPage = $bookingsCount ? ceil($bookingsCount / self::LIST_LENGTH) : 1;
        $page = 1;
        $rows = $repository->paginateRows(self::LIST_LENGTH, $page, $filter, $this->now);

        return $this->render('backend/booking/index.html.twig', [
            'booking_statuses' => array_flip(Booking::$STATUSES),
            'bookings_filter' => $filter,
            'bookings_page' => $page,
            'bookings_last_page' => $lastPage,
            'bookings_count' => $bookingsCount,
            'rows' => $rows,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bookings/{page}/list', name: 'app_backend_booking_list', requirements: ['page' => '[1-9]\d*'], methods: ['GET', 'POST'])]
    public function list(Request $request, int $page): Response
    {
        if ($request->isMethod('post')) {
            $filter = new BookingFilter;
        }
        else {
            $filter = self::getFilter($request);
        }

        $form = $this->createForm(BookingFilterFormType::class, $filter, [
            'parameters' => $this->getParameter('bookings_parameters'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirectToRoute('app_backend_booking_list', [
                'page' => 1,
                'bookings_filter' => $filter->toArray(),
            ]);
        }

        $repository = $this->doctrine->getRepository(Booking::class);

        $bookingsCount = $repository->countBookings($filter, $this->now);
        $lastPage = $bookingsCount ? ceil($bookingsCount / self::LIST_LENGTH) : 1;

        if ($page > $lastPage) {
            throw $this->createNotFoundException();
        }

        $rows = $repository->paginateRows(self::LIST_LENGTH, $page, $filter, $this->now);

        return $this->render('backend/booking/index.html.twig', [
            'booking_statuses' => array_flip(Booking::$STATUSES),
            'bookings_filter' => $filter,
            'bookings_page' => $page,
            'bookings_last_page' => $lastPage,
            'bookings_count' => $bookingsCount,
            'rows' => $rows,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bookings/{page}/create', name: 'app_backend_booking_create', requirements: ['page' => '[1-9]\d*'], methods: ['GET', 'POST'])]
    public function create(Request $request, int $page): Response
    {
        $filter = self::getFilter($request);
        $booking = new Booking;
        $form = $this->createForm(BookingFormType::class, $booking);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();

            $em->persist($booking);
            $em->flush();

            $repository = $this->doctrine->getRepository(Booking::class);
            $id = $booking->getId();
            $newPage = $this->getNewPage($repository, $id, $filter);

            $this->addFlash(
                'success',
                $this->translator->trans('booking.inserted', ['id' => $id], 'admin')
            );

            return $this->redirectToRoute('app_backend_booking_edit', [
                'page' => $newPage,
                'id' => $id,
                'bookings_filter' => $filter->toArray(),
            ]);
        }
        else if ($form->isSubmitted()) {
            $this->addFlash(
                'danger',
                $this->translator->trans('booking.not_inserted', [], 'admin')
            );
        }

        return $this->render('backend/booking/create_modal.html.twig', [
            'bookings_filter' => $filter,
            'bookings_page' => $page,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bookings/{page}/edit/{id}', name: 'app_backend_booking_edit', requirements: ['page' => '[1-9]\d*', 'id' => '[1-9]\d*'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $page, int $id): Response
    {
        $filter = self::getFilter($request);
        $repository = $this->doctrine->getRepository(Booking::class);
        $booking = $repository->find($id);

        if (!$booking) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(BookingFormType::class, $booking);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->doctrine->getManager();

            $em->persist($booking);
            $em->flush();

            $newPage = $this->getNewPage($repository, $id, $filter);

            $this->addFlash(
                'success',
                $this->translator->trans('booking.updated', ['id' => $id], 'admin')
            );

            return $this->redirectToRoute('app_backend_booking_edit', [
                'page' => $newPage,
                'id' => $id,
                'bookings_filter' => $filter->toArray(),
            ]);
        }
        else if ($form->isSubmitted()) {
            $this->addFlash(
                'danger',
                $this->translator->trans('booking.not_updated', ['id' => $id], 'admin')
            );
        }

        return $this->render('backend/booking/edit_modal.html.twig', [
            'bookings_filter' => $filter,
            'bookings_page' => $page,
            'booking_id' => $id,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bookings/{page}/delete/{id}', name: 'app_backend_booking_requestDeletion', requirements: ['page' => '[1-9]\d*', 'id' => '[1-9]\d*'], methods: ['GET'])]
    public function requestDeletion(Request $request, int $page, int $id): Response
    {
        $filter = self::getFilter($request);
        $booking = $this->doctrine->getRepository(Booking::class)->find($id);

        if (!$booking) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder(null, ['translation_domain' => 'admin'])
            ->setMethod('DELETE')
            ->add('cancel', ButtonType::class, ['label' => 'action.cancel'])
            ->add('delete', ButtonType::class, ['label' => 'action.delete'])
            ->getForm();

        return $this->render('backend/booking/delete_modal.html.twig', [
            'bookings_filter' => $filter,
            'bookings_page' => $page,
            'booking' => $booking,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/bookings/{page}/delete/{id}', name: 'app_backend_booking_delete', requirements: ['page' => '[1-9]\d*', 'id' => '[1-9]\d*'], methods: ['DELETE'])]
    public function delete(Request $request, int $page, int $id): Response
    {
        $filter = self::getFilter($request);
        $repository = $this->doctrine->getRepository(Booking::class);
        $booking = $repository->find($id);

        if (!$booking) {
            throw $this->createNotFoundException();
        }

        $em = $this->doctrine->getManager();

        $em->remove($booking);
        $em->flush();

        $this->addFlash(
            'success',
            $this->translator->trans('booking.deleted', ['id' => $id], 'admin')
        );

        $bookingsCount = $repository->countBookings($filter, $this->now);
        $lastPage = $bookingsCount ? ceil($bookingsCount / self::LIST_LENGTH) : 1;
        $newPage = $page > $lastPage ? $lastPage : $page;

        return $this->redirectToRoute('app_backend_booking_index', [
            'page' => $newPage,
            'bookings_filter' => $filter->toArray(),
        ]);
    }

    #[Route('/bookings/refresh-filter', name: 'app_backend_booking_refreshFilter', methods: ['POST'])]
    public function refreshFilter(Request $request): Response
    {
        $filter = new BookingFilter;
        $form = $this->createForm(BookingFilterFormType::class, $filter, [
            'parameters' => $this->getParameter('bookings_parameters'),
            'source' => $request->query->get('source', ''),
        ]);

        $form->handleRequest($request);

        return $this->render('backend/booking/filter_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
