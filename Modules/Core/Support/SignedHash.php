<?php

namespace Modules\Core\Support;

/**
 * Unforgeable "{id}-{signature}" tokens for QR verification URLs. The signature binds the id to a document type,
 * so a hash made for an ID card does not verify as a receipt.
 */
final class SignedHash
{
    public static function make(string $type, int $id): string
    {
        return $id.'-'.self::signature($type, $id);
    }

    /**
     * @return int|null The id when the signature is valid.
     */
    public static function parse(string $type, string $hash): ?int
    {
        [$id, $signature] = array_pad(explode('-', $hash, 2), 2, '');

        if (! ctype_digit($id) || ! hash_equals(self::signature($type, (int) $id), $signature)) {
            return null;
        }

        return (int) $id;
    }

    private static function signature(string $type, int $id): string
    {
        return substr(hash_hmac('sha256', "{$type}|{$id}", (string) config('app.key')), 0, 16);
    }
}
