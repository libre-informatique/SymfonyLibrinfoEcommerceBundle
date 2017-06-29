<?php

/*
 * Copyright (C) 2015-2016 Libre Informatique
 *
 * This file is licenced under the GNU GPL v3.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Librinfo\EcommerceBundle\Entity;

use AppBundle\Entity\OuterExtension\LibrinfoEcommerceBundle\ProductExtension;
use Blast\OuterExtensionBundle\Entity\Traits\OuterExtensible;
use Sylius\Component\Core\Model\Product as BaseProduct;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Component\Core\Model\ImageInterface;
use Librinfo\EcommerceBundle\Entity\ProductImage;

class Product extends BaseProduct
{

    use OuterExtensible,
        ProductExtension;

    /**
     * @var Collection|ImageInterface[]
     */
    protected $images;

    public function __construct()
    {
        parent::__construct();
        $this->initOuterExtendedClasses();
        $this->images = new ArrayCollection();
    }

    public function getImages()
    {
        return $this->images;
    }

    public function setImages(ArrayCollection $images)
    {
        $this->images = $images;
        return $this;
    }

    /**
     * alias for LibrinfoMediaBundle/CRUDController::handleFiles()
     *
     * @param File $file
     * @return Variety
     */
    public function addLibrinfoFile(ProductImage $file = null)
    {

        if (!$this->images->contains($file)) {
            $this->images->add($file);
        }
        return $this;
    }

    /**
     * alias for LibrinfoMediaBundle/CRUDController::handleFiles()
     *
     * @param File $file
     * @return Variety
     */
    public function removeLibrinfoFile(ProductImage $file)
    {
        if ($this->images->contains($file)) {
            $this->images->removeElement($file);
        }
        return $this;
    }

    public function __toString()
    {
        return (string) parent::__toString();
    }

}
