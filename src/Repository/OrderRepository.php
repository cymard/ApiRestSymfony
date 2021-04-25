<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
    * Returns an array of searched Orders
    */
    public function findAllOrders()
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdDate' , 'DESC');
    }

    public function findAllOrdersByDate($email,$date)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.email = :email')
            ->setParameter("email",$email)
            ->orderBy('o.createdDate' , $date);
    }

    /**
    * Returns an array of searched Orders
    */
    public function findOrderBySearchingEmail($search)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.email LIKE :email')
            ->setParameter("email",$search.'%')
            ->orderBy('o.email' ,'DESC');
    }
}
