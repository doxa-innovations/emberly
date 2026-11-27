import { gsap } from 'gsap';

export const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function tweenNumber(from, to, onUpdate, duration = 0.6) {
    if (reducedMotion() || from === to) {
        onUpdate(to);

        return null;
    }

    const state = { value: from };

    return gsap.to(state, { value: to, duration, ease: 'power3.out', onUpdate: () => onUpdate(Math.round(state.value)) });
}
