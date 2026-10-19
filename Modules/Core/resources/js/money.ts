import { bnDigits } from './number';

/** Whole poisha → "৳১,২০০" (Bangla digits, lakh grouping) or "৳1,200" in English. */
export function formatMoney(poisha: number): string {
    const negative = poisha < 0;
    const absolute = Math.abs(poisha);
    const taka = Math.floor(absolute / 100);
    const paisa = absolute % 100;
    const digits = String(taka);
    const grouped = digits.length > 3 ? digits.slice(0, -3).replace(/\B(?=(\d{2})+(?!\d))/g, ',') + ',' + digits.slice(-3) : digits;

    return bnDigits(`${negative ? '-' : ''}৳${grouped}${paisa > 0 ? '.' + String(paisa).padStart(2, '0') : ''}`);
}

/** Whole poisha → a plain taka string for an input ("1250.5"). */
export function poishaToTaka(poisha: number): string {
    return poisha === 0 ? '' : String(poisha / 100);
}
