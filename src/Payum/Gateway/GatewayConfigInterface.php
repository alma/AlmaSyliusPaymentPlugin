<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Payum\Gateway;

use Payum\Core\Bridge\Spl\ArrayObject;

interface GatewayConfigInterface {
    const ALLOWED_CURRENCY_CODES = ['EUR'];

    const CONFIG_API_KEY = 'ALMA_API_KEY';
    const CONFIG_MERCHANT_ID = 'ALMA_MERCHANT_ID';
    const CONFIG_URL_ROOT = 'ALMA_ROOT_URL';

    const CONFIG_INSTALLMENTS_COUNT = 'installments_count';
    const CONFIG_PAYMENT_PAGE_MODE = 'payment_page_mode';
    const CONFIG_PAYMENT_FORM_TEMPLATE = 'payum.template.payment_form_template';

    const PAYMENT_PAGE_MODE_IN_PAGE = 'payment_page_mode.in_page';
    const PAYMENT_PAGE_MODE_REDIRECT = 'payment_page_mode.redirect';

    public function __construct(ArrayObject $config);

    function getApiKey(): string;
    function getMerchantId(): string;
    function getUrlRoot(): string;

    function getInstallmentsCount(): int;

    function getPaymentFormTemplate(): string;

    function getPaymentPageMode(): string;
}
