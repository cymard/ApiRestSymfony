<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }
    /**
     *  Returns a sorted array of Product objects 
     *
    */
    public function findSort($sort)
    {
        return $this->findBy(array(), array('price' => $sort));
    }

    public function searchProductAdminWithCategory($category,$sort){
        if($sort === 'default'){
            $sort = 'ASC';
        }

        if($category === 'all'){
            return $this->createQueryBuilder('p')
            ->orderBy('p.price', $sort)
            ;
        }else{
            return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->setParameter('category',$category)
            ->orderBy('p.price', $sort)
            ;
        }
    }

    public function searchProductWithCategory($category){
        if($category === 'all'){
            return $this->createQueryBuilder('p')
            ;
        }else{
            return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->setParameter('category',$category)
            ;
        }
    }

    public function searchProduct($name){ //,$category,$sort
        return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :name')
            ->setParameter('name','%'.$name.'%' )
            ->orderBy('p.price', "ASC")
        ;
    }

    public function searchProductAdmin($name,$category,$sort){ //,$category,$sort

        if($sort === 'default'){
            $sort = 'ASC';
        }

        if($category === 'all'){
            return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :name')
            ->setParameter('name','%'.$name.'%' )
            ->orderBy('p.price', $sort)
        ;
        }else{
            return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :name')
            ->andWhere('p.category = :category')
            ->setParameter('name','%'.$name.'%' )
            ->setParameter('category',$category )
            ->orderBy('p.price', $sort)
        ;
        }
        
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Product
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
