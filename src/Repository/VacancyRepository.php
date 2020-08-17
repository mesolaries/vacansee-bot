<?php

namespace App\Repository;

use App\Entity\Channel;
use App\Entity\Vacancy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Vacancy|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vacancy|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vacancy[]    findAll()
 * @method Vacancy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VacancyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vacancy::class);
    }

    public function findVacancyIdByGotDateAndChannel(\DateTime $date, Channel $channel)
    {
        $from = new \DateTime($date->format("Y-m-d") . " 00:00:00");
        $to = new \DateTime($date->format("Y-m-d") . " 23:59:59");

        return $this->createQueryBuilder('v')
            ->select('v.vacancyId')
            ->andWhere('v.gotAt BETWEEN :from AND :to')
            ->andWhere('v.channel = :channel')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->setParameter('channel', $channel)
            ->getQuery()
            ->getArrayResult();
    }

    public function findOneNotSentVacancyByChannel(Channel $channel, $isSent = false)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.channel = :channel')
            ->andWhere('v.isSent = :isSent')
            ->orderBy('v.gotAt', 'ASC')
            ->setParameter('channel', $channel)
            ->setParameter('isSent', $isSent)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findSentVacanciesByGotDateTillThisMonth()
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.isSent = :isSent')
            ->andWhere('v.gotAt < :thisMonth')
            ->setParameter('isSent', true)
            ->setParameter('thisMonth', new \DateTime('first day of this month'))
            ->getQuery()
            ->getResult();
    }

    // /**
    //  * @return Vacancy[] Returns an array of Vacancy objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Vacancy
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
