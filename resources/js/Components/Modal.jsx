import React, { useEffect } from 'react';

export default function Modal({
    isOpen = false,
    onClose,
    title,
    children,
    maxWidth = 'max-w-lg',
}) {
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && isOpen && onClose) {
                onClose();
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                {/* Backdrop */}
                <div
                    className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                    onClick={onClose}
                    aria-hidden="true"
                />

                {/* Modal box */}
                <div
                    className={`relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full ${maxWidth} border border-slate-200`}
                >
                    {title && (
                        <div className="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                            <h3 className="text-lg font-semibold text-slate-900">{title}</h3>
                            <button
                                type="button"
                                onClick={onClose}
                                className="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    )}
                    <div className="p-6">{children}</div>
                </div>
            </div>
        </div>
    );
}
