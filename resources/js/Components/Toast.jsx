import React, { useEffect, useState } from 'react';
import { CheckCircle2, AlertCircle, X } from 'lucide-react';

export default function Toast({ message, type = 'success', onClose, duration = 4000 }) {
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        if (!message) return;
        setVisible(true);
        const timer = setTimeout(() => {
            setVisible(false);
            if (onClose) onClose();
        }, duration);

        return () => clearTimeout(timer);
    }, [message, duration, onClose]);

    if (!visible || !message) return null;

    const isSuccess = type === 'success';

    return (
        <div className="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border text-sm font-medium transition-all animate-bounce-short bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700">
            {isSuccess ? (
                <CheckCircle2 className="w-5 h-5 text-emerald-500 shrink-0" />
            ) : (
                <AlertCircle className="w-5 h-5 text-rose-500 shrink-0" />
            )}
            <span className="text-slate-800 dark:text-slate-100">{message}</span>
            <button
                type="button"
                onClick={() => {
                    setVisible(false);
                    if (onClose) onClose();
                }}
                className="ml-2 text-slate-400 hover:text-slate-600 transition"
            >
                <X className="w-4 h-4" />
            </button>
        </div>
    );
}
