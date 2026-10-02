import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Receipt,
    ArrowLeft,
    CheckCircle2,
    Clock,
    AlertCircle,
    Building2,
    Printer,
    DollarSign,
    Check
} from 'lucide-react';

export default function InvoiceShow({ invoice }) {
    const handleMarkPaid = () => {
        if (confirm(`Confirmer l'encaissement de la facture ${invoice.invoice_number} ? Un paiement manuel sera enregistré.`)) {
            router.post(`/platform/invoices/${invoice.id}/mark-paid`);
        }
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <PlatformLayout title={`Facture ${invoice.invoice_number}`}>
            <Head title={`Facture ${invoice.invoice_number} — GEDAPP Platform`} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Actions Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden">
                    <Link
                        href="/platform/invoices"
                        className="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-slate-200 transition"
                    >
                        <ArrowLeft className="w-4 h-4" /> Retour aux factures
                    </Link>

                    <div className="flex items-center gap-3">
                        <button
                            onClick={handlePrint}
                            className="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold rounded-xl transition border border-slate-700"
                        >
                            <Printer className="w-4 h-4" /> Imprimer / PDF
                        </button>

                        {invoice.status !== 'paid' && (
                            <button
                                onClick={handleMarkPaid}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-emerald-600/30"
                            >
                                <Check className="w-4 h-4" /> Marquer comme payée
                            </button>
                        )}
                    </div>
                </div>

                {/* Printable Invoice Card */}
                <div className="bg-slate-900/90 border border-slate-800 rounded-3xl p-8 sm:p-12 shadow-2xl relative overflow-hidden">
                    <div className="absolute top-0 right-0 w-96 h-96 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none" />

                    {/* Invoice Header */}
                    <div className="flex flex-col sm:flex-row justify-between items-start gap-6 border-b border-slate-800 pb-8">
                        <div>
                            <div className="flex items-center gap-2.5 mb-2">
                                <div className="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-600 flex items-center justify-center text-white font-black text-lg">
                                    G
                                </div>
                                <span className="text-xl font-bold tracking-tight text-white">GEDAPP SaaS</span>
                            </div>
                            <p className="text-xs text-slate-400">Plateforme Cloud B2B de Gestion Électronique de Documents</p>
                            <p className="text-xs text-slate-500 mt-1">support@gedapp.com • https://gedapp.com</p>
                        </div>

                        <div className="text-left sm:text-right">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500 block">Facture Officielle</span>
                            <div className="text-2xl font-mono font-black text-slate-100 mt-1">
                                {invoice.invoice_number}
                            </div>
                            <div className="mt-2">
                                {invoice.status === 'paid' ? (
                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                        <CheckCircle2 className="w-3.5 h-3.5" /> Payée
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/30">
                                        <Clock className="w-3.5 h-3.5" /> Émise / En attente
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Parties Metadata */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-8 py-8 border-b border-slate-800 text-sm">
                        <div>
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-2">Facturé à :</span>
                            <h3 className="text-base font-bold text-white mb-1">
                                {invoice.organization?.name || 'Organisation'}
                            </h3>
                            <p className="text-slate-400 text-xs">Identifiant : {invoice.organization?.id}</p>
                            <p className="text-slate-400 text-xs mt-1">Email : {invoice.organization?.email || 'N/A'}</p>
                        </div>

                        <div className="sm:text-right">
                            <span className="text-xs font-bold uppercase tracking-wider text-slate-500 block mb-2">Détails de facturation :</span>
                            <p className="text-xs text-slate-400">
                                <strong className="text-slate-300">Date d'émission :</strong> {invoice.created_at ? new Date(invoice.created_at).toLocaleDateString() : '—'}
                            </p>
                            <p className="text-xs text-slate-400 mt-1">
                                <strong className="text-slate-300">Date d'échéance :</strong> {invoice.due_at ? new Date(invoice.due_at).toLocaleDateString() : '—'}
                            </p>
                            {invoice.paid_at && (
                                <p className="text-xs text-emerald-400 mt-1">
                                    <strong>Encaissé le :</strong> {new Date(invoice.paid_at).toLocaleDateString()}
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Line Items */}
                    <div className="py-8 border-b border-slate-800">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="text-xs uppercase text-slate-400 border-b border-slate-800/80">
                                    <th className="py-3">Description</th>
                                    <th className="py-3 text-center">Cycle</th>
                                    <th className="py-3 text-right">Montant</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/40 text-slate-300">
                                <tr>
                                    <td className="py-4">
                                        <div className="font-semibold text-slate-100">
                                            Abonnement GEDAPP — Plan {invoice.subscription?.plan?.name || 'Standard'}
                                        </div>
                                        <div className="text-xs text-slate-400 mt-0.5">
                                            Accès multi-utilisateurs, archivage sécurisé, OCR et workflows documentaires
                                        </div>
                                    </td>
                                    <td className="py-4 text-center text-xs font-semibold capitalize text-slate-400">
                                        {invoice.subscription?.billing_cycle || 'Mensuel'}
                                    </td>
                                    <td className="py-4 text-right font-bold text-slate-100">
                                        {invoice.amount.toLocaleString()} {invoice.currency}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {/* Total Summary */}
                    <div className="pt-6 flex justify-end">
                        <div className="w-full sm:w-72 space-y-2 text-sm">
                            <div className="flex justify-between text-slate-400 text-xs">
                                <span>Total Hors Taxe :</span>
                                <span>{invoice.amount.toLocaleString()} {invoice.currency}</span>
                            </div>
                            <div className="flex justify-between text-slate-400 text-xs">
                                <span>TVA / Taxes (0%) :</span>
                                <span>0 {invoice.currency}</span>
                            </div>
                            <div className="border-t border-slate-800 pt-2 flex justify-between text-base font-black text-white">
                                <span>Total TTC :</span>
                                <span className="text-indigo-400">{invoice.amount.toLocaleString()} {invoice.currency}</span>
                            </div>
                        </div>
                    </div>

                    {/* Associated Payments */}
                    {invoice.payments && invoice.payments.length > 0 && (
                        <div className="mt-8 pt-8 border-t border-slate-800">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Historique des règlements associés</h4>
                            <div className="space-y-2">
                                {invoice.payments.map((p) => (
                                    <div key={p.id} className="flex items-center justify-between text-xs bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                                        <span className="font-mono text-slate-300">{p.provider_reference || `PAY-${p.id}`}</span>
                                        <span className="text-slate-400">Mode : {p.provider}</span>
                                        <span className="font-semibold text-emerald-400">{p.amount.toLocaleString()} {p.currency}</span>
                                        <span className="text-slate-500">{new Date(p.paid_at || p.created_at).toLocaleDateString()}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </PlatformLayout>
    );
}
