<?php

namespace PaymentSuite\RedsysBundle;

use PaymentSuite\RedsysBundle\Services\RedsysEncrypter;

final class RedsysSignature
{
    private $signature;

    private function __construct(array $parameters, string $secretKey, string $orderIndex)
    {
        $key = self::encrypt($parameters[$orderIndex], $secretKey);

        $this->signature = self::hash(RedsysEncrypter::encode($parameters), $key);
    }

    public static function create($parameters, $secretKey)
    {
        return new self($parameters, $secretKey, 'Ds_Merchant_Order');
    }

    public static function createFromResult($parameters, $secretKey)
    {
        return new self($parameters, $secretKey, 'Ds_Order');
    }

    public function match(string $signature){

        return $this->signature == RedsysEncrypter::normalize($signature);
    }

    public function __toString()
    {
        return $this->signature;
    }

    private function encrypt($message, $key)
    {
        $key = base64_decode($key);

        $bytes = array(0, 0, 0, 0, 0, 0, 0, 0);
        $iv = implode(array_map('chr', $bytes));

        if (strlen($message) % 8) {
            $message = str_pad($message,
                strlen($message) + 8 - strlen($message) % 8, "\0");
        }

        return openssl_encrypt($message, 'DES-EDE3-CBC', $key, OPENSSL_NO_PADDING|OPENSSL_RAW_DATA, $iv);
    }

    private function hash($data, $key)
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true));
    }
}
