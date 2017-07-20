<?php

/*
 * This file is part of the Blast Project package.
 *
 * Copyright (C) 2015-2017 Libre Informatique
 *
 * This file is licenced under the GNU LGPL v3.
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Librinfo\EcommerceBundle\Admin;

use Blast\CoreBundle\Admin\Traits\HandlesRelationsAdmin;
use Blast\CoreBundle\Admin\CoreAdmin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * @author Marcos Bezerra de Menezes <marcos.bezerra@libre-informatique.fr>
 */
class ProductAdmin extends CoreAdmin
{
    use HandlesRelationsAdmin {
        configureFormFields as configFormHandlesRelations;
        configureShowFields as configShowHandlesRelations;
    }

    public function configureActionButtons($action, $object = null)
    {
        $list = parent::configureActionButtons($action, $object);

        if ($action === 'list') {
            $list = array_merge($list, [
                ['template' => 'LibrinfoEcommerceBundle:CRUD:list__action_shop_link.html.twig']
            ]);
        } elseif ($action === 'edit') {
            $list = array_merge($list, [
                ['template' => 'LibrinfoEcommerceBundle:CRUD:global__action_shop_link.html.twig']
            ]);
        }

        return $list;
    }

    protected function configureRoutes(RouteCollection $collection)
    {
        parent::configureRoutes($collection);
        $collection->add('generateProductSlug', 'generate_product_slug');
        $collection->add('setAsCoverImage', 'setAsCoverImage');
    }

    /**
     * Configure create/edit form fields.
     *
     * @param FormMapper
     */
    protected function configureFormFields(FormMapper $mapper)
    {
        //calls to methods of traits
        $this->configFormHandlesRelations($mapper);
        $admin = $this;
        $mapper->getFormBuilder()->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($admin) {
                $form = $event->getForm();
                $subject = $admin->getSubject($event->getData());
                if ($form->has('variants')) {
                    $form->remove('variants');
                }
            }
        );
    }

    /**
     * Configure Show view fields.
     *
     * @param ShowMapper $mapper
     */
    protected function configureShowFields(ShowMapper $mapper)
    {
        // call to aliased trait method
        $this->configShowHandlesRelations($mapper);
    }

    /**
     * @return ProductInterface
     */
    public function getNewInstance()
    {
        /** @var ProductFactoryInterface $productFactory * */
        $productFactory = $this->getConfigurationPool()->getContainer()->get('sylius.factory.product');

        /** @var ProductInterface $product */
        $object = $productFactory->createNew();

        foreach ($this->getExtensions() as $extension) {
            $extension->alterNewInstance($this, $object);
        }

        return $object;
    }

    public function prePersist($product)
    {
        parent::prePersist($product);

        $slugGenerator = $this->getConfigurationPool()->getContainer()->get('sylius.generator.slug');
        $product->setSlug($slugGenerator->generate($product->getName()));
    }
}
