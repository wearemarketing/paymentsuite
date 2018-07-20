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

use PaymentSuite\RedsysBundle\Exception\InvalidSignatureException;
use PaymentSuite\RedsysBundle\Exception\ParameterNotReceivedException;
use PaymentSuite\RedsysBundle\RedsysMethod;
use PaymentSuite\RedsysBundle\RedsysSignature;

/**
 * Class RedsysMethodFactory.
 */
class RedsysMethodFactory
{
    private $secretKey;

    /**
     * Create new redsys method.
     *
     * @return RedsysMethod new instance
     */
    public function create()
    {
        return new RedsysMethod();
    }

    public function createFromResult(array $resultParameters)
    {
        $this->checkResultParameters($resultParameters);

        $redsysMethod =  new RedsysMethod();

        $redsysMethod
            ->setDsMerchantParameters($resultParameters['Ds_MerchantParameters'])
            ->setDsSignatureVersion($resultParameters['Ds_SignatureVersion'])
            ->setDsSignature($resultParameters['Ds_Signature']);

        $this->validateSignature($redsysMethod);

        return $redsysMethod;
    }

    /**
     * @param array $parameters
     * @throws ParameterNotReceivedException
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

    /**
     * @param RedsysMethod $redsysMethod
     * @throws InvalidSignatureException
     */
    private function validateSignature(RedsysMethod $redsysMethod)
    {
        $parameters = $redsysMethod->getDsMerchantParameters(true);

        $signature = RedsysSignature::createFromResult($parameters, $this->secretKey);

        if (!$signature->match($redsysMethod->getDsSignature())) {
            throw new InvalidSignatureException();
        }
    }
}
