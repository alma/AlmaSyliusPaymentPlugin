<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Payum\Action;

use Alma\API\Lib\PaymentValidator;
use Alma\SyliusPaymentPlugin\Bridge\AlmaBridge;
use Alma\SyliusPaymentPlugin\Bridge\AlmaBridgeInterface;
use Alma\SyliusPaymentPlugin\Exception\SecurityException;
use Alma\SyliusPaymentPlugin\Helper\SecurityHelper;
use Alma\SyliusPaymentPlugin\Payum\Request\ValidatePayment;
use ArrayAccess;
use Payum\Core\Action\ActionInterface;
use Payum\Core\ApiAwareInterface;
use Payum\Core\ApiAwareTrait;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\Notify;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentInterface as PaymentInterfaceModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class NotifyAction implements ActionInterface, ApiAwareInterface, GatewayAwareInterface
{
    use GatewayAwareTrait;
    use ApiAwareTrait;

    /**
     * @var AlmaBridgeInterface
     */
    protected $api;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var SecurityHelper
     */
    protected $securityHelper;
    /**
     * @var LoggerInterface
     */
    private  $logger;


    /**
     * NotifyAction constructor.
     * @param RequestStack $requestStack
     * @param SecurityHelper $securityHelper
     */
    public function __construct(LoggerInterface$logger, RequestStack $requestStack, SecurityHelper $securityHelper)
    {
        $this->logger = $logger;
        $this->apiClass = AlmaBridge::class;
        $this->requestStack = $requestStack;
        $this->securityHelper = $securityHelper;
    }

    /**
     * @param Notify $request
     */
    public function execute($request): void
    {
        $this->logger->info('Alma - Start execute', []);
        RequestNotSupportedException::assertSupports($this, $request);
        $httpRequest = $this->getCurrentRequest();



        $payment_id = $this->getQueryPaymentId($httpRequest);
        $this->logger->info('Alma - Payment id ok ', [$payment_id]);
        $signature = $this->getHeaderSignature($httpRequest);
        $this->logger->info('Alma - Signature ok ', [$signature]);

        $this->checkSignature($payment_id, $signature);
        $this->logger->info('Alma - checkSignature ok ', []);

        /** @var PaymentInterface $payment */
        $payment = $request->getFirstModel();
        $this->logger->info('Alma - Payment ok ', []);

        // Make sure the payment's details include the Alma payment ID
        $details = ArrayObject::ensureArrayObject($request->getModel());
        $details[AlmaBridgeInterface::DETAILS_KEY_PAYMENT_ID] = $payment_id;
        $payment->setDetails($details->getArrayCopy());

        // If payment hasn't been validated yet, validate its status against Alma's payment state
        if (in_array($payment->getState(), [PaymentInterfaceModel::STATE_NEW, PaymentInterfaceModel::STATE_PROCESSING], true)) {
            $this->logger->info('Alma - I validate Payment in notify', []);
            $this->validatePayment($payment);
            $this->logger->info('Alma - End i validate Payment in notify', []);
        }

        // $details is the request's model here, but we used a copy above passed down the ValidatePaymentAction through
        // the $payment object, which is the model of our own request.
        // Since Payum will use whatever is in the original details model to overwrite the payment's details data, we
        // need to make sure we copy everything that was set on the payment itself back to the NotifyRequest model.
        $details->replace($payment->getDetails());
        $this->logger->info('Alma - Replace OK ', []);

        // Down here means the callback has been correctly handled, regardless of the final payment state
        $this->logger->info('Alma - Script END', []);
        $this->returnHttpResponse(["success" => true, "state" => $payment->getDetails()[AlmaBridgeInterface::DETAILS_KEY_IS_VALID]]);

    }

    /**
     * Get signature from header or return error
     *
     * @param Request $httpRequest
     * @return string
     * @throws HttpResponse
     */
    private function getHeaderSignature(Request $httpRequest): string
    {
        $signature = $httpRequest->headers->get(strtolower(PaymentValidator::HEADER_SIGNATURE_KEY));
        if (!$signature) {
            $error = [
                "error" => true,
                "message" => 'No signature provided in IPN callback'
            ];
            $this->returnHttpResponse($error, Response::HTTP_FORBIDDEN);
        }

        return $signature;
    }

    /**
     * Get payment ID from query or return error
     *
     * @param Request $httpRequest
     * @return string
     * @throws HttpResponse
     */
    private function getQueryPaymentId(Request $httpRequest): string
    {
        $payment_id = $httpRequest->query->get(AlmaBridgeInterface::QUERY_PARAM_PID);
        if (!$payment_id) {
            $error = [
                "error" => true,
                "message" => 'No payment ID provided in IPN callback'
            ];
            $this->returnHttpResponse($error, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $payment_id;
    }

    /**
     * Get current request in requestStack or return error
     *
     * @return Request
     * @throws HttpResponse
     */
    private function getCurrentRequest(): Request
    {
        $httpRequest = $this->requestStack->getCurrentRequest();
        if (!$httpRequest) {
            $error = [
                "error" => true,
                "message" => 'No request found'
            ];
            $this->returnHttpResponse([$error], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $httpRequest;
    }

    /**
     * Check signature with php client library return forbidden if signature is not valid
     *
     * @param string $payment_id
     * @param string $signature
     * @return void
     * @throws HttpResponse
     */
    private function checkSignature(string $payment_id, string $signature): void
    {

        try {
            $this->securityHelper->isHmacValidated(
                $payment_id,
                $this->api->getGatewayConfig()->getActiveApiKey(),
                $signature
            );
        } catch (SecurityException $e) {
            $error = [
                "error" => true,
                "message" => $e->getMessage()
            ];
            $this->returnHttpResponse($error, Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Validate payment or return error
     *
     * @param PaymentInterface $payment
     * @return void
     * @throws HttpResponse
     */
    private function validatePayment(PaymentInterface $payment): void
    {
        try {
            $this->gateway->execute(new ValidatePayment($payment));
        } catch (\Exception $e) {
            $error = [
                "error" => true,
                "message" => $e->getMessage()
            ];
            $this->returnHttpResponse($error, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * HTTP Response factory
     *
     * @param array $message
     * @param int $code
     * @return void
     * @throws HttpResponse
     */
    private function returnHttpResponse(array $message, int $code = Response::HTTP_OK): void
    {
        throw new HttpResponse(
            json_encode($message),
            $code,
            ["content-type" => "application/json"]
        );
    }


    /**
     * Check if the request is supported
     *
     * @param $request
     * @return bool
     */
    public function supports($request): bool
    {
        return $request instanceof Notify
            && $request->getModel() instanceof ArrayAccess
            && $request->getFirstModel() instanceof PaymentInterface;
    }
}
