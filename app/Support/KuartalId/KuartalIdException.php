<?php

namespace App\Support\KuartalId;

use RuntimeException;
use Throwable;

/**
 * Anything that goes wrong talking to Kuartal ID (id.kuartal.id) or
 * validating what it sent back. Callers catch this and show the person a
 * friendly "sign-in failed, try again" message instead of a 500 page.
 *
 * The exception message is for logs only and never contains tokens or
 * personal data -- just what failed and the HTTP status / library error.
 */
class KuartalIdException extends RuntimeException
{
    /** True when Kuartal ID rejected our token (expired/revoked) -- sign in again. */
    public bool $unauthorized = false;

    /** HTTP status Kuartal ID answered with, when it answered at all. */
    public ?int $status = null;

    public static function unreachable(string $what, ?Throwable $previous = null): self
    {
        return new self("Kuartal ID {$what}: could not connect (timeout or network error).", 0, $previous);
    }

    public static function failed(string $what, int $status, ?string $error = null): self
    {
        $e = new self("Kuartal ID {$what}: HTTP {$status}".($error ? " ({$error})" : '').'.');
        $e->status = $status;
        $e->unauthorized = $status === 401;

        return $e;
    }

    public static function invalid(string $reason, ?Throwable $previous = null): self
    {
        return new self("Kuartal ID response rejected: {$reason}.", 0, $previous);
    }
}
