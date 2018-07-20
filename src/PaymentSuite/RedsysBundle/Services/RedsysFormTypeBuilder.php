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

use PaymentSuite\RedsysBundle\RedsysSignature;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormView;
use PaymentSuite\RedsysBundle\Exception\CurrencyNotSupportedException;
use PaymentSuite\RedsysBundle\Services\Interfaces\PaymentBridgeRedsysInterface;

/**
 * RedsysFormTypeBuilder.
 */
class RedsysFormTypeBuilder
{
    /**
     * @var RedsysUrlFactory
     *
     * URL Factory service
     */
    private $urlFactory;

    /**
     * @var PaymentBridgeRedsysInterface
     *
     * Payment bridge
     */
    private $paymentBridge;

    /**
     * @var FormFactory
     *
     * Form factory
     */
    protected $formFactory;

    /**
     * @var string
     *
     * Merchant code
     */
    private $merchantCode;

    /**
     * @var string
     *
     * Secret key
     */
    private $secretKey;

    /**
     * @var string
     *
     * Url
     */
    private $url;

    /**
     * @var string
     *
     * Terminal id
     */
    private $terminal;

    /**
     * construct.
     *
     * @param PaymentBridgeRedsysInterface $paymentBridge Payment bridge
     * @param RedsysUrlFactory             $urlFactory    URL Factory service
     * @param FormFactory                  $formFactory   Form factory
     * @param string                       $merchantCode  merchant code
     * @param string                       $secretKey     secret key
     * @param string                       $url           gateway url
     */
    public function __construct(
        PaymentBridgeRedsysInterface $paymentBridge,
        RedsysUrlFactory $urlFactory,
        FormFactory $formFactory,
        $merchantCode,
        $secretKey,
        $url,
        $terminal
    ) {
        $this->paymentBridge = $paymentBridge;
        $this->urlFactory = $urlFactory;
        $this->formFactory = $formFactory;
        $this->merchantCode = $merchantCode;
        $this->secretKey = $secretKey;
        $this->url = $url;
        $this->terminal = $terminal;
    }

    /**
     * Builds form given return, success and fail urls.
     *
     * @return FormView
     * @throws CurrencyNotSupportedException
     */
    public function buildForm()
    {
        $parameters = $this->buildParameters();

        $merchantParameters = RedsysEncrypter::encode($parameters);

        $signature = RedsysSignature::create($parameters, $this->secretKey);

        $formBuilder = $this
            ->formFactory
            ->createNamedBuilder(null);

        $formBuilder
            ->setAction($this->url)
            ->setMethod('POST')
            ->add('Ds_SignatureVersion', 'hidden', array(
                'data' => 'HMAC_SHA256_V1',
            ))
            ->add('Ds_MerchantParameters', 'hidden', array(
                'data' => $merchantParameters,
            ))
            ->add('Ds_Signature', 'hidden', array(
                'data' => $signature->normalized(),
            ));

        return $formBuilder
            ->getForm()
            ->createView();
    }

    /**
     * Returns an array of gateway payment parameters.
     *
     * @return array
     *
     * @throws CurrencyNotSupportedException
     */
    private function buildParameters()
    {
        $orderId = $this
            ->paymentBridge
            ->getOrderId();

        $extraData = $this->paymentBridge->getExtraData();

        $parameters = array(
            'Ds_Merchant_TransactionType' => isset($extraData['transaction_type']) ? $extraData['transaction_type'] : 0,
            'Ds_Merchant_MerchantURL' => $this->urlFactory->getReturnRedsysUrl(),
            'Ds_Merchant_UrlOK' => $this->urlFactory->getReturnUrlOkForOrderId($orderId),
            'Ds_Merchant_UrlKO' => $this->urlFactory->getReturnUrlKoForOrderId($orderId),
            'Ds_Merchant_Amount' => (string) $this->paymentBridge->getAmount(),
            'Ds_Merchant_Order' => $this->formatOrderNumber($orderId),
            'Ds_Merchant_MerchantCode' => $this->merchantCode,
            'Ds_Merchant_Currency' => $this->getCurrencyCodeByIso($this->paymentBridge->getCurrency()),
            'Ds_Merchant_Terminal' => $this->terminal,
        );

        /*
         * Optional parameters.
         */
        if (array_key_exists('product_description', $extraData)) {
            $parameters['Ds_Merchant_ProductDescription'] = $extraData['product_description'];
        }

        if (array_key_exists('merchant_titular', $extraData)) {
            $parameters['Ds_Merchant_Titular'] = $extraData['merchant_titular'];
        }

        if (array_key_exists('merchant_name', $extraData)) {
            $parameters['Ds_Merchant_MerchantName'] = $extraData['merchant_name'];
        }

        if (array_key_exists('merchant_data', $extraData)) {
            $parameters['Ds_Merchant_MerchantData'] = $extraData['merchant_data'];
        }

        return $parameters;
    }

    /**
     * Translates standard currency to Redsys currency code.
     *
     * @param string $currency Currency
     *
     * @return string Currency code
     *
     * @throws CurrencyNotSupportedException Currency not supported
     */
    private function getCurrencyCodeByIso($currency)
    {
        switch ($currency) {
            case 'EUR':
                return '978';
            case 'USD':
                return '840';
            case 'GBP':
                return '826';
            case 'JPY':
                return '392';
            case 'ARS':
                return '032';
            case 'CAD':
                return '124';
            case 'CLF':
                return '152';
            case 'COP':
                return '170';
            case 'INR':
                return '356';
            case 'MXN':
                return '484';
            case 'PEN':
                return '604';
            case 'CHF':
                return '756';
            case 'BRL':
                return '986';
            case 'VEF':
                return '937';
            case 'TRY':
                return '949';
            default:
                throw new CurrencyNotSupportedException();
        }
    }

    /**
     * Formats order number to be Redsys compliant.
     *
     * @param string $orderNumber Order number
     *
     * @return string $orderNumber
     */
    private function formatOrderNumber($orderNumber)
    {
        $length = strlen($orderNumber);
        $minLength = 4;

        if ($length < $minLength) {
            $orderNumber = str_pad($orderNumber, $minLength, '0', STR_PAD_LEFT);
        }

        $orderNumber .= 'T'.strrev(time());
        $orderNumber = substr($orderNumber, 0, 12);

        return $orderNumber;
    }
}
