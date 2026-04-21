<?php

declare(strict_types=1);

namespace Alma\SyliusPaymentPlugin\Storage;

use Alma\API\Endpoints\Results\Eligibility;

final class EligibilityStorage
{
    /** @var array<int, Eligibility> */
    private $eligibilities = [];

    public function store(int $installmentsCount, Eligibility $eligibility): void
    {
        $this->eligibilities[$installmentsCount] = $eligibility;
    }

    public function get(int $installmentsCount): ?Eligibility
    {
        return $this->eligibilities[$installmentsCount] ?? null;
    }

    public function has(int $installmentsCount): bool
    {
        return isset($this->eligibilities[$installmentsCount]);
    }
}
