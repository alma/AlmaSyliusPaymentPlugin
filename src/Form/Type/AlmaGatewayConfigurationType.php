<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Form\Type;

use Alma\API\Client as AlmaClient;
use Alma\SyliusPaymentPlugin\Bridge\AlmaBridgeInterface;
use Alma\SyliusPaymentPlugin\Payum\Gateway\GatewayConfigInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;


final class AlmaGatewayConfigurationType extends AbstractType
{
    /** @var TranslatorInterface */
    private $translator;

    /**
     * @var AlmaBridgeInterface
     */
    private $almaBridge;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array<string, string>
     */
    private $errors;

    public function __construct(
        TranslatorInterface $translator,
        AlmaBridgeInterface $almaBridge,
        LoggerInterface $logger
    ) {
        $this->translator = $translator;
        $this->almaBridge = $almaBridge;
        $this->logger = $logger;

        $this->errors = [];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // ->add(GatewayConfigInterface::CONFIG_LIVE_API_KEY, TextType::class, [
            //     'label' => 'alma_sylius_payment_plugin.config.live_api_key_label',
            //     'help' => $this->translator->trans('alma_sylius_payment_plugin.config.find_api_keys_in_dashboard'),
            //     'help_html' => true,
            // ])
            // ->add(GatewayConfigInterface::CONFIG_TEST_API_KEY, TextType::class, [
            //     'label' => 'alma_sylius_payment_plugin.config.test_api_key_label',
            //     'help' => $this->translator->trans('alma_sylius_payment_plugin.config.find_api_keys_in_dashboard'),
            //     'help_html' => true,
            // ])
            // ->add(
            //     GatewayConfigInterface::CONFIG_API_MODE,
            //     ChoiceType::class,
            //     [
            //         'choices' => [
            //             $this->translator->trans('alma_sylius_payment_plugin.api.live_mode') => AlmaClient::LIVE_MODE,
            //             $this->translator->trans('alma_sylius_payment_plugin.api.test_mode') => AlmaClient::TEST_MODE,
            //         ],
            //         'label' => $this->translator->trans('alma_sylius_payment_plugin.config.api_mode_label'),
            //         'help' => $this->translator->trans('alma_sylius_payment_plugin.config.api_mode_tip'),
            //     ]
            // )
            ->add(
                GatewayConfigInterface::CONFIG_INSTALLMENTS_COUNT,
                ChoiceType::class,
                [
                    'choices' => [
                        $this->translator->trans('alma_sylius_payment_plugin.config.installments_count_choice_label',
                            ['%installments_count%' => 2]) => 2,
                        $this->translator->trans('alma_sylius_payment_plugin.config.installments_count_choice_label',
                            ['%installments_count%' => 3]) => 3,
                        $this->translator->trans('alma_sylius_payment_plugin.config.installments_count_choice_label',
                            ['%installments_count%' => 4]) => 4,
                    ],
                    'label' => $this->translator->trans('alma_sylius_payment_plugin.config.installments_count_label'),
                ]
            )
            ->add(
                GatewayConfigInterface::CONFIG_PAYMENT_PAGE_MODE,
                ChoiceType::class,
                [
                    'choices' => [
                        $this->translator->trans(
                            'alma_sylius_payment_plugin.config.payment_page_mode_in_page'
                        ) => GatewayConfigInterface::PAYMENT_PAGE_MODE_IN_PAGE,
                        $this->translator->trans(
                            'alma_sylius_payment_plugin.config.payment_page_mode_redirect'
                        ) => GatewayConfigInterface::PAYMENT_PAGE_MODE_REDIRECT,
                    ],
                    'label' => $this->translator->trans('alma_sylius_payment_plugin.config.payment_page_mode_label'),
                ]
                );
            // ->add(GatewayConfigInterface::CONFIG_MERCHANT_ID, HiddenType::class);

        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit'])
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    public function onPreSetData(FormEvent $event): void
    {
        $data = ArrayObject::ensureArrayObject($event->getData());

        // Set default values for the different form fields (useful for gateway creations)
        $data->defaults([
            GatewayConfigInterface::CONFIG_INSTALLMENTS_COUNT => 3,
            GatewayConfigInterface::CONFIG_PAYMENT_PAGE_MODE => GatewayConfigInterface::PAYMENT_PAGE_MODE_IN_PAGE
        ]);

        $event->setData($data->getArrayCopy());
    }

    public function onPreSubmit(FormEvent $event): void
    {
        $this->errors = [];
        $data = ArrayObject::ensureArrayObject($event->getData());

        // At this point we know $data contains an API key we can try to connect with
        $this->almaBridge->initialize($data);
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $form = $event->getForm();

        foreach ($this->errors as $field => $error) {
            $form->get($field)->addError(new FormError($this->translator->trans($error)));
        }
    }
}
