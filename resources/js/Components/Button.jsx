import React from 'react';

export default function Button({
    type = 'button',
    variant = 'primary',
    size = 'md',
    className = '',
    disabled = false,
    loading = false,
    children,
    ...props
}) {
    const baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-60 disabled:cursor-not-allowed';

    const variants = {
        primary: 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs focus:ring-indigo-500 active:bg-indigo-800',
        secondary: 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 shadow-xs focus:ring-indigo-500',
        danger: 'bg-rose-600 hover:bg-rose-700 text-white shadow-xs focus:ring-rose-500 active:bg-rose-800',
        success: 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs focus:ring-emerald-500 active:bg-emerald-800',
        warning: 'bg-amber-600 hover:bg-amber-700 text-white shadow-xs focus:ring-amber-500 active:bg-amber-800',
        ghost: 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:ring-slate-400',
    };

    const sizes = {
        sm: 'px-2.5 py-1.5 text-xs gap-1.5',
        md: 'px-4 py-2 text-sm gap-2',
        lg: 'px-5 py-2.5 text-base gap-2.5',
    };

    return (
        <button
            type={type}
            disabled={disabled || loading}
            className={`${baseClasses} ${variants[variant] || variants.primary} ${sizes[size] || sizes.md} ${className}`}
            {...props}
        >
            {loading && (
                <svg className="animate-spin -ml-0.5 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            )}
            {children}
        </button>
    );
}
