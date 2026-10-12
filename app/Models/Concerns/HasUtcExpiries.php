<?php

namespace App\Models\Concerns;

/**
 * UTC-explicit expiry comparisons.
 *
 * Security timestamps (invitations, grants, trials, token expiries) are
 * stored as naive UTC wall-clock values and MUST be created with
 * now('UTC'). Comparing via Eloquent's mutated Carbon (parsed in the
 * request's runtime timezone) can shift results by the UTC offset, so all
 * checks here parse the raw attribute as UTC and compare absolutely.
 */
trait HasUtcExpiries
{
    protected function utcFuture(string $attr): bool
    {
        $raw = $this->attributes[$attr] ?? null;
        if (! $raw) {
            return true; // no expiry set = does not expire
        }
        $moment = $raw instanceof \DateTimeInterface
            ? \Carbon\Carbon::instance($raw)->setTimezone('UTC')
            : \Carbon\Carbon::parse($raw, 'UTC');

        return $moment->greaterThan(\Carbon\Carbon::now('UTC'));
    }

    protected function utcPast(string $attr): bool
    {
        $raw = $this->attributes[$attr] ?? null;
        if (! $raw) {
            return false;
        }
        $moment = $raw instanceof \DateTimeInterface
            ? \Carbon\Carbon::instance($raw)->setTimezone('UTC')
            : \Carbon\Carbon::parse($raw, 'UTC');

        return $moment->lessThan(\Carbon\Carbon::now('UTC'));
    }
}
