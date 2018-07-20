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

namespace PaymentSuite\RedsysBundle;

use PaymentSuite\PaymentCoreBundle\PaymentMethodInterface;
use PaymentSuite\RedsysBundle\Services\RedsysEncrypter;

/**
 * RedsysMethod class.
 */
final class RedsysMethod implements PaymentMethodInterface
{
    /**
     * @var string
     *
     * Base64 encoded json string representation of payment parameters
     */
    private $dsMerchantParameters;

    /**
     * @var array
     *
     * Decoded dsMerchantParameters array
     */
    private $dsMerchantParametersDecoded;

    /**
     * @var string
     *
     * Transmission version type
     */
    private $dsSignatureVersion;

    /**
     * @var string
     *
     * Encrypted payment signature
     */
    private $dsSignature;

    /**
     * @return mixed
     */
    public function getDsMerchantParameters($decoded = false)
    {
        if($decoded){
            return $this->dsMerchantParametersDecoded;
        }

        return $this->dsMerchantParameters;
    }

    /**
     * @param mixed $dsMerchantParameters
     *
     * @return RedsysMethod self Object
     */
    public function setDsMerchantParameters($dsMerchantParameters)
    {
        $this->dsMerchantParameters = $dsMerchantParameters;

        $this->dsMerchantParametersDecoded = RedsysEncrypter::decode($dsMerchantParameters);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDsSignatureVersion()
    {
        return $this->dsSignatureVersion;
    }

    /**
     * @param mixed $dsSignatureVersion
     *
     * @return RedsysMethod self Object
     */
    public function setDsSignatureVersion($dsSignatureVersion)
    {
        $this->dsSignatureVersion = $dsSignatureVersion;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDsSignature()
    {
        return $this->dsSignature;
    }

    /**
     * @param mixed $dsSignature
     *
     * @return RedsysMethod self Object
     */
    public function setDsSignature($dsSignature)
    {
        $this->dsSignature = $dsSignature;

        return $this;
    }

    public function isTransactionSuccessful()
    {
        $dsResponse = $this->getDsResponse();

        return $dsResponse >= 0 && $dsResponse <= 99;
    }

    /**
     * Returns the method's type name.
     *
     * @return string
     */
    public function getPaymentName()
    {
        return 'redsys';
    }

    private function getDsResponse()
    {
        $decoded = $this->getDsMerchantParameters(true);

        return intval($decoded['Ds_Response']);
    }

    public function getDsOrder()
    {
        $decoded = $this->getDsMerchantParameters(true);

        return $decoded['Ds_Order'];
    }
}
