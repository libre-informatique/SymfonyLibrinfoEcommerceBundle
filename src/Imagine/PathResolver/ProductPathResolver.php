<?php

namespace Librinfo\EcommerceBundle\Imagine\PathResolver;

use Librinfo\MediaBundle\Imagine\PathResolver\PathResolverInterface;
use Librinfo\MediaBundle\Imagine\PathResolver\DefaultResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductPathResolver extends DefaultResolver implements PathResolverInterface
{

    public function resolvePath($path)
    {
        try {
            if (null === $this->cacheFile) {
                /** @var $this->cacheFile File */
                $this->cacheFile = $this->findFile($path);
            }

            if (null === $this->cacheFile) {
                throw new NotFoundHttpException(sprintf('File « %s » was not found', $path));
            } else {
                return $this->cacheFile->getFile();
            }
        } catch (\NotFoundHttpException $e) {

            throw new NotFoundHttpException(sprintf('File « %s » was not found', $path));
        }
    }

    protected function findFile($path)
    {
        $file = null;

        $qb = $this->em->createQueryBuilder();
        $subQb = $this->em->createQueryBuilder();

        $subQb
            ->select('f')
            ->from('LibrinfoEcommerceBundle:ProductImage', 'pi')
            ->join('LibrinfoMediaBundle:File', 'f', 'WITH', 'pi.realFile = f')
            ->where('pi.path = :path')
            ->setParameter('path', $path);


        $file = $subQb->getQuery()->getOneOrNullResult();

        if (!$file) {
            $qb
                ->select('f')
                ->from('LibrinfoMediaBundle:File', 'f')
                ->where('f.path = :path')
                ->setParameter('path', $path);

            $file = $qb->getQuery()->getOneOrNullResult();
        }

        return $file;
    }

}