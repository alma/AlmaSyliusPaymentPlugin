<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Payum\Gateway;

use Payum\Core\Bridge\Spl\ArrayObject;

interface GatewayConfigInterface {
    const ALLOWED_CURRENCY_CODES = ['EUR'];

    const CONFIG_MERCHANT_ID = 'merchant_id';
    const CONFIG_LIVE_API_KEY = 'live_api_key';
    const CONFIG_TEST_API_KEY = 'test_api_key';
    const CONFIG_API_MODE = 'api_mode';
    const CONFIG_INSTALLMENTS_COUNT = 'installments_count';
    const CONFIG_PAYMENT_PAGE_MODE = 'payment_page_mode';
    const CONFIG_PAYMENT_FORM_TEMPLATE = 'payum.template.payment_form_template';

    const PAYMENT_PAGE_MODE_IN_PAGE = 'payment_page_mode.in_page';
    const PAYMENT_PAGE_MODE_REDIRECT = 'payment_page_mode.redirect';

    public function __construct(ArrayObject $config);

    public function getFactoryName(): string;

    public function getApiMode(): string;

    public function getMerchantId(): string;

    public function getActiveApiKey(): string;
    public function getLiveApiKey(): string;
    public function getTestApiKey(): string;

    public function getInstallmentsCount(): int;

    public function getPaymentFormTemplate(): string;

    public function getPaymentPageMode(): string;
}
