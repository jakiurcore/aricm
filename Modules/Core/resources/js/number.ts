import { locale } from './i18n';

const DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

const MONTHS = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];

export function bnDigits(value: string | number | null | undefined, force?: boolean): string {
    const text = String(value ?? '');

    return (force ?? locale() === 'bn') ? text.replace(/\d/g, (digit) => DIGITS[Number(digit)]) : text;
}

export function formatNumber(value: number, decimals = 0): string {
    return bnDigits(value.toFixed(decimals));
}

/** "২১ অক্টোবর ২০২৬" in Bangla, "21 Oct 2026" in English. Accepts ISO date strings. */
export function formatDate(value: string | Date | null | undefined): string {
    if (!value) {
        return '';
    }

    const date = typeof value === 'string' ? new Date(value.length === 10 ? `${value}T00:00:00` : value) : value;

    if (locale() === 'bn') {
        return `${bnDigits(date.getDate(), true)} ${MONTHS[date.getMonth()]} ${bnDigits(date.getFullYear(), true)}`;
    }

    return date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
