<?php

namespace Modules\Fees\Support;

use InvalidArgumentException;
use JsonSerializable;
use Modules\Core\Support\Bangla;
use Modules\Core\Support\BanglaWords;
use Stringable;

/**
 * An amount of money held as whole poisha (৳1 = 100 poisha), so no float ever touches a balance.
 */
final readonly class Money implements JsonSerializable, Stringable
{
    private function __construct(public int $poisha) {}

    public static function poisha(int $poisha): self
    {
        return new self($poisha);
    }

    /**
     * From taka as a number or numeric string ("1250", "1250.50"); never use it for arithmetic, only for input.
     */
    public static function taka(int|float|string $taka): self
    {
        if (! is_numeric($taka)) {
            throw new InvalidArgumentException('Not a number: '.$taka);
        }

        return new self((int) round((float) $taka * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function plus(self $other): self
    {
        return new self($this->poisha + $other->poisha);
    }

    public function minus(self $other): self
    {
        return new self($this->poisha - $other->poisha);
    }

    /**
     * Percent of this amount, rounded down to a whole poisha.
     */
    public function percent(int|float $percent): self
    {
        return new self((int) floor($this->poisha * $percent / 100));
    }

    public function times(int $factor): self
    {
        return new self($this->poisha * $factor);
    }

    public function min(self $other): self
    {
        return $this->poisha <= $other->poisha ? $this : $other;
    }

    public function max(self $other): self
    {
        return $this->poisha >= $other->poisha ? $this : $other;
    }

    public function isZero(): bool
    {
        return $this->poisha === 0;
    }

    public function isPositive(): bool
    {
        return $this->poisha > 0;
    }

    public function equals(self $other): bool
    {
        return $this->poisha === $other->poisha;
    }

    public function toTaka(): float
    {
        return $this->poisha / 100;
    }

    /**
     * "৳১,২০০" or "৳১২,৫০,০০০.৫০" in Bangla; Indian digit grouping (lakh, crore) in both languages.
     */
    public function format(?bool $bangla = null): string
    {
        $negative = $this->poisha < 0;
        $taka = intdiv(abs($this->poisha), 100);
        $paisa = abs($this->poisha) % 100;

        $digits = (string) $taka;
        $grouped = strlen($digits) > 3 ? preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($digits, 0, -3)).','.substr($digits, -3) : $digits;
        $text = ($negative ? '-' : '').'৳'.$grouped.($paisa > 0 ? '.'.str_pad((string) $paisa, 2, '0', STR_PAD_LEFT) : '');

        return Bangla::digits($text, $bangla);
    }

    public function inWords(?bool $bangla = null): string
    {
        return BanglaWords::money($this->poisha, $bangla);
    }

    public function jsonSerialize(): int
    {
        return $this->poisha;
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
