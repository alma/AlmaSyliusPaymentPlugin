<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Payum\Gateway;


use Alma\API\Client as AlmaClient;
use Payum\Core\Bridge\Spl\ArrayObject;

class GatewayConfig implements GatewayConfigInterface
{
    /**
     * @var ArrayObject
     */
    private $config;

    public function __construct(ArrayObject $config)
    {
        $this->config = $config;
    }

    function getApiKey(): string
    {
        return $this->config[self::CONFIG_API_KEY];
    }

    function getMerchantId(): string
    {
        return $this->config[self::CONFIG_MERCHANT_ID];
    }

    function getUrlRoot(): string
    {
        return $this->config[self::CONFIG_URL_ROOT];
    }

    function getInstallmentsCount(): int
    {
        return (int)$this->config[self::CONFIG_INSTALLMENTS_COUNT];
    }

    function getPaymentFormTemplate(): string
    {
        return $this->config[self::CONFIG_PAYMENT_FORM_TEMPLATE];
    }

    function getPaymentPageMode(): string
    {
        return $this->config[self::CONFIG_PAYMENT_PAGE_MODE];
    }
}
