<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Payum\Action;

use Alma\API\RequestError;
use Alma\SyliusPaymentPlugin\Bridge\AlmaBridge;
use Alma\SyliusPaymentPlugin\Bridge\AlmaBridgeInterface;
use Alma\SyliusPaymentPlugin\Payum\Request\RenderInPagePayment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpRedirect;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\RenderTemplate;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Renders Alma's In-Page payment experience using the `@alma/in-page` SDK.
 *
 * The Alma payment is created server-side (with the mandatory `origin: online_in_page`) so that the
 * `@alma/in-page` SDK can be driven with the resulting payment id, and the browser is redirected to the
 * Payum after-url only once, from the success callback. This avoids relying on the legacy `@alma/fragments`
 * form, whose implicit return handling double-consumed the single-use capture token.
 *
 * @see https://docs.almapay.com/docs/integration-in-page
 */
final class RenderInPagePaymentAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

    /** Mandatory payment origin to flag an In-Page payment to the Alma API. */
    public const PAYMENT_ORIGIN_IN_PAGE = 'online_in_page';

    /**
     * @var AlmaBridgeInterface
     */
    protected $api;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack)
    {
        $this->apiClass = AlmaBridge::class;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
    }

    /**
     * @param RenderInPagePayment $request
     */
    public function execute($request): void
    {
        RequestNotSupportedException::assertSupports($this, $request);

        $details = ArrayObject::ensureArrayObject($request->getModel());
        $config = $this->api->getGatewayConfig();

        /** @var array<string, mixed> $payload */
        $payload = (array) $details[AlmaBridgeInterface::DETAILS_KEY_PAYLOAD];
        /** @var array<string, mixed> $paymentData */
        $paymentData = (array) ($payload['payment'] ?? []);
        $paymentData['origin'] = self::PAYMENT_ORIGIN_IN_PAGE;
        $payload['payment'] = $paymentData;

        $returnUrl = (string) ($paymentData['return_url'] ?? '');

        try {
            $almaPayment = $this->api->getDefaultClient()->payments->create($payload);
        } catch (RequestError $e) {
            $this->logger->error('[Alma] In-Page payment creation failed: ' . $e->getMessage());
            $this->addErrorFlash();
            // Dropping the payload makes the payment look "new" again to the StatusAction, which sends the
            // customer back to the payment method selection once redirected to the after-url.
            $details->offsetUnset(AlmaBridgeInterface::DETAILS_KEY_PAYLOAD);

            throw new HttpRedirect($returnUrl);
        }

        $details[AlmaBridgeInterface::DETAILS_KEY_PAYMENT_ID] = (string) $almaPayment->id;
        $request->setModel($details);

        $this->gateway->execute($renderTemplate = new RenderTemplate(
            $config->getPaymentFormTemplate(),
            [
                'paymentId' => (string) $almaPayment->id,
                'merchantId' => $config->getMerchantId(),
                'apiMode' => $config->getApiMode(),
                'installmentsCount' => $config->getInstallmentsCount(),
                'amountInCents' => (int) ($paymentData['purchase_amount'] ?? 0),
                'returnUrl' => $returnUrl,
            ]
        ));

        throw new HttpResponse($renderTemplate->getResult());
    }

    public function supports($request): bool
    {
        return
            $request instanceof RenderInPagePayment &&
            $request->getModel() instanceof ArrayObject;
    }

    private function addErrorFlash(): void
    {
        /** @var FlashBagInterface $flashBag */
        $flashBag = $this->requestStack->getSession()->getBag('flashes');
        $flashBag->add('error', 'alma_sylius_payment_plugin.payment.creation_failed');
    }
}
