import React from 'react';

export default function Card({
    title,
    subtitle,
    actions,
    children,
    className = '',
    headerClassName = '',
    bodyClassName = '',
}) {
    return (
        <div className={`rounded-xl border border-slate-200 bg-white shadow-xs overflow-hidden ${className}`}>
            {(title || subtitle || actions) && (
                <div className={`flex items-center justify-between border-b border-slate-100 px-6 py-4 ${headerClassName}`}>
                    <div>
                        {title && <h3 className="text-base font-semibold text-slate-900">{title}</h3>}
                        {subtitle && <p className="text-xs text-slate-500 mt-0.5">{subtitle}</p>}
                    </div>
                    {actions && <div className="flex items-center gap-2">{actions}</div>}
                </div>
            )}
            <div className={`p-6 ${bodyClassName}`}>{children}</div>
        </div>
    );
}
