<?php
// src/OC/PlatformBundle/Repository/AdvertRepository.php

namespace OC\PlatformBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class AdvertRepository extends EntityRepository
{
    /**
     * @param \Datetime $date
     * @return array
     */
    public function getAdvertsBefore(\Datetime $date) : array
    {
        return $this->createQueryBuilder('a')
                    ->where('a.updatedAt <= :date')                      // Date de modification antérieure à :date
                    ->orWhere('a.updatedAt IS NULL AND a.date <= :date') // Si la date de modification est vide, on vérifie la date de création
                    ->andWhere('a.applications IS EMPTY')                // On vérifie que l'annonce ne contient aucune candidature
                    ->setParameter('date', $date)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @param int $page
     * @param int $nbPerPage
     * @return Paginator
     */
    public function getAdverts(int $page, int $nbPerPage) : Paginator
    {
        $query = $this->createQueryBuilder('a')
                      ->leftJoin('a.image', 'i')
                      ->addSelect('i')
                      ->leftJoin('a.categories', 'c')
                      ->addSelect('c')
                      ->orderBy('a.date', 'DESC')
                      ->getQuery();

        $query
              // On définit l'annonce à partir de laquelle commencer la liste
              ->setFirstResult(($page-1) * $nbPerPage)
              // Ainsi que le nombre d'annonce à afficher sur une page
              ->setMaxResults($nbPerPage);

        // Enfin, on retourne l'objet Paginator correspondant à la requête construite
        // (n'oubliez pas le use correspondant en début de fichier)
        return new Paginator($query, true);
    }

    /**
     * @return array
     */
    public function myFindAll() : array
    {
        // Méthode 1 : en passant par l'EntityManager
        $queryBuilder = $this->_em->createQueryBuilder()
                            ->select('a')
                            ->from($this->_entityName, 'a');
        // Dans un repository, $this->_entityName est le namespace de l'entité gérée
        // Ici, il vaut donc OC\PlatformBundle\Entity\Advert

        // Méthode 2 : en passant par le raccourci (je recommande)
        $queryBuilder = $this->createQueryBuilder('a');

        // On n'ajoute pas de critère ou tri particulier, la construction
        // de notre requête est finie

        // On récupère la Query à partir du QueryBuilder
        $query = $queryBuilder->getQuery();

        // On récupère les résultats à partir de la Query
        $results = $query->getResult();

        // On retourne ces résultats
        return $results;
    }

    /**
     * @return array
     */
    public function myFind() : array
    {
        $qb = $this->createQueryBuilder('a');

        // On peut ajouter ce qu'on veut avant
        $qb
          ->where('a.author = :author')
          ->setParameter('author', 'Marine');

        // On applique notre condition sur le QueryBuilder
        $this->whereCurrentYear($qb);

        // On peut ajouter ce qu'on veut après
        $qb->orderBy('a.date', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array $categoryNames
     * @return array
     */
    public function getAdvertWithCategories(array $categoryNames) : array
    {
        $qb = $this->createQueryBuilder('a');

        // On fait une jointure avec l'entité Category avec pour alias « c »
        $qb
          ->innerJoin('a.categories', 'c')
          ->addSelect('c');

        // Puis on filtre sur le nom des catégories à l'aide d'un IN
        $qb->where($qb->expr()->in('c.name', $categoryNames));
        // La syntaxe du IN et d'autres expressions se trouve dans la documentation Doctrine

        // Enfin, on retourne le résultat
        return $qb->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $qb
     */
    protected function whereCurrentYear(QueryBuilder $qb)
    {
        $qb
          ->andWhere('a.date BETWEEN :start AND :end')
          ->setParameter('start', new \Datetime(date('Y') . '-01-01')) // Date entre le 1er janvier de cette année
          ->setParameter('end', new \Datetime(date('Y') . '-12-31'))   // Et le 31 décembre de cette année
        ;
    }
}
