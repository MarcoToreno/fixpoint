<?php

namespace App\Repository;

use App\Entity\RepairService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<RepairService> */
final class RepairServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepairService::class);
    }

    /** @return list<RepairService> */
    public function search(string $query, ?int $category): array
    {
        $builder = $this->createQueryBuilder('service')
            ->innerJoin('service.category', 'category')
            ->addSelect('category')
            ->orderBy('service.searchTitle', 'ASC')
            ->addOrderBy('service.id', 'ASC');

        if ($query !== '') {
            $escaped = strtr(mb_strtolower($query, 'UTF-8'), ['!' => '!!', '%' => '!%', '_' => '!_']);
            $builder->andWhere("service.searchTitle LIKE :query ESCAPE '!'")
                ->setParameter('query', '%'.$escaped.'%');
        }

        if ($category !== null) {
            $builder->andWhere('category.id = :category')
                ->setParameter('category', $category);
        }

        return $builder->getQuery()->getResult();
    }
}
