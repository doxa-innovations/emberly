export const SIZES = [
    { value: '1024x1024', label: 'Square', ratio: '1 / 1' },
    { value: '1536x1024', label: 'Landscape', ratio: '3 / 2' },
    { value: '1024x1536', label: 'Portrait', ratio: '2 / 3' },
];

export const QUALITIES = [
    { value: 'low', label: 'Low' },
    { value: 'medium', label: 'Medium' },
    { value: 'high', label: 'High' },
];

export const TEST_KEYWORDS = [
    { keyword: '[succeed]', hint: 'Never fails at random' },
    { keyword: '[fail]', hint: 'Fails while processing' },
    { keyword: '[slow]', hint: 'Takes 60 to 90 seconds' },
    { keyword: '[duplicate]', hint: 'Every webhook arrives twice' },
    { keyword: '[blocked]', hint: 'Rejected by content policy' },
    { keyword: '[overloaded]', hint: 'Pixelforge answers 503' },
];

const FRIENDLY_ERRORS = {
    invalid_api_key: 'Image generation is unavailable right now.',
    content_policy_violation: "Pixelforge's content policy rejected this prompt.",
    daily_limit_exceeded: "Pixelforge's daily quota is used up. Try again after midnight UTC.",
    pixelforge_unavailable: 'Pixelforge stayed too busy to accept this generation.',
};

export const errorMessage = (error) => FRIENDLY_ERRORS[error?.code] ?? error?.message;

export const isInFlight = (generation) => generation.status === 'queued' || generation.status === 'processing';

export const sizeLabel = (value) => SIZES.find((size) => size.value === value)?.label ?? value;
