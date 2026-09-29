import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import {
    CreditCard,
    Sparkles,
    AlertTriangle,
    CheckCircle2,
    Calendar,
    ArrowUpRight,
    Users,
    HardDrive,
    Building2,
    FileStack,
    FileText,
    Download,
    RefreshCw,
    ShieldAlert,
    Clock,
    XCircle
} from 'lucide-react';

export default function SubscriptionShow({
    subscription,
    usage = {},
    invoices = [],
    plans = [],
    can = {}
}) {
    const [confirmCancelOpen, setConfirmCancelOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const metrics = usage.metrics || {};
    const warnings = usage.warnings || [];

    const formatFCFA = (amount) => {
        if (!amount) return '0 FCFA';
        return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
    };

    const handleCancel = () => {
        setProcessing(true);
        router.post('/settings/subscription/cancel', {}, {
            onFinish: () => {
                setProcessing(false);
                setConfirmCancelOpen(false);
            },
        });
    };

    const handleResume = () => {
        setProcessing(true);
        router.post('/settings/subscription/resume', {}, {
            onFinish: () => setProcessing(false),
        });
    };

    const getStatusBadge = (status, isTrial) => {
        if (isTrial) {
            return (
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/10 text-amber-600 border border-amber-500/20">
                    <Clock className="w-3.5 h-3.5" />
                    Période d'essai
                </span>
            );
        }

        switch (status) {
            case 'active':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                        <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                        Actif
                    </span>
                );
            case 'cancelled':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-600 border border-rose-500/20">
                        <XCircle className="w-3.5 h-3.5" />
                        Résilié (fin de période)
                    </span>
                );
            case 'expired':
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-500/10 text-red-600 border border-red-500/20">
                        <AlertTriangle className="w-3.5 h-3.5" />
                        Expiré
                    </span>
                );
            default:
                return (
                    <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-500/10 text-slate-600 border border-slate-500/20">
                        {status}
                    </span>
                );
        }
    };

    const renderProgressBar = (percentage, isExceeded) => {
        let barColor = 'bg-blue-600';
        if (isExceeded) {
            barColor = 'bg-rose-500';
        } else if (percentage >= 80) {
            barColor = 'bg-amber-500';
        }

        return (
            <div className="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                <div
                    className={`h-2.5 rounded-full transition-all duration-500 ${barColor}`}
                    style={{ width: `${Math.min(100, percentage)}%` }}
                />
            </div>
        );
    };

    return (
        <AuthenticatedLayout title="Abonnement & Facturation">
            <Head title="Abonnement & Facturation — GEDAPP" />

            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-black text-slate-900 tracking-tight">
                            Mon abonnement
                        </h1>
                        <p className="text-xs text-slate-500 mt-1">
                            Gérez votre formule SaaS, vos limites d'utilisation et vos factures.
                        </p>
                    </div>

                    {can.manage && (
                        <div className="flex items-center gap-3">
                            <Link
                                href="/subscription/choose"
                                className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-600/20 transition"
                            >
                                <ArrowUpRight className="w-4 h-4" />
                                <span>Changer de plan</span>
                            </Link>
                        </div>
                    )}
                </div>

                {/* Trial Alert Banner if in Trial */}
                {subscription?.is_trial && (
                    <div className={`rounded-2xl p-5 border flex items-start gap-4 ${
                        subscription.trial_days_remaining <= 3
                            ? 'bg-amber-50 border-amber-200 text-amber-900'
                            : 'bg-blue-50 border-blue-200 text-blue-900'
                    }`}>
                        <div className={`p-2.5 rounded-xl shrink-0 ${
                            subscription.trial_days_remaining <= 3 ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700'
                        }`}>
                            <Clock className="w-5 h-5" />
                        </div>
                        <div className="flex-1">
                            <h4 className="text-sm font-bold">
                                {subscription.trial_days_remaining > 0
                                    ? `Votre période d'essai gratuit se termine dans ${subscription.trial_days_remaining} jour(s).`
                                    : "Votre période d'essai gratuit a pris fin."}
                            </h4>
                            <p className="text-xs mt-1 opacity-90 leading-relaxed">
                                Profitez de l'intégralité de vos fonctionnalités jusqu'au {subscription.trial_ends_at}. Choisissez une formule dès maintenant pour continuer à utiliser GEDAPP sans interruption.
                            </p>
                        </div>
                        {can.manage && (
                            <Link
                                href="/subscription/choose"
                                className="shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 text-white hover:bg-blue-700 transition"
                            >
                                Choisir mon offre
                            </Link>
                        )}
                    </div>
                )}

                {/* Limit Warnings Banner */}
                {warnings.length > 0 && (
                    <div className="space-y-2">
                        {warnings.map((w, idx) => (
                            <div key={idx} className="rounded-xl p-4 bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center justify-between">
                                <div className="flex items-center gap-2.5">
                                    <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" />
                                    <span>{w.message}</span>
                                </div>
                                {can.manage && (
                                    <Link
                                        href="/subscription/choose"
                                        className="font-bold text-amber-700 hover:text-amber-800 underline ml-4 shrink-0"
                                    >
                                        Mettre à niveau
                                    </Link>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {/* Main Subscription Info Cards */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    {/* Plan Summary Card */}
                    <div className="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs flex flex-col justify-between">
                        <div>
                            <div className="flex items-start justify-between">
                                <div>
                                    <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                        Formule active
                                    </span>
                                    <h2 className="text-2xl font-black text-slate-900 mt-1">
                                        {subscription?.plan?.name || 'Essentiel'}
                                    </h2>
                                </div>
                                <div>
                                    {getStatusBadge(subscription?.status, subscription?.is_trial)}
                                </div>
                            </div>

                            <p className="text-xs text-slate-500 mt-2 max-w-lg">
                                {subscription?.plan?.description || 'Gestion électronique de documents pour votre entreprise.'}
                            </p>

                            <div className="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <span className="text-[11px] font-semibold text-slate-400">
                                        Tarif contractuel
                                    </span>
                                    <p className="text-lg font-black text-slate-900 mt-0.5">
                                        {subscription?.billing_cycle === 'annual'
                                            ? formatFCFA(subscription?.plan?.annual_price)
                                            : formatFCFA(subscription?.plan?.monthly_price)}
                                        <span className="text-xs font-normal text-slate-500">
                                            {' '}/ {subscription?.billing_cycle === 'annual' ? 'an' : 'mois'}
                                        </span>
                                    </p>
                                </div>

                                <div>
                                    <span className="text-[11px] font-semibold text-slate-400">
                                        Prochaine échéance
                                    </span>
                                    <p className="text-sm font-bold text-slate-800 mt-1 flex items-center gap-1.5">
                                        <Calendar className="w-4 h-4 text-slate-400" />
                                        <span>
                                            {subscription?.is_trial
                                                ? subscription?.trial_ends_at
                                                : (subscription?.current_period_ends_at || 'Renouvellement automatique')}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Action buttons */}
                        {can.manage && (
                            <div className="mt-8 pt-6 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                                <Link
                                    href="/subscription/choose"
                                    className="px-4 py-2.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-xs"
                                >
                                    Changer de formule
                                </Link>

                                {subscription?.status === 'cancelled' ? (
                                    <button
                                        type="button"
                                        onClick={handleResume}
                                        disabled={processing}
                                        className="text-xs font-bold text-emerald-600 hover:text-emerald-700 transition"
                                    >
                                        Reprendre mon abonnement
                                    </button>
                                ) : (
                                    <button
                                        type="button"
                                        onClick={() => setConfirmCancelOpen(true)}
                                        className="text-xs font-semibold text-slate-400 hover:text-rose-600 transition"
                                    >
                                        Annuler le renouvellement
                                    </button>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Quick Features Highlight */}
                    <div className="bg-slate-900 rounded-3xl p-6 sm:p-8 text-white flex flex-col justify-between shadow-xl shadow-slate-900/10">
                        <div>
                            <div className="flex items-center gap-2.5 text-blue-400 text-xs font-bold uppercase tracking-wider mb-4">
                                <Sparkles className="w-4 h-4" />
                                <span>Avantages de votre plan</span>
                            </div>

                            <ul className="space-y-3.5 text-xs text-slate-300">
                                <li className="flex items-center gap-2.5">
                                    <CheckCircle2 className="w-4 h-4 text-blue-400 shrink-0" />
                                    <span>Stockage cloud haute sécurité R2</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <CheckCircle2 className="w-4 h-4 text-blue-400 shrink-0" />
                                    <span>Recherche indexée & OCR automatique</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <CheckCircle2 className="w-4 h-4 text-blue-400 shrink-0" />
                                    <span>Gestion fine des droits & groupes</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <CheckCircle2 className="w-4 h-4 text-blue-400 shrink-0" />
                                    <span>Multi-tenant étanche certifié</span>
                                </li>
                            </ul>
                        </div>

                        <div className="mt-8 pt-6 border-t border-slate-800/80">
                            <span className="text-[11px] text-slate-400 block">Besoin d'aide ou d'un volume sur mesure ?</span>
                            <a
                                href="mailto:support@gedapp.com"
                                className="text-xs font-bold text-blue-400 hover:text-blue-300 transition mt-1 inline-block"
                            >
                                Contacter notre assistance →
                            </a>
                        </div>
                    </div>
                </div>

                {/* Resource Usage Progress Section */}
                <div className="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h3 className="text-lg font-black text-slate-900">
                                Utilisation des ressources
                            </h3>
                            <p className="text-xs text-slate-500 mt-0.5">
                                Suivi en temps réel de votre consommation par rapport aux quotas de l'organisation.
                            </p>
                        </div>
                        {usage.is_any_exceeded && (
                            <span className="px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-200 flex items-center gap-1.5">
                                <AlertTriangle className="w-3.5 h-3.5" />
                                Quota dépassé
                            </span>
                        )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 pt-2">
                        
                        {/* 1. Utilisateurs */}
                        {metrics.users && (
                            <div className="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center gap-2 text-slate-700 font-bold text-xs">
                                            <Users className="w-4 h-4 text-blue-600" />
                                            <span>Utilisateurs</span>
                                        </div>
                                        <span className="text-xs font-black text-slate-900">
                                            {metrics.users.formatted}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-slate-400 mb-3">
                                        Membres actifs dans l'organisation
                                    </p>
                                </div>
                                <div>
                                    {renderProgressBar(metrics.users.percentage, metrics.users.is_exceeded)}
                                    <span className="text-[10px] text-slate-400 mt-1.5 block text-right font-medium">
                                        {metrics.users.percentage}% utilisé
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* 2. Stockage */}
                        {metrics.storage && (
                            <div className="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center gap-2 text-slate-700 font-bold text-xs">
                                            <HardDrive className="w-4 h-4 text-blue-600" />
                                            <span>Stockage</span>
                                        </div>
                                        <span className="text-xs font-black text-slate-900">
                                            {metrics.storage.formatted}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-slate-400 mb-3">
                                        Poids total des fichiers et versions
                                    </p>
                                </div>
                                <div>
                                    {renderProgressBar(metrics.storage.percentage, metrics.storage.is_exceeded)}
                                    <span className="text-[10px] text-slate-400 mt-1.5 block text-right font-medium">
                                        {metrics.storage.percentage}% utilisé
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* 3. OCR */}
                        {metrics.ocr && (
                            <div className="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center gap-2 text-slate-700 font-bold text-xs">
                                            <FileText className="w-4 h-4 text-blue-600" />
                                            <span>Pages OCR (ce mois)</span>
                                        </div>
                                        <span className="text-xs font-black text-slate-900">
                                            {metrics.ocr.formatted}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-slate-400 mb-3">
                                        Reconnaissance de texte mensuelle
                                    </p>
                                </div>
                                <div>
                                    {renderProgressBar(metrics.ocr.percentage, metrics.ocr.is_exceeded)}
                                    <span className="text-[10px] text-slate-400 mt-1.5 block text-right font-medium">
                                        {metrics.ocr.percentage}% utilisé
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* 4. Directions */}
                        {metrics.directions && (
                            <div className="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center gap-2 text-slate-700 font-bold text-xs">
                                            <Building2 className="w-4 h-4 text-blue-600" />
                                            <span>Directions</span>
                                        </div>
                                        <span className="text-xs font-black text-slate-900">
                                            {metrics.directions.formatted}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-slate-400 mb-3">
                                        Dossiers de directions d'entreprise
                                    </p>
                                </div>
                                <div>
                                    {renderProgressBar(metrics.directions.percentage, metrics.directions.is_exceeded)}
                                    <span className="text-[10px] text-slate-400 mt-1.5 block text-right font-medium">
                                        {metrics.directions.percentage}% utilisé
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* 5. Types Documentaires */}
                        {metrics.document_types && (
                            <div className="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <div className="flex items-center gap-2 text-slate-700 font-bold text-xs">
                                            <FileStack className="w-4 h-4 text-blue-600" />
                                            <span>Types documentaires</span>
                                        </div>
                                        <span className="text-xs font-black text-slate-900">
                                            {metrics.document_types.formatted}
                                        </span>
                                    </div>
                                    <p className="text-[11px] text-slate-400 mb-3">
                                        Types avec métadonnées personnalisées
                                    </p>
                                </div>
                                <div>
                                    {renderProgressBar(metrics.document_types.percentage, metrics.document_types.is_exceeded)}
                                    <span className="text-[10px] text-slate-400 mt-1.5 block text-right font-medium">
                                        {metrics.document_types.percentage}% utilisé
                                    </span>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                {/* Invoices History Table */}
                <div className="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-xs">
                    <h3 className="text-lg font-black text-slate-900 mb-1">
                        Historique des factures
                    </h3>
                    <p className="text-xs text-slate-500 mb-6">
                        Consultez et téléchargez les reçus de règlement émis pour votre organisation.
                    </p>

                    {invoices.length === 0 ? (
                        <div className="text-center py-10 border border-dashed border-slate-200 rounded-2xl">
                            <CreditCard className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                            <p className="text-xs text-slate-500 font-medium">
                                Aucune facture émise pour le moment.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider">
                                        <th className="py-3 px-4">Numéro</th>
                                        <th className="py-3 px-4">Date</th>
                                        <th className="py-3 px-4">Montant</th>
                                        <th className="py-3 px-4">Statut</th>
                                        <th className="py-3 px-4 text-right">Reçu</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-slate-700">
                                    {invoices.map((inv) => (
                                        <tr key={inv.id} className="hover:bg-slate-50/60 transition">
                                            <td className="py-3.5 px-4 font-bold text-slate-900">
                                                {inv.invoice_number}
                                            </td>
                                            <td className="py-3.5 px-4 text-slate-500">
                                                {inv.paid_at || inv.created_at}
                                            </td>
                                            <td className="py-3.5 px-4 font-semibold text-slate-900">
                                                {formatFCFA(inv.amount)}
                                            </td>
                                            <td className="py-3.5 px-4">
                                                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Réglée
                                                </span>
                                            </td>
                                            <td className="py-3.5 px-4 text-right">
                                                <button
                                                    type="button"
                                                    title="Télécharger"
                                                    className="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition"
                                                >
                                                    <Download className="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Cancel Confirmation Modal */}
                {confirmCancelOpen && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                        <div className="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-4">
                            <div className="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                                <AlertTriangle className="w-6 h-6" />
                            </div>
                            <h3 className="text-lg font-black text-slate-900">
                                Confirmer la désactivation du renouvellement ?
                            </h3>
                            <p className="text-xs text-slate-500 leading-relaxed">
                                Vos données resteront intégralement conservées et votre accès restera actif jusqu'à la fin de la période en cours. Aucun prélèvement ultérieur ne sera effectué.
                            </p>
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setConfirmCancelOpen(false)}
                                    className="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900"
                                >
                                    Conserver mon abonnement
                                </button>
                                <button
                                    type="button"
                                    onClick={handleCancel}
                                    disabled={processing}
                                    className="px-4 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white transition"
                                >
                                    {processing ? 'Traitement...' : 'Confirmer l\'annulation'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
