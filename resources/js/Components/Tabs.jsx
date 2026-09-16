import React from 'react';

export default function Tabs({
    tabs = [],
    activeTab,
    onChange,
    className = '',
}) {
    return (
        <div className={`border-b border-slate-200 ${className}`}>
            <nav className="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                {tabs.map((tab) => {
                    const isActive = activeTab === tab.id;
                    return (
                        <button
                            key={tab.id}
                            type="button"
                            onClick={() => onChange(tab.id)}
                            className={`flex items-center gap-2 whitespace-nowrap py-3.5 px-1 border-b-2 font-medium text-sm transition ${
                                isActive
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'
                            }`}
                        >
                            {tab.icon}
                            {tab.label}
                            {tab.badge !== undefined && tab.badge !== null && (
                                <span
                                    className={`ml-1.5 py-0.5 px-2 rounded-full text-xs font-semibold ${
                                        isActive
                                            ? 'bg-indigo-100 text-indigo-700'
                                            : 'bg-slate-100 text-slate-600'
                                    }`}
                                >
                                    {tab.badge}
                                </span>
                            )}
                        </button>
                    );
                })}
            </nav>
        </div>
    );
}
