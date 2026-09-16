import React from 'react';

export default function Select({
    id,
    label,
    error,
    options = [],
    className = '',
    required = false,
    placeholder = 'Sélectionnez une option',
    children,
    ...props
}) {
    return (
        <div className="w-full">
            {label && (
                <label htmlFor={id} className="block text-sm font-medium text-slate-700 mb-1">
                    {label} {required && <span className="text-rose-500">*</span>}
                </label>
            )}
            <select
                id={id}
                required={required}
                className={`w-full rounded-lg border px-3 py-2 text-sm text-slate-900 bg-white shadow-xs transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${
                    error ? 'border-rose-400 focus:ring-rose-400 focus:border-rose-400 bg-rose-50/20' : 'border-slate-300 hover:border-slate-400'
                } ${className}`}
                {...props}
            >
                {placeholder && <option value="">{placeholder}</option>}
                {children ? children : options.map((opt) => (
                    <option key={opt.value ?? opt.id} value={opt.value ?? opt.id}>
                        {opt.label ?? opt.name}
                    </option>
                ))}
            </select>
            {error && <p className="mt-1 text-xs text-rose-600 font-medium">{error}</p>}
        </div>
    );
}
