<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Payum\Action;

use Alma\API\RequestError;
use Alma\SyliusPaymentPlugin\Bridge\AlmaBridge;
use Alma\SyliusPaymentPlugin\Bridge\AlmaBridgeInterface;
use Alma\SyliusPaymentPlugin\Payum\Request\ValidatePayment;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\GetStatusInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;


final class StatusAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface, LoggerAwareInterface
{
    use ApiAwareTrait;
    use GatewayAwareTrait;
    use LoggerAwareTrait;

    /** @var AlmaBridge */
    protected $api;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->apiClass = AlmaBridge::class;
    }

    /**
     * @param GetStatusInterface $request
     */
    public function execute($request): void
    {
        $this->logger->info('Alma - Start Status action', []);
        RequestNotSupportedException::assertSupports($this, $request);

        /** @var PaymentInterface $payment */
        $payment = $request->getModel();
        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $httpRequest = new GetHttpRequest();
        $this->gateway->execute($httpRequest);
        $query = ArrayObject::ensureArrayObject($httpRequest->query);

        $this->logger->info('Alma - offsetExists DETAILS_KEY_PAYLOAD', [$details]);

        // No payload and no is_valid key it's a new payment before redirecting to Alma
        if (
            !$details->offsetExists(AlmaBridgeInterface::DETAILS_KEY_PAYLOAD)
            && !$details->offsetExists(AlmaBridgeInterface::DETAILS_KEY_IS_VALID)
        ) {
            $request->markNew();

            return;
        }

        $this->logger->info('Alma - offsetExists QUERY_PARAM_PID', []);
        if (!$query->offsetExists(AlmaBridgeInterface::QUERY_PARAM_PID)) {
            $request->markPending();

            return;
        }

        // Make sure the payment's details include the Alma payment ID
        $this->logger->info('Alma - exist DETAILS_KEY_PAYMENT_ID', []);
        $details[AlmaBridgeInterface::DETAILS_KEY_PAYMENT_ID] = (string)$query[AlmaBridgeInterface::QUERY_PARAM_PID];
        $payment->setDetails($details->getArrayCopy());

        // If payment hasn't been validated yet, validate its status against Alma's payment state
        $this->logger->info('Alma - check payment valid', [!$details->offsetExists(AlmaBridgeInterface::DETAILS_KEY_IS_VALID)]);
        if (
            !$details->offsetExists(AlmaBridgeInterface::DETAILS_KEY_IS_VALID)
            && in_array($payment->getState(), [PaymentInterface::STATE_NEW, PaymentInterface::STATE_PROCESSING], true)
        ) {
            try {
                $this->logger->info('Alma - Validate Payment', []);
                //$this->gateway->execute(new ValidatePayment($payment));
            } catch (RequestError $e) {
                $this->logger->info('Alma - error valid', []);
                $details = ArrayObject::ensureArrayObject($payment->getDetails());
                $details[AlmaBridgeInterface::DETAILS_KEY_IS_VALID] = false;
                $payment->setDetails($details->getArrayCopy());
            }

            // Refresh details to get validation status
            $this->logger->info('Alma - refresh details', []);
            $details = ArrayObject::ensureArrayObject($payment->getDetails());
        }

        /** @var bool|null $isValid */
        $this->logger->info('Alma - $isValid', [$details->get(AlmaBridgeInterface::DETAILS_KEY_IS_VALID)]);
        $isValid = $details->get(AlmaBridgeInterface::DETAILS_KEY_IS_VALID);
        // Explicitly compare to true/false, as a null value (i.e. no IS_VALID_KEY in $details) means unknown state
        if ($isValid === true) {
            $this->logger->info('Alma - check $isValid true', [$isValid]);
            $request->markCaptured();
            $this->cleanPayload($payment);
        } elseif ($isValid === false) {
            $this->logger->info('Alma - check $isValid false', [$isValid]);
            $request->markFailed();
            $this->cleanPayload($payment);
        }
        $this->logger->info('Alma - End Status action', []);
    }

    // Payment's payload will uselessly occupy database space, so clean it once it's been used
    private function cleanPayload(PaymentInterface $payment): void
    {
        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        if (!$details->offsetExists(AlmaBridgeInterface::DETAILS_KEY_PAYLOAD)) {
            return;
        }

        $details->offsetUnset(AlmaBridgeInterface::DETAILS_KEY_PAYLOAD);
        $payment->setDetails($details->getArrayCopy());
    }

    public function supports($request): bool
    {
        return
            $request instanceof GetStatusInterface &&
            $request->getModel() instanceof PaymentInterface;
    }
}
