import { useEffect, useRef, useState } from 'react';

/**
 * Returns true when the visitor asked the OS to reduce motion.
 * Listens for live changes and cleans up its listener.
 */
export function usePrefersReducedMotion() {
    const [prefersReducedMotion, setPrefersReducedMotion] = useState(() => {
        if (typeof window === 'undefined' || !window.matchMedia) {
            return false;
        }
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    });

    useEffect(() => {
        if (typeof window === 'undefined' || !window.matchMedia) {
            return undefined;
        }
        const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
        const handleChange = (event) => setPrefersReducedMotion(event.matches);
        mediaQuery.addEventListener('change', handleChange);
        return () => mediaQuery.removeEventListener('change', handleChange);
    }, []);

    return prefersReducedMotion;
}

/**
 * Observes an element and flips to true the first time it enters the viewport.
 * The observer is disconnected as soon as it fires (or on unmount).
 */
export function useInView({ threshold = 0.2, rootMargin = '0px 0px -10% 0px', once = true } = {}) {
    const elementRef = useRef(null);
    const [isInView, setIsInView] = useState(false);

    useEffect(() => {
        const element = elementRef.current;
        if (!element) {
            return undefined;
        }
        if (typeof IntersectionObserver === 'undefined') {
            setIsInView(true);
            return undefined;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setIsInView(true);
                    if (once) {
                        observer.disconnect();
                    }
                } else if (!once) {
                    setIsInView(false);
                }
            },
            { threshold, rootMargin },
        );

        observer.observe(element);
        return () => observer.disconnect();
    }, [threshold, rootMargin, once]);

    return [elementRef, isInView];
}

/**
 * Plays a list of timed steps once `isActive` becomes true.
 * Returns the current step index and a `replay` function.
 * With reduced motion, jumps straight to the final step.
 *
 * @param {boolean} isActive
 * @param {number[]} stepDelays Delay (ms) before entering each step after the first one.
 */
export function useStepSequence(isActive, stepDelays) {
    const prefersReducedMotion = usePrefersReducedMotion();
    const [currentStep, setCurrentStep] = useState(0);
    const [runId, setRunId] = useState(0);
    const delaysKey = stepDelays.join(',');

    useEffect(() => {
        if (!isActive) {
            return undefined;
        }
        if (prefersReducedMotion) {
            setCurrentStep(stepDelays.length);
            return undefined;
        }

        setCurrentStep(0);
        const timers = [];
        let elapsed = 0;
        stepDelays.forEach((delay, index) => {
            elapsed += delay;
            timers.push(window.setTimeout(() => setCurrentStep(index + 1), elapsed));
        });

        return () => timers.forEach((timer) => window.clearTimeout(timer));
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isActive, prefersReducedMotion, runId, delaysKey]);

    const replay = () => setRunId((previous) => previous + 1);

    return { currentStep, replay, totalSteps: stepDelays.length };
}
