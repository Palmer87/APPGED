import React from 'react';
import Button from './Button';

export default function EmptyState({
    title = 'Aucun élément trouvé',
    description = 'Il n\'y a aucun élément à afficher pour le moment.',
    actionLabel,
    onAction,
    icon,
    className = '',
}) {
    return (
        <div className={`flex flex-col items-center justify-center p-12 text-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 ${className}`}>
            <div className="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-4 shadow-2xs">
                {icon ? (
                    icon
                ) : (
                    <svg className="w-7 h-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                )}
            </div>
            <h3 className="text-base font-semibold text-slate-800 mb-1">{title}</h3>
            <p className="text-sm text-slate-500 max-w-sm mb-6">{description}</p>
            {actionLabel && onAction && (
                <Button onClick={onAction} variant="primary" size="md">
                    {actionLabel}
                </Button>
            )}
        </div>
    );
}
