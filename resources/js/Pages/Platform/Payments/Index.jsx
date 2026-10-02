import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    DollarSign,
    Search,
    PlusCircle,
    CheckCircle2,
    Clock,
    X,
    Receipt
} from 'lucide-react';

export default function PaymentsIndex({
    payments,
    organizations = [],
    totalPaidAmount = 0,
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [modalOpen, setModalOpen] = useState(false);

    const { data, setData, post, processing, reset, errors } = useForm({
        organization_id: organizations[0]?.id || '',
        amount: '',
        currency: 'XOF',
        provider: 'manual',
        provider_reference: '',
        paid_at: new Date().toISOString().slice(0, 10),
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/payments', { search: search.trim() || undefined }, { preserveState: true });
    };

    const handleRecordPayment = (e) => {
        e.preventDefault();
        post('/platform/payments', {
            onSuccess: () => {
                reset();
                setModalOpen(false);
            }
        });
    };

    return (
        <PlatformLayout title="Gestion des Paiements">
            <Head title="Paiements — Console Propriétaire" />

            <div className="space-y-6">
                {/* Header Stats & Search */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Encaissé (Paiements Réussis)</span>
                        <div className="text-2xl sm:text-3xl font-black text-emerald-400 mt-1">
                            {totalPaidAmount.toLocaleString()} XOF
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <form onSubmit={handleSearch} className="relative">
                            <Search className="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Référence ou organisation..."
                                className="pl-9 pr-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </form>
                        <button
                            type="button"
                            onClick={() => setModalOpen(true)}
                            className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow-md shadow-indigo-600/30 transition cursor-pointer"
                        >
                            <PlusCircle className="w-4 h-4" />
                            <span>Enregistrer un paiement</span>
                        </button>
                    </div>
                </div>

                {/* Payments Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-slate-900 border-b border-slate-800 text-slate-400 font-semibold uppercase tracking-wider text-[10px]">
                                <tr>
                                    <th className="py-3.5 px-4">Référence</th>
                                    <th className="py-3.5 px-4">Organisation</th>
                                    <th className="py-3.5 px-4">Plan</th>
                                    <th className="py-3.5 px-4">Montant</th>
                                    <th className="py-3.5 px-4">Mode / Provider</th>
                                    <th className="py-3.5 px-4">Statut</th>
                                    <th className="py-3.5 px-4">Date de paiement</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 text-slate-300">
                                {payments.data.map((p) => (
                                    <tr key={p.id} className="hover:bg-slate-800/30 transition">
                                        <td className="py-3.5 px-4 font-mono font-bold text-white">
                                            {p.provider_reference || `PAY-${p.id}`}
                                        </td>
                                        <td className="py-3.5 px-4">
                                            <Link href={`/platform/organizations/${p.organization_id}`} className="font-semibold text-white hover:text-indigo-400">
                                                {p.organization_name}
                                            </Link>
                                        </td>
                                        <td className="py-3.5 px-4 text-slate-400">
                                            {p.plan_name}
                                        </td>
                                        <td className="py-3.5 px-4 font-black text-emerald-400">
                                            {p.amount.toLocaleString()} {p.currency}
                                        </td>
                                        <td className="py-3.5 px-4 uppercase text-[10px] text-slate-400">
                                            {p.provider}
                                        </td>
                                        <td className="py-3.5 px-4">
                                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                                p.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'
                                            }`}>
                                                {p.status}
                                            </span>
                                        </td>
                                        <td className="py-3.5 px-4 text-slate-400">
                                            {p.paid_at || 'N/A'}
                                        </td>
                                    </tr>
                                ))}
                                {payments.data.length === 0 && (
                                    <tr>
                                        <td colSpan="7" className="py-10 text-center text-slate-500">
                                            Aucun paiement enregistré pour le moment.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Record Manual Payment Modal */}
            {modalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4">
                        <div className="flex items-center justify-between">
                            <h3 className="text-sm font-bold text-white uppercase tracking-wider">
                                Enregistrer un Paiement Manuel
                            </h3>
                            <button
                                type="button"
                                onClick={() => setModalOpen(false)}
                                className="text-slate-400 hover:text-white"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>
                        <form onSubmit={handleRecordPayment} className="space-y-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Organisation cliente :</label>
                                <select
                                    value={data.organization_id}
                                    onChange={(e) => setData('organization_id', e.target.value)}
                                    required
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
                                >
                                    {organizations.map((org) => (
                                        <option key={org.id} value={org.id}>{org.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Montant :</label>
                                    <input
                                        type="number"
                                        min="1"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        placeholder="19000"
                                        required
                                        className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Devise :</label>
                                    <input
                                        type="text"
                                        value={data.currency}
                                        onChange={(e) => setData('currency', e.target.value)}
                                        className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Moyen / Référence :</label>
                                <input
                                    type="text"
                                    value={data.provider_reference}
                                    onChange={(e) => setData('provider_reference', e.target.value)}
                                    placeholder="Ex: Virement #VIR-2026-004 ou Chèque #12345"
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-semibold text-slate-300 mb-1">Date d'encaissement :</label>
                                <input
                                    type="date"
                                    value={data.paid_at}
                                    onChange={(e) => setData('paid_at', e.target.value)}
                                    required
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white"
                                />
                            </div>
                            <div className="flex items-center justify-end gap-2 pt-2">
                                <button
                                    type="button"
                                    onClick={() => setModalOpen(false)}
                                    className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50"
                                >
                                    {processing ? 'Enregistrement...' : 'Valider le paiement'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </PlatformLayout>
    );
}
