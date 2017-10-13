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

namespace Librinfo\EcommerceBundle\Services;

use Doctrine\ORM\EntityManager;
use SM\Factory\Factory;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderPaymentTransitions;
use Sylius\Component\Core\OrderShippingStates;
use Sylius\Component\Core\OrderShippingTransitions;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class OrderCreationManager
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Factory
     */
    private $stateMachineFactory;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, $container)
    {
        $this->em = $em;
        $this->container = $container;
    }

    public function copyAddress(OrderInterface $order, array $data, string $key = 'shippingAddress')
    {
        $orderAddress = null;
        switch ($key) {
            case 'shippingAddress':
                $orderAddress = $order->getShippingAddress();
                break;
            case 'billingAddress':
                $orderAddress = $order->getBillingAddress();
                break;
            case 'customerAddress':
                /* Thanks to Not Typed Php Language ! Should not be done */
                $orderAddress = $order->getCustomer();
                $key = 'shippingAddress'; /* Ugly hack ! Should use another variable as key */
                break;
        }

        if (isset($orderAddress)) {
            foreach ($data[$key] as $field => $bData) {
                try {
                    $propertyAccessor = new PropertyAccessor();
                    $propertyAccessor->setValue($orderAddress, $field, $bData);
                } catch (NoSuchPropertyException $e) {
                    /* Ah Ah Not Always The Same Object ! */
                }
            }
        }

        return $orderAddress;
    }

    /**
     * @param OrderInterface oldOrder
     *
     * @return OrderInterface
     */
    public function duplicateOrder(OrderInterface $oldOrder)
    {
        //        dump($this->getContainer());
        // die;
        $newOrder = $this->createOrder();

        $newOrder->setChannel($oldOrder->getChannel());
        $newOrder->setCustomer($oldOrder->getCustomer());
        $newOrder->setCurrencyCode($oldOrder->getCurrencyCode());
        $newOrder->setLocaleCode($oldOrder->getLocaleCode());
        $newOrder->setBillingAddress(clone $oldOrder->getBillingAddress());
        $newOrder->setShippingAddress(clone $oldOrder->getShippingAddress());

        foreach ($oldOrder->getPromotions() as $oPro) {
            $newOrder->addPromotion(clone $oPro);
        }
        foreach ($oldOrder->getItems() as $oItem) {
            $newOrder->addItem(clone $oItem);
        }
        $newOrder->recalculateItemsTotal();

        foreach ($oldOrder->getAdjustments() as $oAdjust) {
            $newOrder->addAdjustment(clone $oAdjust);
        }
        $newOrder->recalculateAdjustmentsTotal();

        // dump($oldOrder->getCustomer(), $oldOrder->getCustomer());
        //  die("DiE!");
        //$this->container->get('sylius.manager.order')->flush($newOrder);
        return $newOrder;
    }

    public function saveOrder(OrderInterface $newOrder)
    {
        /* @todo: set sylius services as param */
        $newOrder->setNumber($this->container->get('sylius.sequential_order_number_generator')->generate($newOrder));
        $this->container->get('sylius.repository.order')->add($newOrder);
        $this->container->get('sylius.order_processing.order_processor')->process($newOrder);
        $this->container->get('sylius.manager.order')->flush($newOrder);

        return true;
    }

    /**
     * @return OrderInterface
     */
    public function createOrder()
    {
        //@todo : how to new ?
        /* @todo: set sylius services as param */
        $newOrder = $this->container->get('sylius.factory.order')->createNew(); //$this->admin->getNewInstance();
        $addressFactory = $this->container->get('sylius.factory.address');
        $customerFactory = $this->container->get('sylius.factory.customer');

        $newOrder->setShippingAddress($addressFactory->createNew());
        $newOrder->setBillingAddress($addressFactory->createNew());
        $newOrder->setCustomer($customerFactory->createNew());

        $newOrder->setNumber($this->container->get('sylius.sequential_order_number_generator')->generate($newOrder));
        $newOrder->setCheckoutCompletedAt(new \DateTime('NOW'));
        $newOrder->setState(OrderInterface::STATE_NEW);
        $newOrder->setPaymentState(OrderPaymentStates::STATE_CART);
        $newOrder->setShippingState(OrderShippingStates::STATE_CART);

        //$stateMachineFactory = $this->container->get('sm.factory');
        $stateMachine = $this->stateMachineFactory->get($newOrder, OrderShippingTransitions::GRAPH);
        $stateMachine->apply(OrderShippingTransitions::TRANSITION_REQUEST_SHIPPING);
        foreach ($newOrder->getShipments() as $oShipment) {
            $stateMachine = $this->stateMachineFactory->get($oShipment, ShipmentTransitions::GRAPH);
            $stateMachine->apply(ShipmentTransitions::TRANSITION_CREATE);
        }

        $stateMachine = $this->stateMachineFactory->get($newOrder, OrderPaymentTransitions::GRAPH);
        $stateMachine->apply(OrderPaymentTransitions::TRANSITION_REQUEST_PAYMENT);
        foreach ($newOrder->getPayments() as $oPayment) {
            $stateMachine = $this->stateMachineFactory->get($oPayment, PaymentTransitions::GRAPH);
            $stateMachine->apply(PaymentTransitions::TRANSITION_CREATE);
            //            $stateMachine->apply(PaymentTransitions::TRANSITION_PROCESS);
        }

        return $newOrder;
    }

    /**
     * @param Factory stateMachine
     *
     * @return self
     */
    public function setStateMachineFactory(Factory $stateMachineFactory)
    {
        $this->stateMachineFactory = $stateMachineFactory;

        return $this;
    }
}