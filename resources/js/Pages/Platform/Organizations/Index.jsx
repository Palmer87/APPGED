import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Building2,
    Search,
    Filter,
    ShieldAlert,
    CheckCircle2,
    Clock,
    AlertTriangle,
    Eye,
    ChevronLeft,
    ChevronRight,
    X
} from 'lucide-react';

export default function OrganizationsIndex({
    organizations,
    plans = [],
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [planId, setPlanId] = useState(filters.plan_id || '');

    // Suspension modal state
    const [suspendModalOrg, setSuspendModalOrg] = useState(null);
    const [suspendReason, setSuspendReason] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Reactivation modal state
    const [reactivateModalOrg, setReactivateModalOrg] = useState(null);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/organizations', {
            search: search.trim() || undefined,
            status: status || undefined,
            plan_id: planId || undefined,
        }, { preserveState: true });
    };

    const handleFilterChange = (newStatus, newPlanId) => {
        setStatus(newStatus);
        setPlanId(newPlanId);
        router.get('/platform/organizations', {
            search: search.trim() || undefined,
            status: newStatus || undefined,
            plan_id: newPlanId || undefined,
        }, { preserveState: true });
    };

    const handleConfirmSuspend = (e) => {
        e.preventDefault();
        if (!suspendModalOrg || !suspendReason.trim()) return;

        setIsSubmitting(true);
        router.post(`/platform/organizations/${suspendModalOrg.id}/suspend`, {
            reason: suspendReason,
        }, {
            onFinish: () => {
                setIsSubmitting(false);
                setSuspendModalOrg(null);
                setSuspendReason('');
            }
        });
    };

    const handleConfirmReactivate = (e) => {
        e.preventDefault();
        if (!reactivateModalOrg) return;

        setIsSubmitting(true);
        router.post(`/platform/organizations/${reactivateModalOrg.id}/reactivate`, {}, {
            onFinish: () => {
                setIsSubmitting(false);
                setReactivateModalOrg(null);
            }
        });
    };

    return (
        <PlatformLayout title="Gestion des Organisations Clientes">
            <Head title="Organisations Clientes — Console Propriétaire" />

            <div className="space-y-6">
                {/* Header & Filter Controls */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                    <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-12 gap-4">
                        {/* Search Input */}
                        <div className="md:col-span-5 relative">
                            <Search className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher par nom, email ou slug..."
                                className="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        {/* Status Filter */}
                        <div className="md:col-span-3">
                            <select
                                value={status}
                                onChange={(e) => handleFilterChange(e.target.value, planId)}
                                className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="active">Actives</option>
                                <option value="suspended">Suspendues</option>
                            </select>
                        </div>

                        {/* Plan Filter */}
                        <div className="md:col-span-3">
                            <select
                                value={planId}
                                onChange={(e) => handleFilterChange(status, e.target.value)}
                                className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Tous les plans</option>
                                {plans.map((p) => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </select>
                        </div>

                        {/* Submit Button */}
                        <div className="md:col-span-1">
                            <button
                                type="submit"
                                className="w-full py-2 px-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs transition"
                            >
                                Filtrer
                            </button>
                        </div>
                    </form>
                </div>

                {/* Organizations Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-900 border-b border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3.5 px-4">Organisation</th>
                                    <th className="py-3.5 px-4">Admin Principal</th>
                                    <th className="py-3.5 px-4">Plan & Cycle</th>
                                    <th className="py-3.5 px-4">Statut</th>
                                    <th className="py-3.5 px-4">Utilisateurs</th>
                                    <th className="py-3.5 px-4">Stockage</th>
                                    <th className="py-3.5 px-4">Documents</th>
                                    <th className="py-3.5 px-4">Période d'essai</th>
                                    <th className="py-3.5 px-4">Création</th>
                                    <th className="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 text-slate-300">
                                {organizations.data.map((org) => (
                                    <tr key={org.id} className="hover:bg-slate-800/30 transition">
                                        <td className="py-3.5 px-4">
                                            <div className="font-bold text-white text-xs">{org.name}</div>
                                            <div className="text-[10px] text-slate-500">{org.slug}</div>
                                        </td>
                                        <td className="py-3.5 px-4">
                                            <div className="font-medium text-slate-200">{org.admin_name}</div>
                                            <div className="text-[10px] text-slate-500">{org.admin_email}</div>
                                        </td>
                                        <td className="py-3.5 px-4">
                                            <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                                {org.plan_name} ({org.billing_cycle === 'annual' ? 'Annuel' : 'Mensuel'})
                                            </span>
                                        </td>
                                        <td className="py-3.5 px-4">
                                            {org.status === 'suspended' ? (
                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                                    <AlertTriangle className="w-3 h-3" />
                                                    <span>Suspendue</span>
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                                    <CheckCircle2 className="w-3 h-3" />
                                                    <span>Active</span>
                                                </span>
                                            )}
                                        </td>
                                        <td className="py-3.5 px-4 font-semibold text-white">
                                            {org.users_count}
                                        </td>
                                        <td className="py-3.5 px-4">
                                            {org.storage_formatted}
                                        </td>
                                        <td className="py-3.5 px-4 font-semibold text-white">
                                            {org.documents_count}
                                        </td>
                                        <td className="py-3.5 px-4">
                                            {org.is_trial ? (
                                                <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30">
                                                    {org.trial_days_remaining} jours
                                                </span>
                                            ) : (
                                                <span className="text-[10px] text-slate-500">Payant</span>
                                            )}
                                        </td>
                                        <td className="py-3.5 px-4 text-slate-400 text-[11px]">
                                            {org.created_at}
                                        </td>
                                        <td className="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                            <Link
                                                href={`/platform/organizations/${org.id}`}
                                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 transition text-xs font-semibold"
                                            >
                                                <Eye className="w-3 h-3" />
                                                <span>Détails</span>
                                            </Link>
                                            {org.status === 'suspended' ? (
                                                <button
                                                    type="button"
                                                    onClick={() => setReactivateModalOrg(org)}
                                                    className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 transition text-xs font-semibold cursor-pointer"
                                                >
                                                    Réactiver
                                                </button>
                                            ) : (
                                                <button
                                                    type="button"
                                                    onClick={() => setSuspendModalOrg(org)}
                                                    className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-600/20 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 transition text-xs font-semibold cursor-pointer"
                                                >
                                                    Suspendre
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {organizations.data.length === 0 && (
                                    <tr>
                                        <td colSpan="10" className="py-12 text-center text-slate-500">
                                            Aucune organisation trouvée avec ces critères.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {organizations.links && organizations.links.length > 3 && (
                        <div className="p-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
                            <span>Page {organizations.current_page} sur {organizations.last_page}</span>
                            <div className="flex items-center gap-1">
                                {organizations.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1 rounded-lg border ${
                                            link.active
                                                ? 'bg-indigo-600 text-white border-indigo-500 font-bold'
                                                : link.url
                                                ? 'bg-slate-900 border-slate-800 text-slate-400 hover:text-white hover:bg-slate-800'
                                                : 'opacity-40 cursor-not-allowed border-transparent'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Suspend Confirmation Modal */}
            {suspendModalOrg && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2 text-rose-400 font-bold text-base">
                                <AlertTriangle className="w-5 h-5" />
                                <span>Suspendre l'organisation</span>
                            </div>
                            <button
                                type="button"
                                onClick={() => setSuspendModalOrg(null)}
                                className="p-1 rounded-lg text-slate-400 hover:text-white"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                        <p className="text-xs text-slate-300 leading-relaxed">
                            Vous êtes sur le point de suspendre <strong className="text-white">{suspendModalOrg.name}</strong>.
                            Les utilisateurs clients ne pourront plus accéder à leurs documents ni modifier leurs données.
                            <br /><br />
                            <span className="text-emerald-400">Toutes les données, fichiers R2 et métadonnées restent rigoureusement intacts.</span>
                        </p>
                        <form onSubmit={handleConfirmSuspend} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">
                                    Motif de suspension (obligatoire) :
                                </label>
                                <textarea
                                    value={suspendReason}
                                    onChange={(e) => setSuspendReason(e.target.value)}
                                    placeholder="Ex: Facture impayée depuis plus de 30 jours..."
                                    required
                                    rows="3"
                                    className="w-full p-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-rose-500"
                                />
                            </div>
                            <div className="flex items-center justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setSuspendModalOrg(null)}
                                    className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSubmitting || !suspendReason.trim()}
                                    className="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-500 shadow-md shadow-rose-600/30 disabled:opacity-50"
                                >
                                    {isSubmitting ? 'Suspension...' : 'Confirmer la suspension'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Reactivate Confirmation Modal */}
            {reactivateModalOrg && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2 text-emerald-400 font-bold text-base">
                                <CheckCircle2 className="w-5 h-5" />
                                <span>Réactiver l'organisation</span>
                            </div>
                            <button
                                type="button"
                                onClick={() => setReactivateModalOrg(null)}
                                className="p-1 rounded-lg text-slate-400 hover:text-white"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                        <p className="text-xs text-slate-300 leading-relaxed">
                            Confirmez-vous la réactivation immédiate de l'organisation <strong className="text-white">{reactivateModalOrg.name}</strong> ?
                            Les utilisateurs pourront à nouveau se connecter et exploiter l'ensemble de leurs documents.
                        </p>
                        <form onSubmit={handleConfirmReactivate} className="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                onClick={() => setReactivateModalOrg(null)}
                                className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white hover:bg-slate-800"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/30 disabled:opacity-50"
                            >
                                {isSubmitting ? 'Réactivation...' : 'Confirmer la réactivation'}
                            </button>
                        </form>
                    </div>
                </div>
            )}
        </PlatformLayout>
    );
}
