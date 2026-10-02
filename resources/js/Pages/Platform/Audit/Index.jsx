import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    ShieldCheck,
    Search,
    Filter,
    Calendar,
    User,
    Building2,
    Eye,
    X,
    Code
} from 'lucide-react';

export default function AuditIndex({
    logs,
    platformUsers = [],
    organizations = [],
    filters = {},
}) {
    const [action, setAction] = useState(filters.action || '');
    const [platformUserId, setPlatformUserId] = useState(filters.platform_user_id || '');
    const [organizationId, setOrganizationId] = useState(filters.organization_id || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [detailLog, setDetailLog] = useState(null);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/audit', {
            action: action.trim() || undefined,
            platform_user_id: platformUserId || undefined,
            organization_id: organizationId || undefined,
            from: from || undefined,
            to: to || undefined,
        }, { preserveState: true });
    };

    const resetFilters = () => {
        setAction('');
        setPlatformUserId('');
        setOrganizationId('');
        setFrom('');
        setTo('');
        router.get('/platform/audit');
    };

    const getActionBadgeColor = (actionName) => {
        if (actionName.includes('suspended') || actionName.includes('cancelled') || actionName.includes('disabled')) {
            return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        }
        if (actionName.includes('reactivated') || actionName.includes('paid')) {
            return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        }
        if (actionName.includes('created') || actionName.includes('extended')) {
            return 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
        }
        return 'bg-slate-800 text-slate-300 border-slate-700';
    };

    return (
        <PlatformLayout title="Piste d'Audit Plateforme">
            <Head title="Audit Log — Console Propriétaire" />

            <div className="space-y-6">
                {/* Filters Bar */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-5 shadow-xl">
                    <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-slate-400 mb-1">Action</label>
                            <input
                                type="text"
                                placeholder="ex: organization.suspended"
                                value={action}
                                onChange={(e) => setAction(e.target.value)}
                                className="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-400 mb-1">Opérateur SaaS</label>
                            <select
                                value={platformUserId}
                                onChange={(e) => setPlatformUserId(e.target.value)}
                                className="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            >
                                <option value="">Tous les opérateurs</option>
                                {platformUsers.map((u) => (
                                    <option key={u.id} value={u.id}>{u.name}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-400 mb-1">Organisation Ciblée</label>
                            <select
                                value={organizationId}
                                onChange={(e) => setOrganizationId(e.target.value)}
                                className="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            >
                                <option value="">Toutes (ou Global)</option>
                                {organizations.map((org) => (
                                    <option key={org.id} value={org.id}>{org.name}</option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-400 mb-1">Date début</label>
                            <input
                                type="date"
                                value={from}
                                onChange={(e) => setFrom(e.target.value)}
                                className="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-400 mb-1">Date fin</label>
                            <input
                                type="date"
                                value={to}
                                onChange={(e) => setTo(e.target.value)}
                                className="w-full px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            />
                        </div>

                        <div className="sm:col-span-2 lg:col-span-5 flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                onClick={resetFilters}
                                className="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition"
                            >
                                Réinitialiser
                            </button>
                            <button
                                type="submit"
                                className="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition shadow-lg shadow-indigo-600/30"
                            >
                                Appliquer les filtres
                            </button>
                        </div>
                    </form>
                </div>

                {/* Audit Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl shadow-slate-950/40">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-400">
                            <thead className="bg-slate-950/60 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80">
                                <tr>
                                    <th className="px-6 py-4">Horodatage</th>
                                    <th className="px-6 py-4">Action</th>
                                    <th className="px-6 py-4">Opérateur</th>
                                    <th className="px-6 py-4">Organisation</th>
                                    <th className="px-6 py-4">Description</th>
                                    <th className="px-6 py-4">IP</th>
                                    <th className="px-6 py-4 text-right">Détails</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {logs.data && logs.data.length > 0 ? (
                                    logs.data.map((log) => (
                                        <tr key={log.id} className="hover:bg-slate-800/30 transition">
                                            <td className="px-6 py-4 text-xs font-mono text-slate-300">
                                                {log.created_at}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={`px-2.5 py-1 rounded-md text-xs font-mono font-bold border ${getActionBadgeColor(log.action)}`}>
                                                    {log.action}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                <div className="font-semibold text-slate-200">{log.platform_user_name}</div>
                                                <div className="text-slate-500">{log.platform_user_role || 'système'}</div>
                                            </td>
                                            <td className="px-6 py-4 text-xs font-medium text-slate-300">
                                                {log.organization_name}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-300 max-w-xs truncate">
                                                {log.description}
                                            </td>
                                            <td className="px-6 py-4 text-xs font-mono text-slate-500">
                                                {log.ip_address || '—'}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                {(log.old_values || log.new_values) && (
                                                    <button
                                                        onClick={() => setDetailLog(log)}
                                                        className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition"
                                                    >
                                                        <Code className="w-3.5 h-3.5" /> Diff
                                                    </button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="7" className="px-6 py-12 text-center text-slate-500">
                                            <ShieldCheck className="w-10 h-10 mx-auto mb-3 opacity-40" />
                                            Aucune trace d'audit trouvée pour ces critères.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {logs.links && logs.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between">
                            <span className="text-xs text-slate-500">
                                Total : {logs.total} événement(s)
                            </span>
                            <div className="flex gap-1">
                                {logs.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : link.url
                                                ? 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                                                : 'bg-slate-900 text-slate-600 cursor-not-allowed'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>

                {/* Diff Viewer Modal */}
                {detailLog && (
                    <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
                        <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-2xl shadow-2xl relative">
                            <button
                                onClick={() => setDetailLog(null)}
                                className="absolute top-6 right-6 text-slate-400 hover:text-slate-200"
                            >
                                <X className="w-5 h-5" />
                            </button>

                            <h3 className="text-base font-bold text-white mb-1">Détails de l'Audit #{detailLog.id}</h3>
                            <p className="text-xs text-indigo-400 font-mono mb-4">{detailLog.action} — {detailLog.created_at}</p>

                            <div className="space-y-4 text-xs font-mono">
                                {detailLog.old_values && (
                                    <div>
                                        <span className="text-rose-400 font-bold block mb-1">Valeurs Précédentes :</span>
                                        <pre className="p-3 bg-slate-950 rounded-xl border border-slate-800 overflow-x-auto text-slate-300">
                                            {JSON.stringify(detailLog.old_values, null, 2)}
                                        </pre>
                                    </div>
                                )}

                                {detailLog.new_values && (
                                    <div>
                                        <span className="text-emerald-400 font-bold block mb-1">Nouvelles Valeurs :</span>
                                        <pre className="p-3 bg-slate-950 rounded-xl border border-slate-800 overflow-x-auto text-slate-300">
                                            {JSON.stringify(detailLog.new_values, null, 2)}
                                        </pre>
                                    </div>
                                )}
                            </div>

                            <div className="mt-6 flex justify-end">
                                <button
                                    onClick={() => setDetailLog(null)}
                                    className="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition"
                                >
                                    Fermer
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </PlatformLayout>
    );
}
