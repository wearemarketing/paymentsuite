<?php

namespace PaymentSuite\RedsysBundle;

use PaymentSuite\RedsysBundle\Services\RedsysEncrypter;

class RedsysSignature
{
    private $signature;

    private function __construct(array $parameters, string $secretKey, string $orderIndex)
    {
        $key = RedsysEncrypter::encrypt($parameters[$orderIndex], $secretKey);

        $this->signature = RedsysEncrypter::hash($this->encodeMerchantParameters($parameters), $key);
    }

    public static function create($parameters, $secretKey)
    {
        return new self($parameters, $secretKey, 'Ds_Merchant_Order');
    }

    public static function createFromResult($parameters, $secretKey)
    {
        return new self($parameters, $secretKey, 'Ds_Order');
    }

    public function normalized()
    {
        return $this->signature;
    }

    public function denormalized()
    {
        return RedsysEncrypter::denormalize($this->signature);
    }
}
