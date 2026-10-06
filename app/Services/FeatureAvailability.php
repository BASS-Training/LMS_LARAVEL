<?php

namespace App\Services;

use App\Models\FeatureSetting;
use InvalidArgumentException;

class FeatureAvailability
{
    private ?FeatureSetting $settings = null;

    public function bundlesEnabled(): bool
    {
        return $this->settings()->bundles_enabled;
    }

    public function learningPathsEnabled(): bool
    {
        return $this->settings()->learning_paths_enabled;
    }

    public function refundRequestsEnabled(): bool
    {
        return $this->settings()->refund_requests_enabled;
    }

    public function enabled(string $feature): bool
    {
        return match ($feature) {
            'bundles' => $this->bundlesEnabled(),
            'learning-paths' => $this->learningPathsEnabled(),
            'refund-requests' => $this->refundRequestsEnabled(),
            default => throw new InvalidArgumentException("Fitur tidak dikenal: {$feature}"),
        };
    }

    public function settings(): FeatureSetting
    {
        return $this->settings ??= FeatureSetting::current();
    }
}
