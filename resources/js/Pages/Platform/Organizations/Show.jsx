import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Building2,
    Users,
    HardDrive,
    ScanText,
    CreditCard,
    Receipt,
    ShieldAlert,
    AlertTriangle,
    CheckCircle2,
    Clock,
    DollarSign,
    ArrowLeft,
    Check,
    X,
    LifeBuoy
} from 'lucide-react';

export default function OrganizationShow({
    organization,
    users,
    quotas,
    stats,
    plans = [],
}) {
    const sub = organization.currentSubscription;
    const plan = sub?.plan;

    // Subscription edit modal
    const [changePlanOpen, setChangePlanOpen] = useState(false);
    const [selectedPlanId, setSelectedPlanId] = useState(plan?.id || '');
    const [selectedCycle, setSelectedCycle] = useState(sub?.billing_cycle || 'monthly');
    const [submittingPlan, setSubmittingPlan] = useState(false);

    // Suspend modal
    const [suspendOpen, setSuspendOpen] = useState(false);
    const [suspendReason, setSuspendReason] = useState('');
    const [submittingSuspend, setSubmittingSuspend] = useState(false);

    const handleUpdateSubscription = (e) => {
        e.preventDefault();
        setSubmittingPlan(true);
        router.post(`/platform/organizations/${organization.id}/subscription`, {
            plan_id: selectedPlanId,
            billing_cycle: selectedCycle,
        }, {
            onFinish: () => {
                setSubmittingPlan(false);
                setChangePlanOpen(false);
            }
        });
    };

    const handleSuspend = (e) => {
        e.preventDefault();
        if (!suspendReason.trim()) return;
        setSubmittingSuspend(true);
        router.post(`/platform/organizations/${organization.id}/suspend`, {
            reason: suspendReason,
        }, {
            onFinish: () => {
                setSubmittingSuspend(false);
                setSuspendOpen(false);
                setSuspendReason('');
            }
        });
    };

    const handleReactivate = () => {
        router.post(`/platform/organizations/${organization.id}/reactivate`);
    };

    const formatBytes = (bytes) => {
        if (!bytes) return '0 o';
        if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' Go';
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' Mo';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' Ko';
        return bytes + ' o';
    };

    return (
        <PlatformLayout title={`Organisation : ${organization.name}`}>
            <Head title={`Fiche ${organization.name} — Console Propriétaire`} />

            <div className="space-y-6">
                {/* Back button & Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <Link
                        href="/platform/organizations"
                        className="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        <span>Retour à la liste des organisations</span>
                    </Link>

                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setChangePlanOpen(true)}
                            className="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 transition cursor-pointer"
                        >
                            Changer d'abonnement
                        </button>
                        {organization.status === 'suspended' ? (
                            <button
                                type="button"
                                onClick={handleReactivate}
                                className="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/30 transition cursor-pointer"
                            >
                                Réactiver l'organisation
                            </button>
                        ) : (
                            <button
                                type="button"
                                onClick={() => setSuspendOpen(true)}
                                className="px-3.5 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 shadow-md shadow-rose-600/30 transition cursor-pointer"
                            >
                                Suspendre l'organisation
                            </button>
                        )}
                    </div>
                </div>

                {/* Organization Identity & Subscription Overview */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Left: Organization Info (7 cols) */}
                    <div className="lg:col-span-7 bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 space-y-6">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center gap-4">
                                <div className="w-14 h-14 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center font-black text-xl">
                                    {organization.name[0].toUpperCase()}
                                </div>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h2 className="text-xl font-black text-white">{organization.name}</h2>
                                        {organization.status === 'suspended' ? (
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                                Suspendue
                                            </span>
                                        ) : (
                                            <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                Active
                                            </span>
                                        )}
                                    </div>
                                    <p className="text-xs text-slate-400 mt-1">
                                        Identifiant / Slug : <span className="text-slate-200 font-mono">{organization.slug}</span> • Créée le {new Date(organization.created_at).toLocaleDateString('fr-FR')}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-800">
                            <div>
                                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Email Contact</span>
                                <span className="text-xs font-semibold text-white mt-1 block truncate">{organization.email || 'Non renseigné'}</span>
                            </div>
                            <div>
                                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Téléphone</span>
                                <span className="text-xs font-semibold text-white mt-1 block">{organization.phone || 'Non renseigné'}</span>
                            </div>
                            <div>
                                <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Localisation</span>
                                <span className="text-xs font-semibold text-white mt-1 block">
                                    {[organization.city, organization.country].filter(Boolean).join(', ') || 'Non renseigné'}
                                </span>
                            </div>
                        </div>

                        {/* Internal Count Stats */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2">
                            <div className="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                                <span className="text-[10px] text-slate-400 uppercase font-bold block">Utilisateurs</span>
                                <span className="text-base font-black text-white">{stats.users_count}</span>
                            </div>
                            <div className="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                                <span className="text-[10px] text-slate-400 uppercase font-bold block">Directions</span>
                                <span className="text-base font-black text-white">{stats.directions_count}</span>
                            </div>
                            <div className="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                                <span className="text-[10px] text-slate-400 uppercase font-bold block">Services</span>
                                <span className="text-base font-black text-white">{stats.services_count}</span>
                            </div>
                            <div className="p-3 rounded-2xl bg-slate-950 border border-slate-800 text-center">
                                <span className="text-[10px] text-slate-400 uppercase font-bold block">Documents</span>
                                <span className="text-base font-black text-white">{stats.documents_count}</span>
                            </div>
                        </div>
                    </div>

                    {/* Right: Active Subscription Details (5 cols) */}
                    <div className="lg:col-span-5 bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2 text-sm font-bold text-white uppercase tracking-wider">
                                <CreditCard className="w-4 h-4 text-indigo-400" />
                                <span>Abonnement Actuel</span>
                            </div>
                            <span className="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                {sub?.status || 'trialing'}
                            </span>
                        </div>

                        <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-black text-white">{plan?.name || 'Essentiel'}</span>
                                <span className="text-xs font-bold text-emerald-400">
                                    {sub?.billing_cycle === 'annual'
                                        ? `${plan?.annual_price ? plan.annual_price.toLocaleString() : 0} XOF / an`
                                        : `${plan?.monthly_price ? plan.monthly_price.toLocaleString() : 0} XOF / mois`}
                                </span>
                            </div>
                            <div className="text-xs text-slate-400 space-y-1">
                                <div className="flex justify-between">
                                    <span>Période début :</span>
                                    <span className="text-white font-medium">{sub?.current_period_starts_at ? new Date(sub.current_period_starts_at).toLocaleDateString('fr-FR') : 'N/A'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Période fin :</span>
                                    <span className="text-white font-medium">{sub?.current_period_ends_at ? new Date(sub.current_period_ends_at).toLocaleDateString('fr-FR') : 'N/A'}</span>
                                </div>
                                {sub?.status === 'trialing' && (
                                    <div className="flex justify-between text-amber-400 font-bold pt-1 border-t border-slate-800">
                                        <span>Fin de l'essai :</span>
                                        <span>{sub?.trial_ends_at ? new Date(sub.trial_ends_at).toLocaleDateString('fr-FR') : '14 jours'}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        <button
                            type="button"
                            onClick={() => setChangePlanOpen(true)}
                            className="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 hover:text-white transition cursor-pointer"
                        >
                            Modifier le plan ou le cycle
                        </button>
                    </div>
                </div>

                {/* Quotas & Resource Usage Bars */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                    <h3 className="text-sm font-bold text-white uppercase tracking-wider mb-4">
                        Consommation des Quotas de l'Organisation
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Users Quota */}
                        <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-semibold text-slate-300">Utilisateurs</span>
                                <span className="font-bold text-white">
                                    {quotas.metrics.users.used} / {quotas.metrics.users.is_unlimited ? '∞' : quotas.metrics.users.limit}
                                </span>
                            </div>
                            <div className="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                                <div
                                    className={`h-full rounded-full ${quotas.metrics.users.percentage >= 90 ? 'bg-rose-500' : 'bg-indigo-500'}`}
                                    style={{ width: `${Math.min(100, quotas.metrics.users.percentage)}%` }}
                                />
                            </div>
                            <span className="text-[10px] text-slate-500 block">{quotas.metrics.users.percentage}% du quota autorisé</span>
                        </div>

                        {/* Storage Quota */}
                        <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-semibold text-slate-300">Stockage</span>
                                <span className="font-bold text-white truncate">
                                    {quotas.metrics.storage.used_formatted} / {quotas.metrics.storage.limit_formatted}
                                </span>
                            </div>
                            <div className="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                                <div
                                    className={`h-full rounded-full ${quotas.metrics.storage.percentage >= 90 ? 'bg-rose-500' : 'bg-amber-500'}`}
                                    style={{ width: `${Math.min(100, quotas.metrics.storage.percentage)}%` }}
                                />
                            </div>
                            <span className="text-[10px] text-slate-500 block">{quotas.metrics.storage.percentage}% utilisé</span>
                        </div>

                        {/* OCR Quota */}
                        <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-semibold text-slate-300">Pages OCR (Mois)</span>
                                <span className="font-bold text-white">
                                    {quotas.metrics.ocr.used} / {quotas.metrics.ocr.is_unlimited ? '∞' : quotas.metrics.ocr.limit}
                                </span>
                            </div>
                            <div className="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                                <div
                                    className={`h-full rounded-full ${quotas.metrics.ocr.percentage >= 90 ? 'bg-rose-500' : 'bg-purple-500'}`}
                                    style={{ width: `${Math.min(100, quotas.metrics.ocr.percentage)}%` }}
                                />
                            </div>
                            <span className="text-[10px] text-slate-500 block">{quotas.metrics.ocr.percentage}% consommé</span>
                        </div>
                    </div>
                </div>

                {/* Client Users Listing */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                    <h3 className="text-sm font-bold text-white uppercase tracking-wider mb-4">
                        Utilisateurs Rattachés ({users.total})
                    </h3>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="border-b border-slate-800 text-slate-400 font-semibold">
                                <tr>
                                    <th className="pb-3">Utilisateur</th>
                                    <th className="pb-3">Rôle Métier</th>
                                    <th className="pb-3">Service & Direction</th>
                                    <th className="pb-3">Statut</th>
                                    <th className="pb-3">Dernière Connexion</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 text-slate-300">
                                {users.data.map((u) => (
                                    <tr key={u.id} className="hover:bg-slate-800/30">
                                        <td className="py-3">
                                            <div className="font-bold text-white">{u.name}</div>
                                            <div className="text-[10px] text-slate-500">{u.email}</div>
                                        </td>
                                        <td className="py-3">
                                            {u.roles?.map(r => (
                                                <span key={r.id} className="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-300 mr-1">
                                                    {r.name}
                                                </span>
                                            ))}
                                        </td>
                                        <td className="py-3 text-[11px] text-slate-400">
                                            {u.primary_service ? `${u.primary_service.name} (${u.primary_service.direction?.name || 'N/A'})` : 'Général'}
                                        </td>
                                        <td className="py-3">
                                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                                                u.status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400'
                                            }`}>
                                                {u.status}
                                            </span>
                                        </td>
                                        <td className="py-3 text-slate-400 text-[11px]">
                                            {u.last_login_at ? new Date(u.last_login_at).toLocaleDateString('fr-FR') : 'Jamais'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Audit & Platform History */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                    <h3 className="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <ShieldAlert className="w-4 h-4 text-amber-400" />
                        <span>Historique des Événements Plateforme</span>
                    </h3>
                    <div className="space-y-3">
                        {organization.platform_audit_logs?.map((log) => (
                            <div key={log.id} className="p-3 rounded-2xl bg-slate-950 border border-slate-800 flex items-start justify-between text-xs">
                                <div>
                                    <p className="font-bold text-white">{log.description || log.action}</p>
                                    <p className="text-[11px] text-slate-400 mt-0.5">
                                        Par : {log.platform_user?.name || 'Système'}
                                    </p>
                                </div>
                                <span className="text-[11px] text-slate-500">
                                    {new Date(log.created_at).toLocaleString('fr-FR')}
                                </span>
                            </div>
                        ))}
                        {(!organization.platform_audit_logs || organization.platform_audit_logs.length === 0) && (
                            <p className="text-xs text-slate-500 py-3">Aucun événement d'audit spécifique pour le moment.</p>
                        )}
                    </div>
                </div>
            </div>

            {/* Change Subscription Modal */}
            {changePlanOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-bold text-white uppercase tracking-wider">
                                Modifier l'abonnement
                            </h3>
                            <button
                                type="button"
                                onClick={() => setChangePlanOpen(false)}
                                className="p-1 rounded-lg text-slate-400 hover:text-white"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                        <form onSubmit={handleUpdateSubscription} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Nouveau Plan :
                                </label>
                                <select
                                    value={selectedPlanId}
                                    onChange={(e) => setSelectedPlanId(e.target.value)}
                                    required
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    {plans.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name} — {p.monthly_price?.toLocaleString()} XOF/m
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Cycle de facturation :
                                </label>
                                <select
                                    value={selectedCycle}
                                    onChange={(e) => setSelectedCycle(e.target.value)}
                                    required
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="monthly">Mensuel</option>
                                    <option value="annual">Annuel</option>
                                </select>
                            </div>
                            <div className="flex items-center justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setChangePlanOpen(false)}
                                    className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    disabled={submittingPlan}
                                    className="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/30 disabled:opacity-50"
                                >
                                    {submittingPlan ? 'Mise à jour...' : 'Appliquer le changement'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Suspend Confirmation Modal */}
            {suspendOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center gap-2 text-rose-400 font-bold text-base">
                            <AlertTriangle className="w-5 h-5" />
                            <span>Suspendre l'organisation</span>
                        </div>
                        <p className="text-xs text-slate-300">
                            Précisez la raison de la suspension. L'action sera enregistrée dans l'audit et l'accès des utilisateurs sera bloqué.
                        </p>
                        <form onSubmit={handleSuspend} className="space-y-4">
                            <textarea
                                value={suspendReason}
                                onChange={(e) => setSuspendReason(e.target.value)}
                                placeholder="Motif obligatoire..."
                                required
                                rows="3"
                                className="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-rose-500"
                            />
                            <div className="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setSuspendOpen(false)}
                                    className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    disabled={submittingSuspend || !suspendReason.trim()}
                                    className="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 shadow-md shadow-rose-600/30 disabled:opacity-50"
                                >
                                    {submittingSuspend ? 'Suspension...' : 'Confirmer la suspension'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </PlatformLayout>
    );
}
