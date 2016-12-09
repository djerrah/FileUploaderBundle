<?php

namespace Djerrah\FileUploaderBundle\Repository;

use Doctrine\ORM\EntityRepository;

class FileRepository extends EntityRepository
{

    /**
     * @param string $alias
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQueryBuilder($alias = 'file')
    {

        $query = $this->createQueryBuilder($alias);

        return $query;
    }
}
