const NANOS_PER_DOLLAR = 1_000_000_000;

const formatters = new Map();

function formatter(digits) {
    if (!formatters.has(digits)) {
        formatters.set(digits, new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: digits, maximumFractionDigits: digits }));
    }

    return formatters.get(digits);
}

export function usd(nanos) {
    const abs = Math.abs(nanos);
    const digits = abs >= NANOS_PER_DOLLAR || abs === 0 ? 2 : abs >= 100_000 ? 4 : 6;

    return formatter(digits).format(nanos / NANOS_PER_DOLLAR);
}

export function exactUsd(nanos) {
    const digits = String(Math.abs(nanos)).padStart(10, '0');

    return `${nanos < 0 ? '-' : ''}$${digits.slice(0, -9)}.${digits.slice(-9)}`;
}
