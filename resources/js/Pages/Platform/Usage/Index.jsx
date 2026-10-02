import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Activity,
    Search,
    AlertTriangle,
    Database,
    Users,
    FileText,
    Cpu,
    Building2,
    ShieldAlert,
    ExternalLink
} from 'lucide-react';

export default function UsageIndex({
    globalMetrics = {},
    organizations,
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/usage', { search: search.trim() || undefined }, { preserveState: true });
    };

    const getAlertBadge = (level) => {
        switch (level) {
            case 'exceeded_100':
                return (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30 animate-pulse">
                        <ShieldAlert className="w-3.5 h-3.5" /> 100%+ Dépassé
                    </span>
                );
            case 'danger_90':
                return (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                        <AlertTriangle className="w-3.5 h-3.5" /> 90%+ Critique
                    </span>
                );
            case 'warning_80':
                return (
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-500/20 text-blue-400 border border-blue-500/30">
                        80%+ Vigilance
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-400">
                        Normal
                    </span>
                );
        }
    };

    const ProgressBar = ({ percentage, alertLevel }) => {
        let barColor = 'bg-indigo-500';
        if (alertLevel === 'exceeded_100') barColor = 'bg-rose-500';
        else if (alertLevel === 'danger_90') barColor = 'bg-amber-500';
        else if (alertLevel === 'warning_80') barColor = 'bg-blue-500';

        return (
            <div className="w-full bg-slate-800/80 rounded-full h-1.5 overflow-hidden">
                <div
                    className={`${barColor} h-1.5 rounded-full transition-all duration-500`}
                    style={{ width: `${Math.min(percentage, 100)}%` }}
                />
            </div>
        );
    };

    return (
        <PlatformLayout title="Quotas & Consommation Globale">
            <Head title="Usage & Quotas — Console Propriétaire" />

            <div className="space-y-6">
                {/* Global Metrics Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <Database className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Stockage Total</span>
                            <div className="text-xl font-black text-slate-100 mt-0.5">
                                {globalMetrics.total_storage_formatted || '0 MB'}
                            </div>
                        </div>
                    </div>

                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-2xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400">
                            <Users className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Utilisateurs Clients</span>
                            <div className="text-xl font-black text-slate-100 mt-0.5">
                                {globalMetrics.total_users || 0}
                            </div>
                        </div>
                    </div>

                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                            <FileText className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Documents Hébergés</span>
                            <div className="text-xl font-black text-slate-100 mt-0.5">
                                {globalMetrics.total_documents || 0}
                            </div>
                        </div>
                    </div>

                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-5 flex items-center gap-4">
                        <div className="w-12 h-12 rounded-2xl bg-violet-500/10 border border-violet-500/20 flex items-center justify-center text-violet-400">
                            <Cpu className="w-6 h-6" />
                        </div>
                        <div>
                            <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Pages OCR Traitées</span>
                            <div className="text-xl font-black text-slate-100 mt-0.5">
                                {globalMetrics.total_ocr_pages || 0}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filter and Search Bar */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-4 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex items-center gap-3 w-full sm:w-auto">
                        <div className="relative w-full sm:w-80">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="Rechercher une organisation..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-10 pr-4 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            />
                        </div>
                        <button
                            type="submit"
                            className="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold rounded-xl transition"
                        >
                            Filtrer
                        </button>
                    </form>
                    <div className="text-xs text-slate-400">
                        Alertes automatiques activées : seuils de vigilance à 80 %, 90 % et 100 %.
                    </div>
                </div>

                {/* Quotas Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl shadow-slate-950/40">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-400">
                            <thead className="bg-slate-950/60 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80">
                                <tr>
                                    <th className="px-6 py-4">Organisation</th>
                                    <th className="px-6 py-4">Plan</th>
                                    <th className="px-6 py-4">Alerte Quota</th>
                                    <th className="px-6 py-4">Utilisateurs</th>
                                    <th className="px-6 py-4">Stockage</th>
                                    <th className="px-6 py-4">OCR (Mois)</th>
                                    <th className="px-6 py-4">Directions</th>
                                    <th className="px-6 py-4 text-right">Fiche</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {organizations.data && organizations.data.length > 0 ? (
                                    organizations.data.map((org) => (
                                        <tr key={org.organization_id} className="hover:bg-slate-800/30 transition">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2">
                                                    <Building2 className="w-4 h-4 text-slate-500" />
                                                    <Link
                                                        href={`/platform/organizations/${org.organization_id}`}
                                                        className="font-bold text-slate-200 hover:text-indigo-400 transition"
                                                    >
                                                        {org.organization_name}
                                                    </Link>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                                    {org.plan_name}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                {getAlertBadge(org.highest_alert)}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="w-36 space-y-1">
                                                    <div className="flex justify-between text-xs">
                                                        <span className="text-slate-300 font-semibold">{org.users.current}</span>
                                                        <span className="text-slate-500">/ {org.users.max}</span>
                                                    </div>
                                                    <ProgressBar percentage={org.users.percentage} alertLevel={org.users.alert_level} />
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="w-36 space-y-1">
                                                    <div className="flex justify-between text-xs">
                                                        <span className="text-slate-300 font-semibold">{org.storage.current_formatted}</span>
                                                        <span className="text-slate-500">/ {org.storage.max_formatted}</span>
                                                    </div>
                                                    <ProgressBar percentage={org.storage.percentage} alertLevel={org.storage.alert_level} />
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="w-36 space-y-1">
                                                    <div className="flex justify-between text-xs">
                                                        <span className="text-slate-300 font-semibold">{org.ocr_pages.current}</span>
                                                        <span className="text-slate-500">/ {org.ocr_pages.max}</span>
                                                    </div>
                                                    <ProgressBar percentage={org.ocr_pages.percentage} alertLevel={org.ocr_pages.alert_level} />
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-xs font-semibold text-slate-300">
                                                {org.directions.current} / {org.directions.max}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <Link
                                                    href={`/platform/organizations/${org.organization_id}`}
                                                    className="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 transition"
                                                >
                                                    Détails <ExternalLink className="w-3.5 h-3.5" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="px-6 py-12 text-center text-slate-500">
                                            <Activity className="w-10 h-10 mx-auto mb-3 opacity-40" />
                                            Aucune organisation trouvée.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {organizations.links && organizations.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between">
                            <span className="text-xs text-slate-500">
                                Total : {organizations.total} organisation(s)
                            </span>
                            <div className="flex gap-1">
                                {organizations.links.map((link, idx) => (
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
            </div>
        </PlatformLayout>
    );
}
