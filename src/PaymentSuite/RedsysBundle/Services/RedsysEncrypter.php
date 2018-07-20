<?php


namespace PaymentSuite\RedsysBundle\Services;


final class RedsysEncrypter
{
    public static function encode(array $params)
    {
        return base64_encode(json_encode($params));
    }

    public static function decode(string $params)
    {
        return json_decode(base64_decode(self::normalize($params)), true);
    }

    public static function normalize(string $value)
    {
        return strtr($value, '-_', '+/');
    }

    public static function denormalize(string $value)
    {
        return strtr($value, '+/', '-_');
    }

    public static function encrypt($message, $key)
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

    public static function hash($data, $key)
    {
        return base64_encode(hash_hmac('sha256', $data, $key, true));
    }
}