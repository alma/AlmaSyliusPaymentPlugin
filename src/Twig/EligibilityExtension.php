<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Twig;

use Alma\API\Endpoints\Results\Eligibility;
use Alma\SyliusPaymentPlugin\Storage\EligibilityStorage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class EligibilityExtension extends AbstractExtension
{
    /** @var EligibilityStorage */
    private $eligibilityStorage;

    public function __construct(EligibilityStorage $eligibilityStorage)
    {
        $this->eligibilityStorage = $eligibilityStorage;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('alma_get_eligibility', [$this, 'getEligibility']),
        ];
    }

    public function getEligibility(int $installmentsCount): ?Eligibility
    {
        $eligibility = $this->eligibilityStorage->get($installmentsCount);

        if ($eligibility === null || !$eligibility->isEligible()) {
            return null;
        }

        return $eligibility;
    }
}
