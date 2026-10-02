import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    CreditCard,
    ArrowLeft,
    Clock,
    AlertTriangle,
    X,
    CheckCircle2
} from 'lucide-react';

export default function SubscriptionsShow({ subscription, plans = [] }) {
    const org = subscription.organization;
    const plan = subscription.plan;

    const [extendDays, setExtendDays] = useState(14);
    const [extending, setExtending] = useState(false);

    const handleExtend = (e) => {
        e.preventDefault();
        setExtending(true);
        router.post(`/platform/subscriptions/${subscription.id}/extend-trial`, {
            days: extendDays,
        }, {
            onFinish: () => setExtending(false)
        });
    };

    const handleEndTrial = () => {
        router.post(`/platform/subscriptions/${subscription.id}/end-trial`);
    };

    const handleCancel = () => {
        if (confirm('Voulez-vous vraiment résilier cet abonnement ?')) {
            router.post(`/platform/subscriptions/${subscription.id}/cancel`);
        }
    };

    return (
        <PlatformLayout title={`Abonnement #${subscription.id}`}>
            <Head title={`Abonnement #${subscription.id} — Console Propriétaire`} />

            <div className="max-w-4xl mx-auto space-y-6">
                <Link
                    href="/platform/subscriptions"
                    className="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition"
                >
                    <ArrowLeft className="w-4 h-4" />
                    <span>Retour aux abonnements</span>
                </Link>

                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 sm:p-8 space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <span className="text-xs font-bold text-indigo-400 uppercase tracking-wider block">
                                Organisation : {org?.name}
                            </span>
                            <h2 className="text-xl font-black text-white mt-1">
                                Plan {plan?.name || 'Essentiel'} ({subscription.billing_cycle === 'annual' ? 'Annuel' : 'Mensuel'})
                            </h2>
                        </div>
                        <span className="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            {subscription.status}
                        </span>
                    </div>

                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs">
                        <div>
                            <span className="text-slate-500 block">Date de début</span>
                            <span className="font-bold text-white mt-0.5 block">{subscription.starts_at ? new Date(subscription.starts_at).toLocaleDateString('fr-FR') : 'N/A'}</span>
                        </div>
                        <div>
                            <span className="text-slate-500 block">Échéance courante</span>
                            <span className="font-bold text-white mt-0.5 block">{subscription.current_period_ends_at ? new Date(subscription.current_period_ends_at).toLocaleDateString('fr-FR') : 'N/A'}</span>
                        </div>
                        <div>
                            <span className="text-slate-500 block">Fin période d'essai</span>
                            <span className="font-bold text-amber-400 mt-0.5 block">{subscription.trial_ends_at ? new Date(subscription.trial_ends_at).toLocaleDateString('fr-FR') : 'Aucune'}</span>
                        </div>
                        <div>
                            <span className="text-slate-500 block">Renouvellement auto</span>
                            <span className="font-bold text-white mt-0.5 block">{subscription.auto_renew ? 'Oui' : 'Non'}</span>
                        </div>
                    </div>

                    {/* Actions Panel */}
                    <div className="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-4">
                        <h3 className="text-xs font-bold text-white uppercase tracking-wider">
                            Actions Administrateur SaaS
                        </h3>
                        <div className="flex flex-wrap items-center gap-3">
                            <form onSubmit={handleExtend} className="flex items-center gap-2">
                                <input
                                    type="number"
                                    min="1"
                                    max="90"
                                    value={extendDays}
                                    onChange={(e) => setExtendDays(e.target.value)}
                                    className="w-20 px-2.5 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white"
                                />
                                <button
                                    type="submit"
                                    disabled={extending}
                                    className="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white transition disabled:opacity-50"
                                >
                                    {extending ? '...' : "Prolonger l'essai (jours)"}
                                </button>
                            </form>

                            <button
                                type="button"
                                onClick={handleEndTrial}
                                className="px-3 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-500 text-xs font-bold text-white transition"
                            >
                                Terminer l'essai maintenant
                            </button>

                            <button
                                type="button"
                                onClick={handleCancel}
                                className="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white transition"
                            >
                                Résilier l'abonnement
                            </button>
                        </div>
                    </div>

                    {/* Invoices list */}
                    <div>
                        <h3 className="text-xs font-bold text-white uppercase tracking-wider mb-3">
                            Factures émises ({subscription.invoices?.length || 0})
                        </h3>
                        <div className="space-y-2">
                            {subscription.invoices?.map((inv) => (
                                <div key={inv.id} className="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center justify-between text-xs">
                                    <div>
                                        <span className="font-bold text-white">{inv.invoice_number}</span>
                                        <span className="text-slate-400 ml-2">{inv.amount?.toLocaleString()} {inv.currency}</span>
                                    </div>
                                    <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase ${
                                        inv.status === 'paid' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'
                                    }`}>
                                        {inv.status}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </PlatformLayout>
    );
}
