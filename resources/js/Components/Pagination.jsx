import React from 'react';
import { Link } from '@inertiajs/react';

export default function Pagination({ pagination, className = '' }) {
    if (!pagination || !pagination.links || pagination.links.length <= 3) {
        return null;
    }

    return (
        <div className={`flex flex-col sm:flex-row items-center justify-between gap-4 py-3 px-2 ${className}`}>
            <div className="text-xs text-slate-500">
                Affichage de <span className="font-semibold text-slate-700">{pagination.from || 0}</span> à{' '}
                <span className="font-semibold text-slate-700">{pagination.to || 0}</span> sur{' '}
                <span className="font-semibold text-slate-700">{pagination.total || 0}</span> résultats
            </div>
            <div className="flex flex-wrap items-center gap-1">
                {pagination.links.map((link, idx) => {
                    const label = link.label
                        .replace('&laquo; Previous', '← Précédent')
                        .replace('Next &raquo;', 'Suivant →');

                    if (!link.url) {
                        return (
                            <span
                                key={idx}
                                className="px-3 py-1.5 text-xs text-slate-400 bg-slate-50 rounded-md border border-slate-200 cursor-not-allowed select-none"
                                dangerouslySetInnerHTML={{ __html: label }}
                            />
                        );
                    }

                    return (
                        <Link
                            key={idx}
                            href={link.url}
                            preserveScroll
                            preserveState
                            className={`px-3 py-1.5 text-xs rounded-md font-medium border transition ${
                                link.active
                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-xs'
                                    : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                            }`}
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                })}
            </div>
        </div>
    );
}
