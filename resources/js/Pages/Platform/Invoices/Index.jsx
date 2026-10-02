import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Receipt,
    Search,
    CheckCircle2,
    Clock,
    AlertCircle,
    Eye,
    Building2,
    Check
} from 'lucide-react';

export default function InvoicesIndex({
    invoices,
    totalIssuedAmount = 0,
    totalPaidAmount = 0,
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/invoices', {
            search: search.trim() || undefined,
            status: status || undefined
        }, { preserveState: true });
    };

    const handleMarkPaid = (id, number) => {
        if (confirm(`Confirmer l'encaissement de la facture ${number} ? Un paiement manuel sera enregistré.`)) {
            router.post(`/platform/invoices/${id}/mark-paid`);
        }
    };

    const getStatusBadge = (invoiceStatus) => {
        switch (invoiceStatus) {
            case 'paid':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <CheckCircle2 className="w-3.5 h-3.5" /> Payée
                    </span>
                );
            case 'issued':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                        <Clock className="w-3.5 h-3.5" /> Émise
                    </span>
                );
            case 'overdue':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        <AlertCircle className="w-3.5 h-3.5" /> En retard
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-500/10 text-slate-400 border border-slate-500/20">
                        {invoiceStatus}
                    </span>
                );
        }
    };

    return (
        <PlatformLayout title="Facturation SaaS">
            <Head title="Factures — Console Propriétaire" />

            <div className="space-y-6">
                {/* Header Stats */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                        <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Facturé Émis</span>
                        <div className="text-2xl sm:text-3xl font-black text-indigo-400 mt-1">
                            {totalIssuedAmount.toLocaleString()} XOF
                        </div>
                    </div>
                    <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                        <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Recouvré (Payé)</span>
                        <div className="text-2xl sm:text-3xl font-black text-emerald-400 mt-1">
                            {totalPaidAmount.toLocaleString()} XOF
                        </div>
                    </div>
                </div>

                {/* Filter and Search Bar */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        <div className="relative w-full sm:w-80">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="N° facture, organisation..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-10 pr-4 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            />
                        </div>

                        <select
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                router.get('/platform/invoices', {
                                    search: search.trim() || undefined,
                                    status: e.target.value || undefined
                                }, { preserveState: true });
                            }}
                            className="w-full sm:w-44 px-3 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="issued">Émise</option>
                            <option value="paid">Payée</option>
                            <option value="overdue">En retard</option>
                            <option value="cancelled">Annulée</option>
                        </select>

                        <button
                            type="submit"
                            className="w-full sm:w-auto px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold rounded-xl transition"
                        >
                            Filtrer
                        </button>
                    </form>
                </div>

                {/* Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl shadow-slate-950/40">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-400">
                            <thead className="bg-slate-950/60 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80">
                                <tr>
                                    <th className="px-6 py-4">N° Facture</th>
                                    <th className="px-6 py-4">Organisation</th>
                                    <th className="px-6 py-4">Plan</th>
                                    <th className="px-6 py-4">Montant</th>
                                    <th className="px-6 py-4">Statut</th>
                                    <th className="px-6 py-4">Échéance</th>
                                    <th className="px-6 py-4">Payée le</th>
                                    <th className="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {invoices.data && invoices.data.length > 0 ? (
                                    invoices.data.map((inv) => (
                                        <tr key={inv.id} className="hover:bg-slate-800/30 transition">
                                            <td className="px-6 py-4 font-mono font-bold text-slate-200">
                                                {inv.invoice_number}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-2">
                                                    <Building2 className="w-4 h-4 text-slate-500" />
                                                    <Link
                                                        href={`/platform/organizations/${inv.organization_id}`}
                                                        className="font-medium text-slate-200 hover:text-indigo-400 transition"
                                                    >
                                                        {inv.organization_name}
                                                    </Link>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className="px-2.5 py-1 rounded-lg text-xs font-semibold bg-slate-800 text-slate-300 border border-slate-700">
                                                    {inv.plan_name}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 font-bold text-slate-100">
                                                {inv.amount.toLocaleString()} {inv.currency}
                                            </td>
                                            <td className="px-6 py-4">
                                                {getStatusBadge(inv.status)}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-400">
                                                {inv.due_at || '—'}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-400">
                                                {inv.paid_at || '—'}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link
                                                        href={`/platform/invoices/${inv.id}`}
                                                        className="p-2 text-slate-400 hover:text-slate-200 hover:bg-slate-800 rounded-lg transition"
                                                        title="Voir détails"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>
                                                    {inv.status !== 'paid' && (
                                                        <button
                                                            onClick={() => handleMarkPaid(inv.id, inv.invoice_number)}
                                                            className="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/30 rounded-lg transition"
                                                            title="Marquer comme payée"
                                                        >
                                                            <Check className="w-3.5 h-3.5" /> Encaisser
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="px-6 py-12 text-center text-slate-500">
                                            <Receipt className="w-10 h-10 mx-auto mb-3 opacity-40" />
                                            Aucune facture trouvée.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {invoices.links && invoices.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between">
                            <span className="text-xs text-slate-500">
                                Total : {invoices.total} facture(s)
                            </span>
                            <div className="flex gap-1">
                                {invoices.links.map((link, idx) => (
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
