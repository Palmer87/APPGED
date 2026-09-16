import React from 'react';
import { Link } from '@inertiajs/react';

export default function Breadcrumb({ items = [] }) {
    if (!items.length) return null;

    return (
        <nav className="flex text-sm text-slate-500 mb-4" aria-label="Breadcrumb">
            <ol className="inline-flex items-center space-x-1 sm:space-x-2">
                <li className="inline-flex items-center">
                    <Link
                        href="/dashboard"
                        className="inline-flex items-center text-xs font-medium text-slate-500 hover:text-indigo-600 transition"
                    >
                        <svg className="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Accueil
                    </Link>
                </li>
                {items.map((item, index) => {
                    const isLast = index === items.length - 1;
                    return (
                        <li key={index} className="inline-flex items-center">
                            <svg className="w-4 h-4 text-slate-400 mx-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                            </svg>
                            {isLast || !item.href ? (
                                <span className="text-xs font-semibold text-slate-800" aria-current="page">
                                    {item.label}
                                </span>
                            ) : (
                                <Link
                                    href={item.href}
                                    className="text-xs font-medium text-slate-500 hover:text-indigo-600 transition"
                                >
                                    {item.label}
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
