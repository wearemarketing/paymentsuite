<?php

/*
 * This file is part of the PaymentSuite package.
 *
 * Copyright (c) 2013-2016 Marc Morera
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

namespace PaymentSuite\RedsysBundle\Services;

use PaymentSuite\PaymentCoreBundle\Exception\PaymentException;
use PaymentSuite\PaymentCoreBundle\Exception\PaymentOrderNotFoundException;
use PaymentSuite\PaymentCoreBundle\Services\Interfaces\PaymentBridgeInterface;
use PaymentSuite\PaymentCoreBundle\Services\PaymentEventDispatcher;
use PaymentSuite\RedsysBundle\Exception\InvalidSignatureException;
use PaymentSuite\RedsysBundle\Exception\ParameterNotReceivedException;
use PaymentSuite\RedsysBundle\RedsysMethod;
use PaymentSuite\RedsysBundle\RedsysSignature;

/**
 * Redsys manager.
 */
class RedsysManager
{
    /**
     * @var RedsysFormTypeBuilder
     *
     * Form Type Builder
     */
    private $redsysFormTypeBuilder;

    /**
     * @var RedsysMethodFactory
     *
     * RedsysMethod factory
     */
    private $redsysMethodFactory;

    /**
     * @var PaymentBridgeInterface
     *
     * Payment bridge interface
     */
    private $paymentBridge;

    /**
     * @var PaymentEventDispatcher
     *
     * Payment event dispatcher
     */
    private $paymentEventDispatcher;

    /**
     * @var string
     *
     * Secret key
     */
    private $secretKey;

    /**
     * Construct method for redsys manager.
     *
     * @param RedsysFormTypeBuilder  $redsysFormTypeBuilder  Form Type Builder
     * @param RedsysMethodFactory    $redsysMethodFactory    RedsysMethod factory
     * @param PaymentBridgeInterface $paymentBridge          Payment Bridge
     * @param PaymentEventDispatcher $paymentEventDispatcher Event dispatcher
     * @param string                 $secretKey              Secret Key
     */
    public function __construct(
        RedsysFormTypeBuilder $redsysFormTypeBuilder,
        RedsysMethodFactory $redsysMethodFactory,
        PaymentBridgeInterface $paymentBridge,
        PaymentEventDispatcher $paymentEventDispatcher,
        $secretKey
    ) {
        $this->redsysFormTypeBuilder = $redsysFormTypeBuilder;
        $this->redsysMethodFactory = $redsysMethodFactory;
        $this->paymentBridge = $paymentBridge;
        $this->paymentEventDispatcher = $paymentEventDispatcher;
        $this->secretKey = $secretKey;
    }

    /**
     * Creates form view for Redsys payment.
     *
     * @return \Symfony\Component\Form\FormView
     *
     * @throws PaymentOrderNotFoundException
     */
    public function processPayment()
    {
        $redsysMethod = $this
            ->redsysMethodFactory
            ->create();

        /**
         * At this point, order must be created given a cart, and placed in PaymentBridge.
         *
         * So, $this->paymentBridge->getOrder() must return an object
         */
        $this
            ->paymentEventDispatcher
            ->notifyPaymentOrderLoad(
                $this->paymentBridge,
                $redsysMethod
            );

        /**
         * Order Not found Exception must be thrown just here.
         */
        if (!$this->paymentBridge->getOrder()) {
            throw new PaymentOrderNotFoundException();
        }

        /**
         * Order exists right here.
         */
        $this
            ->paymentEventDispatcher
            ->notifyPaymentOrderCreated(
                $this->paymentBridge,
                $redsysMethod
            );

        return $this
            ->redsysFormTypeBuilder
            ->buildForm();
    }

    /**
     * Processes the POST request sent by Redsys.
     *
     * @param array $parameters Array with response parameters
     *
     * @return RedsysManager Self object
     *
     * @throws InvalidSignatureException     Invalid signature
     * @throws ParameterNotReceivedException Invalid parameters
     * @throws PaymentException              Payment exception
     */
    public function processResult(array $parameters)
    {
        $this->checkResultParameters($parameters);

        $decoded = RedsysEncrypter::decode($parameters['Ds_MerchantParameters']);

        $signature = RedsysSignature::createFromResult($decoded, $this->secretKey);

        if ($parameters['Ds_Signature'] != $signature->denormalized()) {
            throw new InvalidSignatureException();
        }

        $redsysMethod = new RedsysMethod();

        /*
         * Adding transaction information to PaymentMethod.
         *
         * This information is only available in PaymentOrderSuccess event
         */
        $redsysMethod
            ->setDsMerchantParameters($parameters['Ds_MerchantParameters'])
            ->setDsSignatureVersion($parameters['Ds_SignatureVersion'])
            ->setDsSignature($parameters['Ds_Signature']);

        /*
         * Here PaymentBridge shouldn't have the order loaded, so we find it fromm parameters
         */
        $this->paymentBridge
            ->findOrder($this->parseOrderId($decoded['Ds_Order']));

        /*
         * Payment paid done.
         *
         * Paid process has ended ( No matters result )
         */
        $this
            ->paymentEventDispatcher
            ->notifyPaymentOrderDone(
                $this->paymentBridge,
                $redsysMethod
            );

        /*
         * when a transaction is successful, $Ds_Response has a
         * value between 0 and 99.
         */
        if (!$this->transactionSuccessful($decoded['Ds_Response'])) {

            /*
             * Payment paid failed.
             *
             * Paid process has ended failed
             */
            $this
                ->paymentEventDispatcher
                ->notifyPaymentOrderFail(
                    $this->paymentBridge,
                    $redsysMethod
                );

            throw new PaymentException();
        }

        /*
         * Payment paid successfully.
         *
         * Paid process has ended successfully
         */
        $this
            ->paymentEventDispatcher
            ->notifyPaymentOrderSuccess(
                $this->paymentBridge,
                $redsysMethod
            );

        return $this;
    }

    /**
     * Returns true if the transaction was successful.
     *
     * @param string $dsResponse Response code
     *
     * @return bool
     */
    private function transactionSuccessful($dsResponse)
    {
        /**
         * When a transaction is successful, $Ds_Response has a value
         * between 0 and 99.
         */

        return intval($dsResponse) >= 0 && intval($dsResponse) <= 99;
    }

    /**
     * Checks that all the required parameters are received.
     *
     * @param array $parameters Parameters
     *
     * @throws ParameterNotReceivedException Parameters missing
     */
    private function checkResultParameters(array $parameters)
    {
        $elementsMissing = array_diff([
            'Ds_MerchantParameters',
            'Ds_Signature',
            'Ds_SignatureVersion',
        ], array_keys($parameters));

        if (!empty($elementsMissing)) {
            throw new ParameterNotReceivedException(
                implode(', ', $elementsMissing)
            );
        }
    }

    private function parseOrderId($dsOrder)
    {
        $chunks = explode('T', $dsOrder);

        return intval($chunks[0]);
    }
}
