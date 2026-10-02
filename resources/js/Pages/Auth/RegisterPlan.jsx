import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    Sparkles,
    Check,
    ArrowRight,
    ArrowLeft,
    ShieldCheck,
    Building2,
    User,
    CheckCircle2,
    Clock,
    Zap,
    AlertCircle,
    HelpCircle
} from 'lucide-react';
import RegistrationSteps from '../../Components/RegistrationSteps';

export default function RegisterPlan({ plans = [], organization = {}, admin = {}, isInscriptionFlow = false }) {
    const { flash } = usePage().props;
    const [billingCycle, setBillingCycle] = useState('monthly');
    const [selectedPlan, setSelectedPlan] = useState('professional');

    const { data, setData, post, processing, errors } = useForm({
        plan: 'professional',
        billing_cycle: 'monthly',
    });

    const handleSelectPlan = (slug) => {
        setSelectedPlan(slug);
        setData('plan', slug);
    };

    const handleSelectBillingCycle = (cycle) => {
        setBillingCycle(cycle);
        setData('billing_cycle', cycle);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const targetUrl = isInscriptionFlow ? '/inscription/plan' : '/register/plan';
        post(targetUrl);
    };

    const isEnterprise = selectedPlan === 'enterprise';

    return (
        <div className="min-h-screen w-full bg-slate-50 text-slate-900 font-sans antialiased py-8 px-4 sm:px-6 lg:px-8">
            <Head title="Choisir votre plan & essai 14 jours — GEDAPP" />

            <div className="max-w-6xl mx-auto space-y-8">
                {/* Brand Header */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-4 pb-4 border-b border-slate-200/80">
                    <Link href="/" className="inline-flex items-center gap-3 group">
                        <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-lg tracking-wider shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform">
                            GED
                        </div>
                        <div>
                            <span className="font-bold text-xl tracking-tight text-slate-950">
                                GED<span className="text-blue-600">APP</span>
                            </span>
                            <span className="block text-[11px] font-medium text-slate-500">
                                Vos documents. Plus loin.
                            </span>
                        </div>
                    </Link>

                    {/* Recap Badges */}
                    <div className="flex flex-wrap items-center gap-2 text-xs">
                        <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-2xs text-slate-700">
                            <Building2 className="w-3.5 h-3.5 text-blue-600" />
                            <span>Organisation : <strong className="text-slate-950">{organization?.name || 'Votre entreprise'}</strong></span>
                        </div>
                        <div className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-slate-200 shadow-2xs text-slate-700">
                            <User className="w-3.5 h-3.5 text-emerald-600" />
                            <span>Admin : <strong className="text-slate-950">{admin?.first_name} {admin?.last_name}</strong></span>
                        </div>
                    </div>
                </div>

                {/* Stepper */}
                <RegistrationSteps currentStep={3} />

                {/* Title & Billing Toggle */}
                <div className="text-center max-w-2xl mx-auto space-y-3">
                    <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold">
                        <Sparkles className="w-3.5 h-3.5 text-blue-600" />
                        14 jours d'essai gratuit sur tous nos plans
                    </div>
                    <h1 className="text-3xl sm:text-4xl font-black text-slate-950 tracking-tight">
                        Choisissez votre formule
                    </h1>
                    <p className="text-sm text-slate-600">
                        Testez gratuitement l'intégralité des fonctionnalités sans engagement ni carte bancaire.
                    </p>

                    {/* Monthly / Annual Toggle */}
                    <div className="pt-2">
                        <div className="inline-flex items-center p-1 bg-white rounded-full border border-slate-200 shadow-2xs">
                            <button
                                type="button"
                                onClick={() => handleSelectBillingCycle('monthly')}
                                className={`px-5 py-2 rounded-full text-xs sm:text-sm font-semibold transition ${
                                    billingCycle === 'monthly'
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                Mensuel
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSelectBillingCycle('annual')}
                                className={`px-5 py-2 rounded-full text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 ${
                                    billingCycle === 'annual'
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <span>Annuel</span>
                                <span className={`text-[10px] px-1.5 py-0.5 rounded-full font-bold ${
                                    billingCycle === 'annual'
                                        ? 'bg-white/20 text-white'
                                        : 'bg-emerald-100 text-emerald-800'
                                }`}>
                                    -20%
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                {/* Flash error */}
                {flash?.error && (
                    <div className="rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 flex items-start gap-3 text-rose-800 text-xs leading-relaxed max-w-2xl mx-auto">
                        <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                        <span>{flash.error}</span>
                    </div>
                )}

                {/* 3 Plans Grid */}
                <form onSubmit={handleSubmit}>
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch mb-8">
                        {/* 1. ESSENTIEL */}
                        <div
                            onClick={() => handleSelectPlan('essential')}
                            className={`rounded-3xl p-6 sm:p-7 border-2 cursor-pointer transition-all duration-200 flex flex-col justify-between ${
                                selectedPlan === 'essential'
                                    ? 'bg-white border-blue-600 shadow-xl shadow-blue-500/10 ring-2 ring-blue-600/20'
                                    : 'bg-white/90 border-slate-200/90 hover:border-slate-300 hover:shadow-md'
                            }`}
                        >
                            <div>
                                <div className="flex items-center justify-between mb-2">
                                    <h3 className="text-xl font-bold text-slate-950">Essentiel</h3>
                                    <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                                        selectedPlan === 'essential'
                                            ? 'border-blue-600 bg-blue-600 text-white'
                                            : 'border-slate-300'
                                    }`}>
                                        {selectedPlan === 'essential' && <Check className="w-3 h-3 stroke-[3]" />}
                                    </div>
                                </div>
                                <p className="text-xs text-slate-500 mb-5">Idéal pour les petites équipes et démarrer la GED.</p>

                                <div className="mb-6 pb-6 border-b border-slate-100">
                                    <div className="flex items-baseline gap-1">
                                        <span className="text-3xl font-black text-slate-950">
                                            {billingCycle === 'monthly' ? '19 000' : '190 000'} FCFA
                                        </span>
                                        <span className="text-xs text-slate-500 font-medium">
                                            {billingCycle === 'monthly' ? '/ mois' : '/ an'}
                                        </span>
                                    </div>
                                    <div className="mt-2 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                        <Clock className="w-3 h-3" />
                                        <span>14 jours d'essai gratuit inclus</span>
                                    </div>
                                </div>

                                <div className="space-y-3 text-xs text-slate-700">
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Jusqu'à <strong>5 utilisateurs</strong></span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span><strong>20 Go</strong> de stockage sécurisé</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>3 directions / départements</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>100 pages OCR par mois</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Recherche intelligente plein texte</span>
                                    </div>
                                </div>
                            </div>

                            <div className="pt-6">
                                <button
                                    type="button"
                                    onClick={() => handleSelectPlan('essential')}
                                    className={`w-full py-2.5 rounded-full text-xs font-bold transition ${
                                        selectedPlan === 'essential'
                                            ? 'bg-blue-600 text-white shadow-xs'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                    }`}
                                >
                                    {selectedPlan === 'essential' ? 'Formule sélectionnée' : 'Sélectionner Essentiel'}
                                </button>
                            </div>
                        </div>

                        {/* 2. PROFESSIONNEL (Populaire) */}
                        <div
                            onClick={() => handleSelectPlan('professional')}
                            className={`rounded-3xl p-6 sm:p-7 border-2 cursor-pointer transition-all duration-200 flex flex-col justify-between relative ${
                                selectedPlan === 'professional'
                                    ? 'bg-white border-blue-600 shadow-2xl shadow-blue-500/15 ring-2 ring-blue-600/30'
                                    : 'bg-white/90 border-slate-200/90 hover:border-slate-300 hover:shadow-md'
                            }`}
                        >
                            {/* Popular pill */}
                            <div className="absolute -top-3.5 right-6 bg-blue-600 text-white text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow-sm flex items-center gap-1">
                                <Zap className="w-3 h-3 fill-current" />
                                <span>Recommandé</span>
                            </div>

                            <div>
                                <div className="flex items-center justify-between mb-2">
                                    <h3 className="text-xl font-bold text-slate-950">Professionnel</h3>
                                    <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                                        selectedPlan === 'professional'
                                            ? 'border-blue-600 bg-blue-600 text-white'
                                            : 'border-slate-300'
                                    }`}>
                                        {selectedPlan === 'professional' && <Check className="w-3 h-3 stroke-[3]" />}
                                    </div>
                                </div>
                                <p className="text-xs text-slate-500 mb-5">Pour les équipes dynamiques nécessitant des workflows.</p>

                                <div className="mb-6 pb-6 border-b border-slate-100">
                                    <div className="flex items-baseline gap-1">
                                        <span className="text-3xl font-black text-slate-950">
                                            {billingCycle === 'monthly' ? '39 000' : '390 000'} FCFA
                                        </span>
                                        <span className="text-xs text-slate-500 font-medium">
                                            {billingCycle === 'monthly' ? '/ mois' : '/ an'}
                                        </span>
                                    </div>
                                    <div className="mt-2 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">
                                        <Clock className="w-3 h-3" />
                                        <span>14 jours d'essai gratuit inclus</span>
                                    </div>
                                </div>

                                <div className="space-y-3 text-xs text-slate-700">
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Jusqu'à <strong>20 utilisateurs</strong></span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span><strong>100 Go</strong> de stockage sécurisé</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>10 directions / départements</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>1 000 pages OCR par mois</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 font-semibold text-blue-900">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Circuits de validation & workflows</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Audit d'activité et conformité avancée</span>
                                    </div>
                                </div>
                            </div>

                            <div className="pt-6">
                                <button
                                    type="button"
                                    onClick={() => handleSelectPlan('professional')}
                                    className={`w-full py-2.5 rounded-full text-xs font-bold transition ${
                                        selectedPlan === 'professional'
                                            ? 'bg-blue-600 text-white shadow-sm'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                    }`}
                                >
                                    {selectedPlan === 'professional' ? 'Formule sélectionnée' : 'Sélectionner Professionnel'}
                                </button>
                            </div>
                        </div>

                        {/* 3. ENTREPRISE */}
                        <div
                            onClick={() => handleSelectPlan('enterprise')}
                            className={`rounded-3xl p-6 sm:p-7 border-2 cursor-pointer transition-all duration-200 flex flex-col justify-between ${
                                selectedPlan === 'enterprise'
                                    ? 'bg-white border-blue-600 shadow-xl shadow-blue-500/10 ring-2 ring-blue-600/20'
                                    : 'bg-white/90 border-slate-200/90 hover:border-slate-300 hover:shadow-md'
                            }`}
                        >
                            <div>
                                <div className="flex items-center justify-between mb-2">
                                    <h3 className="text-xl font-bold text-slate-950">Entreprise</h3>
                                    <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${
                                        selectedPlan === 'enterprise'
                                            ? 'border-blue-600 bg-blue-600 text-white'
                                            : 'border-slate-300'
                                    }`}>
                                        {selectedPlan === 'enterprise' && <Check className="w-3 h-3 stroke-[3]" />}
                                    </div>
                                </div>
                                <p className="text-xs text-slate-500 mb-5">Pour les grandes structures et besoins spécifiques.</p>

                                <div className="mb-6 pb-6 border-b border-slate-100">
                                    <div className="text-3xl font-black text-slate-950">
                                        Sur devis
                                    </div>
                                    <div className="mt-2 text-xs text-slate-500">
                                        Accompagnement commercial & intégration personnalisée.
                                    </div>
                                </div>

                                <div className="space-y-3 text-xs text-slate-700">
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Utilisateurs <strong>illimités</strong></span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Stockage sur mesure</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Directions et types documentaires illimités</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Intégrations SI & API dédiées</span>
                                    </div>
                                    <div className="flex items-center gap-2.5">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>SLA garanti 99,9% & Support dédié</span>
                                    </div>
                                </div>
                            </div>

                            <div className="pt-6">
                                <button
                                    type="button"
                                    onClick={() => handleSelectPlan('enterprise')}
                                    className={`w-full py-2.5 rounded-full text-xs font-bold transition ${
                                        selectedPlan === 'enterprise'
                                            ? 'bg-blue-600 text-white shadow-xs'
                                            : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                    }`}
                                >
                                    {selectedPlan === 'enterprise' ? 'Formule sélectionnée' : 'Sélectionner Entreprise'}
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Notice if Enterprise is selected */}
                    {isEnterprise && (
                        <div className="mb-6 p-4 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-950 text-xs sm:text-sm flex items-start gap-3 max-w-3xl mx-auto animate-in fade-in duration-200">
                            <HelpCircle className="w-5 h-5 text-indigo-600 shrink-0 mt-0.5" />
                            <div>
                                <strong className="font-bold">Information Offre Entreprise :</strong>
                                <p className="mt-0.5 text-indigo-800">
                                    En choisissant l'offre Entreprise, votre espace organisationnel sera créé immédiatement. Notre équipe commerciale vous contactera dans les plus brefs délais pour adapter vos volumes et configurer vos accès sur mesure.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Submit Bar */}
                    <div className="flex flex-col sm:flex-row items-center justify-between gap-4 p-5 bg-white rounded-2xl border border-slate-200 shadow-sm max-w-3xl mx-auto">
                        <Link
                            href="/register/admin"
                            className="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-600 hover:text-slate-900 transition"
                        >
                            <ArrowLeft className="w-4 h-4" />
                            <span>Modifier les informations administrateur</span>
                        </Link>

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full sm:w-auto inline-flex items-center justify-center gap-2 font-bold rounded-full px-8 py-3.5 text-sm text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-lg shadow-blue-500/25 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed transition-all cursor-pointer"
                        >
                            {processing ? (
                                <span>Initialisation de votre espace...</span>
                            ) : (
                                <>
                                    <span>
                                        {isEnterprise
                                            ? "Créer mon espace & Demander l'offre Entreprise"
                                            : "Démarrer mes 14 jours d'essai gratuit"}
                                    </span>
                                    <ArrowRight className="w-4 h-4" />
                                </>
                            )}
                        </button>
                    </div>

                    {/* Security Footnote */}
                    <div className="text-center pt-4 text-xs text-slate-500 flex flex-wrap items-center justify-center gap-4">
                        <span className="flex items-center gap-1.5">
                            <ShieldCheck className="w-4 h-4 text-emerald-600" />
                            14 jours d'essai gratuit
                        </span>
                        <span>•</span>
                        <span>Aucune carte bancaire requise</span>
                        <span>•</span>
                        <span>Annulation en un clic à tout moment</span>
                    </div>
                </form>
            </div>
        </div>
    );
}
