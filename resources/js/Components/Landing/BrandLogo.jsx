import React from 'react';
import { Link } from '@inertiajs/react';

export const BRAND_NAME = 'APPGED';

/**
 * APPGED brand mark + wordmark.
 */
export default function BrandLogo({ tone = 'dark', compact = false, tagline = true }) {
    const isOnDark = tone === 'dark';

    return (
        <Link href="/" className="group flex items-center gap-2.5 rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400" aria-label={`${BRAND_NAME} — accueil`}>
            <span
                className={`relative flex shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white transition-transform duration-300 group-hover:scale-105 ${
                    compact ? 'h-9 w-9' : 'h-10 w-10'
                }`}
            >
                <svg className="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M7 4V20C7 20.5523 7.44772 21 8 21H18C18.5523 21 19 20.5523 19 20V8.5L14.5 4H8C7.44772 4 7 4.44772 7 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M14 4V9H19" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                    <path d="M4 8V17" stroke="currentColor" strokeWidth="2" strokeLinecap="round" opacity="0.6" />
                    <path d="M10 13H15" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                    <path d="M10 17H13" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
            </span>
            <span className="flex flex-col">
                <span className={`text-lg font-bold leading-none tracking-tight ${isOnDark ? 'text-white' : 'text-slate-950'}`}>
                    APP<span className={isOnDark ? 'text-cyan-400' : 'text-blue-600'}>GED</span>
                </span>
                {tagline && !compact && (
                    <span className={`mt-1 text-[10.5px] font-medium leading-none ${isOnDark ? 'text-slate-400' : 'text-slate-500'}`}>
                        Gestion électronique de documents
                    </span>
                )}
            </span>
        </Link>
    );
}
