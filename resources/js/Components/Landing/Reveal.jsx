import React from 'react';
import { useInView } from './useLandingMotion';

/**
 * Fades and lifts its children into place the first time they scroll into view.
 */
export default function Reveal({ as: Component = 'div', delay = 0, className = '', style = {}, children, ...props }) {
    const [elementRef, isInView] = useInView();

    return (
        <Component
            ref={elementRef}
            className={`lp-reveal ${isInView ? 'is-visible' : ''} ${className}`}
            style={{ '--lp-delay': `${delay}ms`, ...style }}
            {...props}
        >
            {children}
        </Component>
    );
}

/**
 * Consistent section heading block (eyebrow + title + lead).
 */
export function SectionHeading({ eyebrow, title, lead, align = 'center', tone = 'light', id }) {
    const isDark = tone === 'dark';
    const alignment = align === 'center' ? 'text-center mx-auto' : 'text-left';

    return (
        <Reveal className={`max-w-3xl ${alignment}`}>
            {eyebrow && (
                <p
                    className={`inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] ${
                        isDark
                            ? 'border-white/10 bg-white/5 text-cyan-300'
                            : 'border-blue-100 bg-blue-50 text-blue-700'
                    }`}
                >
                    <span className={`h-1.5 w-1.5 rounded-full ${isDark ? 'bg-cyan-400' : 'bg-blue-600'}`} aria-hidden="true" />
                    {eyebrow}
                </p>
            )}
            <h2
                id={id}
                className={`mt-5 text-3xl font-bold tracking-tight sm:text-4xl lg:text-[2.75rem] lg:leading-[1.1] ${
                    isDark ? 'text-white' : 'text-slate-950'
                }`}
            >
                {title}
            </h2>
            {lead && (
                <p className={`mt-5 text-base leading-relaxed sm:text-lg ${isDark ? 'text-slate-400' : 'text-ink'}`}>
                    {lead}
                </p>
            )}
        </Reveal>
    );
}

/**
 * Small pill making clear that a visual uses fictitious sample data.
 */
export function IllustrativeBadge({ tone = 'light', children = 'Aperçu illustratif · données fictives' }) {
    return (
        <span
            className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ${
                tone === 'dark'
                    ? 'bg-white/5 text-slate-400 ring-1 ring-white/10'
                    : 'bg-slate-100 text-slate-500 ring-1 ring-slate-200'
            }`}
        >
            <span className="h-1.5 w-1.5 rounded-full bg-amber-400" aria-hidden="true" />
            {children}
        </span>
    );
}
