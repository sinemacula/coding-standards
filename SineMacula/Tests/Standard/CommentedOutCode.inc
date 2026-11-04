<?php

declare(strict_types = 1);

namespace App\Onboarding;

/**
 * Lists the steps of an onboarding flow.
 *
 * @author      Ben Carey <bdmc@sinemacula.co.uk>
 * @copyright   2026 Sine Macula Limited
 */
final class StepList
{
    /**
     * The steps in the order they run.
     *
     * @return list<string>
     */
    public function steps(): array
    {
        // Upload step: Utility Bill (min: 0, max: 4)
        $steps = ['upload'];

        // Review step: Manual Check (min: 1, max: 2)
        $steps[] = 'review';

        // $steps[] = $this->legacyStep();
        $steps[] = 'confirm';

        // $last = $count - 1;
        $steps[] = 'done';

        // return array_reverse($steps);
        return $steps;
    }
}
