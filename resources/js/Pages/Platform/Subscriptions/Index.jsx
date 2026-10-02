import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    CreditCard,
    Search,
    Filter,
    Eye,
    Clock,
    CheckCircle2,
    AlertTriangle,
    XCircle
} from 'lucide-react';

export default function SubscriptionsIndex({ subscriptions, plans = [], filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [planId, setPlanId] = useState(filters.plan_id || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/subscriptions', {
            search: search.trim() || undefined,
            status: status || undefined,
            plan_id: planId || undefined,
        }, { preserveState: true });
    };

    return (
        <PlatformLayout title="Gestion des Abonnements SaaS">
            <Head title="Abonnements — Console Propriétaire" />

            <div className="space-y-6">
                {/* Search & Filters */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                    <form onSubmit={handleSearch} className="grid grid-cols-1 md:grid-cols-12 gap-4">
                        <div className="md:col-span-5 relative">
                            <Search className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher par nom d'organisation..."
                                className="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div className="md:col-span-3">
                            <select
                                value={status}
                                onChange={(e) => {
                                    setStatus(e.target.value);
                                    router.get('/platform/subscriptions', { search, status: e.target.value || undefined, plan_id: planId || undefined }, { preserveState: true });
                                }}
                                className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="trialing">Essai (Trial)</option>
                                <option value="active">Actif</option>
                                <option value="past_due">Impayé</option>
                                <option value="cancelled">Résilié</option>
                                <option value="expired">Expiré</option>
                                <option value="suspended">Suspendu</option>
                            </select>
                        </div>

                        <div className="md:col-span-3">
                            <select
                                value={planId}
                                onChange={(e) => {
                                    setPlanId(e.target.value);
                                    router.get('/platform/subscriptions', { search, status, plan_id: e.target.value || undefined }, { preserveState: true });
                                }}
                                className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">Tous les plans</option>
                                {plans.map((p) => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </select>
                        </div>

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

                {/* Subscriptions Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-900 border-b border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3.5 px-4">Organisation</th>
                                    <th className="py-3.5 px-4">Plan</th>
                                    <th className="py-3.5 px-4">Cycle</th>
                                    <th className="py-3.5 px-4">Statut</th>
                                    <th className="py-3.5 px-4">Début</th>
                                    <th className="py-3.5 px-4">Échéance</th>
                                    <th className="py-3.5 px-4">Période d'essai</th>
                                    <th className="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 text-slate-300">
                                {subscriptions.data.map((sub) => (
                                    <tr key={sub.id} className="hover:bg-slate-800/30 transition">
                                        <td className="py-3.5 px-4 font-bold text-white">
                                            {sub.organization_name}
                                        </td>
                                        <td className="py-3.5 px-4">
                                            <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                                {sub.plan_name}
                                            </span>
                                        </td>
                                        <td className="py-3.5 px-4 capitalize">
                                            {sub.billing_cycle === 'annual' ? 'Annuel' : 'Mensuel'}
                                        </td>
                                        <td className="py-3.5 px-4">
                                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                                sub.status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30'
                                                : sub.status === 'trialing' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30'
                                                : sub.status === 'suspended' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30'
                                                : 'bg-slate-800 text-slate-400'
                                            }`}>
                                                {sub.status}
                                            </span>
                                        </td>
                                        <td className="py-3.5 px-4 text-slate-400">{sub.starts_at || 'N/A'}</td>
                                        <td className="py-3.5 px-4 font-medium text-white">{sub.current_period_ends_at || 'N/A'}</td>
                                        <td className="py-3.5 px-4">
                                            {sub.is_trial ? (
                                                <span className="text-amber-400 font-semibold">{sub.trial_days_remaining} jours</span>
                                            ) : (
                                                <span className="text-slate-500">Non</span>
                                            )}
                                        </td>
                                        <td className="py-3.5 px-4 text-right">
                                            <Link
                                                href={`/platform/subscriptions/${sub.id}`}
                                                className="px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white transition font-semibold"
                                            >
                                                Gérer
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                                {subscriptions.data.length === 0 && (
                                    <tr>
                                        <td colSpan="8" className="py-10 text-center text-slate-500">
                                            Aucun abonnement trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </PlatformLayout>
    );
}
