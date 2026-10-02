import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Clock,
    Search,
    AlertTriangle,
    CheckCircle2,
    Calendar,
    ArrowRight,
    X
} from 'lucide-react';

export default function TrialsIndex({ trials, plans = [], filters = {} }) {
    const [search, setSearch] = useState(filters.search || '');
    const [filter, setFilter] = useState(filters.filter || 'all');

    // Extend modal
    const [extendOrg, setExtendOrg] = useState(null);
    const [extendDays, setExtendDays] = useState(14);
    const [extending, setExtending] = useState(false);

    // Convert modal
    const [convertOrg, setConvertOrg] = useState(null);
    const [selectedPlanId, setSelectedPlanId] = useState(plans[0]?.id || '');
    const [selectedCycle, setSelectedCycle] = useState('monthly');
    const [converting, setConverting] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/trials', {
            search: search.trim() || undefined,
            filter: filter !== 'all' ? filter : undefined,
        }, { preserveState: true });
    };

    const handleConfirmExtend = (e) => {
        e.preventDefault();
        if (!extendOrg) return;
        setExtending(true);
        router.post(`/platform/trials/${extendOrg.organization_id}/extend`, {
            days: extendDays,
        }, {
            onFinish: () => {
                setExtending(false);
                setExtendOrg(null);
            }
        });
    };

    const handleEndTrial = (orgId) => {
        if (confirm("Voulez-vous vraiment terminer la période d'essai immédiatement ?")) {
            router.post(`/platform/trials/${orgId}/end`);
        }
    };

    const handleConfirmConvert = (e) => {
        e.preventDefault();
        if (!convertOrg) return;
        setConverting(true);
        router.post(`/platform/trials/${convertOrg.organization_id}/convert`, {
            plan_id: selectedPlanId,
            billing_cycle: selectedCycle,
        }, {
            onFinish: () => {
                setConverting(false);
                setConvertOrg(null);
            }
        });
    };

    return (
        <PlatformLayout title="Suivi des Périodes d'Essai (Trials 14 Jours)">
            <Head title="Périodes d'Essai — Console Propriétaire" />

            <div className="space-y-6">
                {/* Search & Tabs */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex-1 max-w-md relative">
                        <Search className="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher une organisation en essai..."
                            className="w-full pl-10 pr-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                        />
                    </form>

                    <div className="flex items-center gap-2">
                        {['all', 'active', 'expired'].map((f) => (
                            <button
                                key={f}
                                type="button"
                                onClick={() => {
                                    setFilter(f);
                                    router.get('/platform/trials', { search, filter: f !== 'all' ? f : undefined }, { preserveState: true });
                                }}
                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition capitalize cursor-pointer ${
                                    filter === f
                                        ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                                        : 'bg-slate-950 text-slate-400 hover:text-white border border-slate-800'
                                }`}
                            >
                                {f === 'all' ? 'Tous les essais' : f === 'active' ? 'Essais actifs' : 'Essais expirés'}
                            </button>
                        ))}
                    </div>
                </div>

                {/* Trials Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-900 border-b border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3.5 px-4">Organisation</th>
                                    <th className="py-3.5 px-4">Admin Principal</th>
                                    <th className="py-3.5 px-4">Plan Testé</th>
                                    <th className="py-3.5 px-4">Début Essai</th>
                                    <th className="py-3.5 px-4">Fin Prévue</th>
                                    <th className="py-3.5 px-4">État & Décompte</th>
                                    <th className="py-3.5 px-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 text-slate-300">
                                {trials.data.map((t) => (
                                    <tr key={t.organization_id} className="hover:bg-slate-800/30 transition">
                                        <td className="py-3.5 px-4 font-bold text-white">
                                            {t.organization_name}
                                        </td>
                                        <td className="py-3.5 px-4 text-slate-300">
                                            {t.admin_name}
                                        </td>
                                        <td className="py-3.5 px-4">
                                            <span className="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                                {t.plan_name}
                                            </span>
                                        </td>
                                        <td className="py-3.5 px-4 text-slate-400">{t.starts_at || 'N/A'}</td>
                                        <td className="py-3.5 px-4 text-slate-400">{t.ends_at || 'N/A'}</td>
                                        <td className="py-3.5 px-4">
                                            {t.is_expired ? (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30">
                                                    <AlertTriangle className="w-3 h-3" />
                                                    <span>Trial expiré</span>
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-300 border border-amber-500/30">
                                                    <Clock className="w-3 h-3" />
                                                    <span>{t.days_remaining} jours restants</span>
                                                </span>
                                            )}
                                        </td>
                                        <td className="py-3.5 px-4 text-right space-x-1.5 whitespace-nowrap">
                                            <button
                                                type="button"
                                                onClick={() => setExtendOrg(t)}
                                                className="px-2.5 py-1 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-semibold transition cursor-pointer"
                                            >
                                                Prolonger
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setConvertOrg(t)}
                                                className="px-2.5 py-1 rounded-lg bg-emerald-600/20 hover:bg-emerald-600 text-emerald-300 hover:text-white border border-emerald-500/30 text-xs font-semibold transition cursor-pointer"
                                            >
                                                Convertir
                                            </button>
                                            {!t.is_expired && (
                                                <button
                                                    type="button"
                                                    onClick={() => handleEndTrial(t.organization_id)}
                                                    className="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-rose-600 text-slate-400 hover:text-white text-xs font-semibold transition cursor-pointer"
                                                >
                                                    Terminer
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                                {trials.data.length === 0 && (
                                    <tr>
                                        <td colSpan="7" className="py-10 text-center text-slate-500">
                                            Aucune organisation en période d'essai trouvée.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Extend Trial Modal */}
            {extendOrg && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-xs font-bold text-white uppercase tracking-wider">
                                Prolonger l'Essai
                            </h3>
                            <button
                                type="button"
                                onClick={() => setExtendOrg(null)}
                                className="text-slate-400 hover:text-white"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                        <p className="text-xs text-slate-300">
                            Prolonger l'essai pour <strong className="text-white">{extendOrg.organization_name}</strong> :
                        </p>
                        <form onSubmit={handleConfirmExtend} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-400 mb-1">Nombre de jours additionnels :</label>
                                <input
                                    type="number"
                                    min="1"
                                    max="90"
                                    value={extendDays}
                                    onChange={(e) => setExtendDays(e.target.value)}
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
                                />
                            </div>
                            <div className="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setExtendOrg(null)}
                                    className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    disabled={extending}
                                    className="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50"
                                >
                                    {extending ? 'Prolongation...' : 'Valider la prolongation'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Convert to Subscription Modal */}
            {convertOrg && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-xs font-bold text-white uppercase tracking-wider">
                                Convertir en Abonnement Payant
                            </h3>
                            <button
                                type="button"
                                onClick={() => setConvertOrg(null)}
                                className="text-slate-400 hover:text-white"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                        <p className="text-xs text-slate-300">
                            Activer l'abonnement pour <strong className="text-white">{convertOrg.organization_name}</strong> :
                        </p>
                        <form onSubmit={handleConfirmConvert} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-400 mb-1">Plan cible :</label>
                                <select
                                    value={selectedPlanId}
                                    onChange={(e) => setSelectedPlanId(e.target.value)}
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
                                >
                                    {plans.map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-400 mb-1">Cycle :</label>
                                <select
                                    value={selectedCycle}
                                    onChange={(e) => setSelectedCycle(e.target.value)}
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
                                >
                                    <option value="monthly">Mensuel</option>
                                    <option value="annual">Annuel</option>
                                </select>
                            </div>
                            <div className="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={() => setConvertOrg(null)}
                                    className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    disabled={converting}
                                    className="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50"
                                >
                                    {converting ? 'Conversion...' : 'Activer l\'abonnement'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </PlatformLayout>
    );
}
