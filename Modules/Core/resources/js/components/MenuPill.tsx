import { bnDigits } from '../number';

/** The yellow count pill of a menu entry (the design's pending-verification badge). Nothing at zero. */
export function MenuPill({ count }: { count: number | null }) {
    if (!count) {
        return null;
    }

    return (
        <span
            className="grid place-items-center"
            style={{ minWidth: 22, height: 22, padding: '0 6px', borderRadius: 999, background: '#FFD21F', color: '#17212B', fontSize: 12, fontWeight: 700 }}
        >
            {bnDigits(count)}
        </span>
    );
}
