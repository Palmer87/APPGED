import React from 'react';
import { Head, Link } from '@inertiajs/react';
import PlatformLayout from '../../Layouts/PlatformLayout';
import {
    Building2,
    Users,
    FileText,
    HardDrive,
    ScanText,
    TrendingUp,
    ShieldAlert,
    Clock,
    ArrowUpRight,
    CheckCircle2,
    AlertTriangle,
    LifeBuoy
} from 'lucide-react';

export default function PlatformDashboard({
    metrics,
    recent_organizations = [],
    recent_tickets = [],
    recent_audit_logs = [],
}) {
    const formatNumber = (num) => new Intl.NumberFormat('fr-FR').format(num || 0);

    return (
        <PlatformLayout title="Tableau de bord Global SaaS">
            <Head title="Tableau de bord Propriétaire — GEDAPP" />

            <div className="space-y-8">
                {/* Executive Top Banner */}
                <div className="bg-gradient-to-r from-indigo-950 via-slate-900 to-purple-950 border border-indigo-900/40 rounded-3xl p-6 sm:p-8 relative overflow-hidden">
                    <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 mb-3">
                                <TrendingUp className="w-3.5 h-3.5" />
                                <span>Plateforme SaaS Opérationnelle</span>
                            </span>
                            <h2 className="text-2xl sm:text-3xl font-black text-white tracking-tight">
                                Console Propriétaire GEDAPP
                            </h2>
                            <p className="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
                                Suivi global en temps réel des organisations clientes, de la facturation récurrente, des quotas de stockage et des circuits de support.
                            </p>
                        </div>

                        {/* Revenue Quick Pill */}
                        <div className="bg-slate-900/80 border border-slate-800 rounded-2xl p-4 sm:p-5 flex items-center gap-6 shrink-0">
                            <div>
                                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
                                    MRR Estimé
                                </span>
                                <span className="text-xl sm:text-2xl font-black text-emerald-400">
                                    {formatNumber(metrics?.revenue?.mrr)} {metrics?.revenue?.currency}
                                </span>
                                <span className="text-[10px] text-slate-500 block mt-0.5">
                                    ARR : {formatNumber(metrics?.revenue?.arr)} {metrics?.revenue?.currency}
                                </span>
                            </div>
                            <div className="w-px h-10 bg-slate-800" />
                            <div>
                                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">
                                    Encaissé ce mois
                                </span>
                                <span className="text-xl sm:text-2xl font-black text-white">
                                    {formatNumber(metrics?.revenue?.revenue_this_month)} {metrics?.revenue?.currency}
                                </span>
                                <span className="text-[10px] text-emerald-400 block mt-0.5">
                                    Facturation manuelle active
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* 5 High-Level KPI Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    {/* Organizations */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4">
                        <div className="flex items-center justify-between text-slate-400 mb-2">
                            <span className="text-xs font-semibold">Organisations</span>
                            <Building2 className="w-4 h-4 text-indigo-400" />
                        </div>
                        <div className="text-2xl font-black text-white">
                            {formatNumber(metrics?.organizations?.total)}
                        </div>
                        <div className="flex items-center gap-2 mt-2 text-[11px]">
                            <span className="text-emerald-400 font-bold">{metrics?.organizations?.active} actives</span>
                            <span className="text-slate-600">•</span>
                            <span className="text-amber-400">{metrics?.subscriptions?.trialing} essais</span>
                        </div>
                    </div>

                    {/* Users */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4">
                        <div className="flex items-center justify-between text-slate-400 mb-2">
                            <span className="text-xs font-semibold">Utilisateurs</span>
                            <Users className="w-4 h-4 text-blue-400" />
                        </div>
                        <div className="text-2xl font-black text-white">
                            {formatNumber(metrics?.users?.total)}
                        </div>
                        <div className="flex items-center gap-2 mt-2 text-[11px]">
                            <span className="text-emerald-400 font-bold">{metrics?.users?.active} actifs</span>
                            <span className="text-slate-600">•</span>
                            <span className="text-slate-400">Total global</span>
                        </div>
                    </div>

                    {/* Documents */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4">
                        <div className="flex items-center justify-between text-slate-400 mb-2">
                            <span className="text-xs font-semibold">Documents GED</span>
                            <FileText className="w-4 h-4 text-emerald-400" />
                        </div>
                        <div className="text-2xl font-black text-white">
                            {formatNumber(metrics?.documents?.total)}
                        </div>
                        <div className="flex items-center gap-2 mt-2 text-[11px]">
                            <span className="text-slate-400 font-medium">{metrics?.documents?.active} actifs</span>
                            <span className="text-slate-600">•</span>
                            <span className="text-slate-500">{metrics?.documents?.archived} archivés</span>
                        </div>
                    </div>

                    {/* Storage */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4">
                        <div className="flex items-center justify-between text-slate-400 mb-2">
                            <span className="text-xs font-semibold">Stockage utilisé</span>
                            <HardDrive className="w-4 h-4 text-amber-400" />
                        </div>
                        <div className="text-2xl font-black text-white truncate">
                            {metrics?.storage?.total_formatted}
                        </div>
                        <div className="mt-2 text-[11px] text-slate-400 truncate">
                            Moy : {metrics?.storage?.average_formatted} / org
                        </div>
                    </div>

                    {/* OCR */}
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4">
                        <div className="flex items-center justify-between text-slate-400 mb-2">
                            <span className="text-xs font-semibold">Consommation OCR</span>
                            <ScanText className="w-4 h-4 text-purple-400" />
                        </div>
                        <div className="text-2xl font-black text-white">
                            {formatNumber(metrics?.ocr?.month_pages)}
                        </div>
                        <div className="mt-2 text-[11px] text-slate-400">
                            Pages ce mois ({formatNumber(metrics?.ocr?.all_time_pages)} total)
                        </div>
                    </div>
                </div>

                {/* Main 2-Column Section */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    {/* Left: Recent Organizations Table (7 cols) */}
                    <div className="lg:col-span-7 bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                        <div className="flex items-center justify-between mb-5">
                            <div>
                                <h3 className="text-sm font-bold text-white uppercase tracking-wider">
                                    Dernières Organisations Clientes
                                </h3>
                                <p className="text-xs text-slate-400 mt-0.5">
                                    Organisations créées récemment sur la plateforme
                                </p>
                            </div>
                            <Link
                                href="/platform/organizations"
                                className="text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1 transition"
                            >
                                <span>Voir toutes</span>
                                <ArrowUpRight className="w-3.5 h-3.5" />
                            </Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-800 text-slate-400 font-semibold">
                                        <th className="pb-3">Organisation</th>
                                        <th className="pb-3">Plan</th>
                                        <th className="pb-3">Statut</th>
                                        <th className="pb-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-800/60">
                                    {recent_organizations.map((org) => (
                                        <tr key={org.id} className="hover:bg-slate-800/30 transition">
                                            <td className="py-3">
                                                <div className="font-bold text-white">{org.name}</div>
                                                <div className="text-[11px] text-slate-400 mt-0.5">
                                                    {org.users_count} utilisateur(s) • {org.created_at}
                                                </div>
                                            </td>
                                            <td className="py-3">
                                                <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                                    {org.plan_name}
                                                </span>
                                            </td>
                                            <td className="py-3">
                                                {org.status === 'suspended' ? (
                                                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                                        Suspendue
                                                    </span>
                                                ) : org.is_trial ? (
                                                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/30">
                                                        Essai ({org.trial_days_remaining}j)
                                                    </span>
                                                ) : (
                                                    <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                        Active
                                                    </span>
                                                )}
                                            </td>
                                            <td className="py-3 text-right">
                                                <Link
                                                    href={`/platform/organizations/${org.id}`}
                                                    className="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition font-semibold"
                                                >
                                                    Gérer
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                    {recent_organizations.length === 0 && (
                                        <tr>
                                            <td colSpan="4" className="py-6 text-center text-slate-500">
                                                Aucune organisation pour le moment.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Right: Support Tickets & Platform Audit Feed (5 cols) */}
                    <div className="lg:col-span-5 space-y-6">
                        {/* Support Tickets card */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h3 className="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                        <LifeBuoy className="w-4 h-4 text-purple-400" />
                                        <span>Tickets Support Récents</span>
                                    </h3>
                                </div>
                                <Link
                                    href="/platform/support"
                                    className="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition"
                                >
                                    Voir tous
                                </Link>
                            </div>

                            <div className="space-y-3">
                                {recent_tickets.map((t) => (
                                    <div key={t.id} className="p-3 rounded-2xl bg-slate-800/40 border border-slate-800/80 text-xs">
                                        <div className="flex items-center justify-between">
                                            <span className="font-bold text-white truncate">{t.subject}</span>
                                            <span className={`px-2 py-0.5 rounded text-[10px] font-bold ${
                                                t.priority === 'urgent' ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-700 text-slate-300'
                                            }`}>
                                                {t.priority}
                                            </span>
                                        </div>
                                        <div className="flex items-center justify-between mt-2 text-[11px] text-slate-400">
                                            <span>{t.organization_name}</span>
                                            <span>{t.created_at}</span>
                                        </div>
                                    </div>
                                ))}
                                {recent_tickets.length === 0 && (
                                    <p className="text-xs text-slate-500 text-center py-4">
                                        Aucun ticket ouvert.
                                    </p>
                                )}
                            </div>
                        </div>

                        {/* Recent Platform Audit Feed */}
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                            <div className="flex items-center justify-between mb-4">
                                <div>
                                    <h3 className="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                        <ShieldAlert className="w-4 h-4 text-amber-400" />
                                        <span>Journal d'audit Plateforme</span>
                                    </h3>
                                </div>
                                <Link
                                    href="/platform/audit"
                                    className="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition"
                                >
                                    Explorer
                                </Link>
                            </div>

                            <div className="space-y-2.5">
                                {recent_audit_logs.map((log) => (
                                    <div key={log.id} className="flex items-start gap-3 text-xs">
                                        <div className="w-2 h-2 rounded-full bg-indigo-500 mt-1.5 shrink-0" />
                                        <div className="min-w-0 flex-1">
                                            <div className="text-slate-200 font-medium truncate">
                                                {log.description || log.action}
                                            </div>
                                            <div className="text-[10px] text-slate-500 flex items-center gap-2 mt-0.5">
                                                <span>{log.user_name}</span>
                                                {log.organization_name && (
                                                    <>
                                                        <span>•</span>
                                                        <span>{log.organization_name}</span>
                                                    </>
                                                )}
                                                <span>•</span>
                                                <span>{log.created_at}</span>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                                {recent_audit_logs.length === 0 && (
                                    <p className="text-xs text-slate-500 text-center py-4">
                                        Aucun événement d'audit récent.
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </PlatformLayout>
    );
}
