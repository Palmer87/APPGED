import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import {
    Check,
    Crown,
    Sparkles,
    ArrowLeft,
    CheckCircle2,
    Building2,
    Shield,
    Zap,
    Mail
} from 'lucide-react';

export default function SubscriptionChoose({
    currentSubscription = {},
    plans = [],
    can = {}
}) {
    const currentPlanSlug = currentSubscription?.plan_slug || 'essential';
    const [billingCycle, setBillingCycle] = useState(currentSubscription?.billing_cycle || 'monthly');
    const [selectedPlan, setSelectedPlan] = useState(null);
    const [confirmModalOpen, setConfirmModalOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const formatFCFA = (amount) => {
        if (!amount) return 'Sur devis';
        return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
    };

    const handleSelectPlan = (plan) => {
        if (plan.slug === 'enterprise') {
            window.location.href = 'mailto:commercial@gedapp.com?subject=Demande%20d\'offre%20Entreprise%20GEDAPP';
            return;
        }

        setSelectedPlan(plan);
        setConfirmModalOpen(true);
    };

    const handleConfirmChange = () => {
        if (!selectedPlan) return;
        setProcessing(true);

        router.post('/settings/subscription/change-plan', {
            plan_slug: selectedPlan.slug,
            billing_cycle: billingCycle,
        }, {
            onFinish: () => {
                setProcessing(false);
                setConfirmModalOpen(false);
            },
        });
    };

    return (
        <AuthenticatedLayout title="Choisir un plan">
            <Head title="Choisir une formule — GEDAPP" />

            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                
                {/* Back button & Title */}
                <div>
                    <Link
                        href="/settings/subscription"
                        className="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition mb-4"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        <span>Retour à mon abonnement</span>
                    </Link>

                    <div className="text-center max-w-2xl mx-auto">
                        <h1 className="text-3xl font-black text-slate-900 tracking-tight">
                            Choisissez la formule adaptée à vos besoins
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-500 mt-2">
                            Passez au niveau supérieur pour débloquer davantage de stockage, de membres et les fonctionnalités avancées de GEDAPP.
                        </p>

                        {/* Cycle Toggle */}
                        <div className="mt-8 inline-flex p-1 rounded-2xl bg-slate-100 border border-slate-200">
                            <button
                                type="button"
                                onClick={() => setBillingCycle('monthly')}
                                className={`px-5 py-2 rounded-xl text-xs font-bold transition ${
                                    billingCycle === 'monthly'
                                        ? 'bg-white text-slate-900 shadow-xs'
                                        : 'text-slate-500 hover:text-slate-900'
                                }`}
                            >
                                Facturation mensuelle
                            </button>
                            <button
                                type="button"
                                onClick={() => setBillingCycle('annual')}
                                className={`px-5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 ${
                                    billingCycle === 'annual'
                                        ? 'bg-white text-slate-900 shadow-xs'
                                        : 'text-slate-500 hover:text-slate-900'
                                }`}
                            >
                                <span>Facturation annuelle</span>
                                <span className="text-[10px] px-1.5 py-0.5 rounded-full font-extrabold bg-emerald-100 text-emerald-800">
                                    -20%
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Plan Cards Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch pt-4">
                    
                    {/* 1. Essentiel */}
                    <div className={`rounded-3xl p-8 border flex flex-col justify-between transition ${
                        currentPlanSlug === 'essential'
                            ? 'bg-blue-50/40 border-blue-300 ring-2 ring-blue-500/20 shadow-sm'
                            : 'bg-white border-slate-200 shadow-xs hover:border-slate-300'
                    }`}>
                        <div>
                            <div className="flex items-center justify-between mb-3">
                                <h3 className="text-xl font-bold text-slate-900">Essentiel</h3>
                                {currentPlanSlug === 'essential' && (
                                    <span className="text-[10px] font-bold text-blue-700 px-2.5 py-1 rounded-full bg-blue-100 border border-blue-200">
                                        Plan actuel
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-500 mb-6">
                                Pour les petites structures démarrant la gestion documentaire.
                            </p>

                            <div className="mb-6 pb-6 border-b border-slate-100">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-3xl font-black text-slate-900">
                                        {billingCycle === 'monthly' ? '19 000' : '190 000'} FCFA
                                    </span>
                                    <span className="text-xs text-slate-400 font-medium">
                                        / {billingCycle === 'monthly' ? 'mois' : 'an'}
                                    </span>
                                </div>
                                {billingCycle === 'annual' && (
                                    <p className="text-[11px] text-emerald-600 font-semibold mt-1">
                                        190 000 FCFA/an au lieu de 228 000 FCFA/an
                                    </p>
                                )}
                            </div>

                            <ul className="space-y-3 text-xs text-slate-600 mb-8">
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                    <span>5 utilisateurs maximum</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                    <span>20 Go de stockage</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                    <span>3 directions</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                    <span>15 types documentaires</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                    <span>100 pages OCR / mois</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                    <span>Support par email</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            {currentPlanSlug === 'essential' ? (
                                <button
                                    type="button"
                                    disabled
                                    className="w-full py-3 px-4 rounded-xl text-xs font-bold text-center bg-slate-100 text-slate-400 cursor-not-allowed"
                                >
                                    Formule actuelle
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => handleSelectPlan({ slug: 'essential', name: 'Essentiel' })}
                                    className="w-full py-3 px-4 rounded-xl text-xs font-bold text-center bg-slate-900 hover:bg-slate-800 text-white transition"
                                >
                                    Choisir l'offre Essentiel
                                </button>
                            )}
                        </div>
                    </div>

                    {/* 2. Professionnel (Recommended) */}
                    <div className={`rounded-3xl p-8 border-2 flex flex-col justify-between relative shadow-lg ${
                        currentPlanSlug === 'professional'
                            ? 'bg-blue-50/50 border-blue-600 ring-4 ring-blue-600/10'
                            : 'bg-white border-blue-600 shadow-blue-500/10'
                    }`}>
                        <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-[11px] font-bold px-3.5 py-1 rounded-full uppercase tracking-wider shadow-sm flex items-center gap-1.5">
                            <Crown className="w-3.5 h-3.5 text-amber-300" />
                            <span>⭐ POPULAIRE</span>
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-3 mt-1">
                                <h3 className="text-xl font-bold text-slate-900">Professionnel</h3>
                                {currentPlanSlug === 'professional' && (
                                    <span className="text-[10px] font-bold text-blue-700 px-2.5 py-1 rounded-full bg-blue-100 border border-blue-200">
                                        Plan actuel
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-500 mb-6">
                                Pour les PME nécessitant des circuits de validation et haute capacité.
                            </p>

                            <div className="mb-6 pb-6 border-b border-slate-100">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-3xl font-black text-slate-900">
                                        {billingCycle === 'monthly' ? '39 000' : '390 000'} FCFA
                                    </span>
                                    <span className="text-xs text-slate-400 font-medium">
                                        / {billingCycle === 'monthly' ? 'mois' : 'an'}
                                    </span>
                                </div>
                                {billingCycle === 'annual' && (
                                    <p className="text-[11px] text-emerald-600 font-semibold mt-1">
                                        390 000 FCFA/an au lieu de 468 000 FCFA/an
                                    </p>
                                )}
                            </div>

                            <ul className="space-y-3 text-xs text-slate-700 font-medium mb-8">
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0 font-bold" />
                                    <span>20 utilisateurs inclus</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0 font-bold" />
                                    <span>100 Go de stockage cloud R2</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0 font-bold" />
                                    <span>10 directions & 50 types doc</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0 font-bold" />
                                    <span>1 000 pages OCR / mois</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0 font-bold" />
                                    <span><strong>Workflows & Instances</strong></span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-blue-600 shrink-0 font-bold" />
                                    <span><strong>Audit avancé & API REST</strong></span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            {currentPlanSlug === 'professional' ? (
                                <button
                                    type="button"
                                    disabled
                                    className="w-full py-3 px-4 rounded-xl text-xs font-bold text-center bg-slate-100 text-slate-400 cursor-not-allowed"
                                >
                                    Formule actuelle
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => handleSelectPlan({ slug: 'professional', name: 'Professionnel' })}
                                    className="w-full py-3 px-4 rounded-xl text-xs font-bold text-center bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-600/30 transition"
                                >
                                    Passer au plan Professionnel
                                </button>
                            )}
                        </div>
                    </div>

                    {/* 3. Entreprise */}
                    <div className={`rounded-3xl p-8 border flex flex-col justify-between transition ${
                        currentPlanSlug === 'enterprise'
                            ? 'bg-purple-50/40 border-purple-300 ring-2 ring-purple-500/20 shadow-sm'
                            : 'bg-white border-slate-200 shadow-xs hover:border-slate-300'
                    }`}>
                        <div>
                            <div className="flex items-center justify-between mb-3">
                                <h3 className="text-xl font-bold text-slate-900">Entreprise</h3>
                                {currentPlanSlug === 'enterprise' && (
                                    <span className="text-[10px] font-bold text-purple-700 px-2.5 py-1 rounded-full bg-purple-100 border border-purple-200">
                                        Plan actuel
                                    </span>
                                )}
                            </div>
                            <p className="text-xs text-slate-500 mb-6">
                                Pour les grandes organisations avec exigences strictes et accompagnement.
                            </p>

                            <div className="mb-6 pb-6 border-b border-slate-100">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-3xl font-black text-slate-900">
                                        Sur devis
                                    </span>
                                </div>
                                <p className="text-[11px] text-slate-500 mt-1">
                                    Facturation sur mesure & SLA garanti
                                </p>
                            </div>

                            <ul className="space-y-3 text-xs text-slate-600 mb-8">
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-purple-600 shrink-0" />
                                    <span>Utilisateurs & stockage sur mesure</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-purple-600 shrink-0" />
                                    <span>Directions & Types documentaires illimités</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-purple-600 shrink-0" />
                                    <span>Volumes OCR personnalisés</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-purple-600 shrink-0" />
                                    <span>SLA 99.9% avec engagement contractuel</span>
                                </li>
                                <li className="flex items-center gap-2.5">
                                    <Check className="w-4 h-4 text-purple-600 shrink-0" />
                                    <span>Accompagnement à la migration</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <button
                                type="button"
                                onClick={() => handleSelectPlan({ slug: 'enterprise', name: 'Entreprise' })}
                                className="w-full py-3 px-4 rounded-xl text-xs font-bold text-center bg-slate-900 hover:bg-slate-800 text-white transition flex items-center justify-center gap-2"
                            >
                                <Mail className="w-4 h-4" />
                                <span>Demander une offre personnalisée</span>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Confirm Change Modal */}
                {confirmModalOpen && selectedPlan && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
                        <div className="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-4">
                            <div className="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                <Zap className="w-6 h-6" />
                            </div>
                            <h3 className="text-lg font-black text-slate-900">
                                Confirmer le passage au plan {selectedPlan.name} ?
                            </h3>
                            <p className="text-xs text-slate-500 leading-relaxed">
                                Votre organisation sera immédiatement basculée sur la formule {selectedPlan.name} avec un cycle de facturation {billingCycle === 'annual' ? 'annuel' : 'mensuel'}. Vos données existantes sont intégralement préservées.
                            </p>
                            <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                                <button
                                    type="button"
                                    onClick={() => setConfirmModalOpen(false)}
                                    className="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-900"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="button"
                                    onClick={handleConfirmChange}
                                    disabled={processing}
                                    className="px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-sm"
                                >
                                    {processing ? 'Mise à jour...' : 'Confirmer le changement'}
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
