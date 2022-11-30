<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Model\Backend\BookingFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BookingRepository extends ServiceEntityRepository
{
    private $parameters;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function save(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Booking $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    private function filter($qb, BookingFilter $filter, ?\DateTimeInterface $now)
    {
        if ($filter->getYear()) {
            $qb->andWhere($qb->expr()->orX('YEAR(b.startDate) = :year', 'YEAR(b.endDate) = :year'))
                ->setParameter('year', $filter->getYear());
        }

        $statusSql = vsprintf("CASE WHEN CONCAT(b.startDate, ' %s') > :now THEN %d WHEN CONCAT(b.endDate, ' %s') < :now THEN %d ELSE %d END", [
            $this->parameters['arrival_min_time'],
            Booking::PLANNED,
            $this->parameters['departure_max_time'],
            Booking::COMPLETED,
            Booking::IN_PROGRESS
        ]);

        $qb->andWhere("BIT_AND(CAST(POWER(2, $statusSql - 1) AS INTEGER), CAST(:status AS INTEGER)) <> 0")
            ->setParameter('now', ($now ?? new \DateTimeImmutable)->format('Y-m-d H:i:s'))
            ->setParameter('status', $filter->getStatus());
    }

    public function countBookings(?BookingFilter $filter = null, ?\DateTimeInterface $now = null): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COUNT(b.id)');

        if ($filter) {
            $this->filter($qb, $filter, $now);
        }

        return $qb->getQuery()
            ->getSingleScalarResult();
    }

    public function paginateRows(int $limit, int $page, BookingFilter $filter = null, ?\DateTimeInterface $now = null)
    {
        $statusSql = vsprintf("CASE WHEN CONCAT(b.startDate, ' %s') > :now THEN %d WHEN CONCAT(b.endDate, ' %s') < :now THEN %d ELSE %d END", [
            $this->parameters['arrival_min_time'],
            Booking::PLANNED,
            $this->parameters['departure_max_time'],
            Booking::COMPLETED,
            Booking::IN_PROGRESS
        ]);

        if (!$now) {
            $now = new \DateTimeImmutable;
        }

        $qb = $this->createQueryBuilder('b')
            ->select("b AS booking, $statusSql AS status")
            ->setParameter('now', $now->format('Y-m-d H:i:s'))
            ->addOrderBy('b.startDate', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit)
        ;

        if ($filter) {
            $this->filter($qb, $filter, $now);
        }

        return $qb->getQuery()
            ->getResult();
    }

    public function getPage(int $limit, int $id, BookingFilter $filter = null, ?\DateTimeInterface $now = null): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = <<<EOD
SELECT a.page
FROM (
    SELECT b.id, CEIL(CAST(ROW_NUMBER() OVER (ORDER BY b.start_date DESC, b.gite_id) AS REAL) / :limit) AS page
    FROM booking AS b
EOD;
        $where = [];
        $params = [];

        if ($filter) {
            if ($filter->getYear()) {
                $where[] = "(EXTRACT(YEAR FROM b.start_date) = :year OR EXTRACT(YEAR FROM b.end_date) = :year)";
                $params['year'] = $filter->getYear();
            }

            $statusSql = vsprintf("CASE WHEN CONCAT(b.start_date, ' %s') > :now THEN %d WHEN CONCAT(b.end_date, ' %s') < :now THEN %d ELSE %d END", [
                $this->parameters['arrival_min_time'],
                Booking::PLANNED,
                $this->parameters['departure_max_time'],
                Booking::COMPLETED,
                Booking::IN_PROGRESS
            ]);

            $where[] = "CAST(POWER(2, $statusSql - 1) AS INTEGER) & CAST(:status AS INTEGER) <> 0";
            $params['now'] = ($now ?? new \DateTimeImmutable)->format('Y-m-d H:i:s');
            $params['status'] = $filter->getStatus();
        }

        $sql .= $where ? ' WHERE '.implode(' AND ', $where) : '';
        $sql .= ') AS a WHERE a.id = :id';
        $stmt = $conn->prepare($sql);
        $params['limit'] = $limit;
        $params['id'] = $id;

        return $stmt->executeQuery($params)
            ->fetchOne();
    }

    public function getOverlappingBookings(Booking $booking)
    {
        $qb = $this->createQueryBuilder('b')
            ->where('b.gite = :gite')
            ->setParameter(':gite', $booking->getGite())
            ->andWhere('b.endDate > :start_date')
            ->setParameter(':start_date', $booking->getStartDate())
            ->andWhere('b.startDate < :end_date')
            ->setParameter(':end_date', $booking->getEndDate())
            ->addOrderBy('b.startDate', 'ASC')
            ->setMaxResults(3)
        ;

        if ($booking->getId()) {
            $qb->andWhere('b.id <> :id')
                ->setParameter(':id', $booking->getId());
        }

        return $qb->getQuery()
            ->getResult();
    }
}
