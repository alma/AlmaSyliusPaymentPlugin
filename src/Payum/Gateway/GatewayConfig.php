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

    // function getApiMode(): string
    // {
    //     return $this->config[self::CONFIG_API_MODE];
    // }

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

    // function getActiveApiKey(): string
    // {
    //     return ($this->getApiMode() == AlmaClient::LIVE_MODE ? $this->getLiveApiKey() : $this->getTestApiKey());
    // }

    // function getLiveApiKey(): string
    // {
    //     return $this->config[self::CONFIG_LIVE_API_KEY];
    // }

    // function getTestApiKey(): string
    // {
    //     return $this->config[self::CONFIG_TEST_API_KEY];
    // }

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
