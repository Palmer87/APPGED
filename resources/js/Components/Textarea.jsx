import React from 'react';

export default function Textarea({
    id,
    label,
    error,
    rows = 3,
    className = '',
    required = false,
    ...props
}) {
    return (
        <div className="w-full">
            {label && (
                <label htmlFor={id} className="block text-sm font-medium text-slate-700 mb-1">
                    {label} {required && <span className="text-rose-500">*</span>}
                </label>
            )}
            <textarea
                id={id}
                rows={rows}
                required={required}
                className={`w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${
                    error ? 'border-rose-400 focus:ring-rose-400 focus:border-rose-400 bg-rose-50/20' : 'border-slate-300 bg-white hover:border-slate-400'
                } ${className}`}
                {...props}
            />
            {error && <p className="mt-1 text-xs text-rose-600 font-medium">{error}</p>}
        </div>
    );
}
