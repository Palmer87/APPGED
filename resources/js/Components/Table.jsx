import React from 'react';

export default function Table({
    headers = [],
    children,
    className = '',
}) {
    return (
        <div className={`overflow-x-auto rounded-lg border border-slate-200 bg-white ${className}`}>
            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                {headers.length > 0 && (
                    <thead className="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            {headers.map((h, idx) => (
                                <th key={idx} scope="col" className="px-6 py-3.5 whitespace-nowrap">
                                    {h}
                                </th>
                            ))}
                        </tr>
                    </thead>
                )}
                <tbody className="divide-y divide-slate-200 bg-white text-slate-700">
                    {children}
                </tbody>
            </table>
        </div>
    );
}
