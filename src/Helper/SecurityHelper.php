<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Helper;

use Alma\API\Lib\PaymentValidator;
use Alma\SyliusPaymentPlugin\Exception\SecurityException;

class SecurityHelper
{
    /**
     * @var PaymentValidator
     */
    protected $paymentValidator;

    public function __construct(PaymentValidator $paymentValidator)
    {
        $this->paymentValidator = $paymentValidator;
    }

    /**
     * @param string $paymentId
     * @param string $key
     * @param string $signature
     * @throws SecurityException
     */
    public function isHmacValidated(string $paymentId, string $key, string $signature): void
    {
        if (!$this->paymentValidator->isHmacValidated($paymentId, $key, $signature)) {
            throw new SecurityException("HMAC validation failed for payment $paymentId - signature: $signature");
        }
    }


}
