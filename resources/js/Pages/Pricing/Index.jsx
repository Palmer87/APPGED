import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Check,
    X,
    Sparkles,
    Shield,
    Crown,
    ArrowRight,
    HelpCircle,
    Zap,
    Users,
    HardDrive,
    Building2,
    FileStack,
    Search,
    ChevronDown,
    CheckCircle2
} from 'lucide-react';

export default function PricingIndex({ plans = [] }) {
    const [billingCycle, setBillingCycle] = useState('monthly'); // 'monthly' | 'annual'

    const formatFCFA = (amount) => {
        if (!amount) return 'Sur devis';
        return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
    };

    // Feature matrix comparison
    const featuresList = [
        { name: 'Utilisateurs inclus', essential: '5', professional: '20', enterprise: 'Sur mesure' },
        { name: 'Espace de stockage', essential: '20 Go', professional: '100 Go', enterprise: 'Personnalisé' },
        { name: 'Directions administratives', essential: '3', professional: '10', enterprise: 'Illimité' },
        { name: 'Types documentaires', essential: '15', professional: '50', enterprise: 'Illimité' },
        { name: 'Pages OCR / mois', essential: '100 pages', professional: '1 000 pages', enterprise: 'Sur mesure' },
        { name: 'Recherche avancée & filtres', essential: true, professional: true, enterprise: true },
        { name: 'Métadonnées personnalisées', essential: true, professional: true, enterprise: true },
        { name: 'Gestion des versions', essential: true, professional: true, enterprise: true },
        { name: 'Partage interne & groupes', essential: true, professional: true, enterprise: true },
        { name: 'Workflows de validation', essential: false, professional: true, enterprise: true },
        { name: 'Journal d\'audit & traçabilité', essential: 'Basique', professional: 'Avancé', enterprise: 'Complet & export' },
        { name: 'Accès API REST', essential: false, professional: true, enterprise: true },
        { name: 'Support client', essential: 'Email', professional: 'Prioritaire', enterprise: 'Dédié 24/7' },
        { name: 'Engagement de service (SLA)', essential: false, professional: false, enterprise: '99.9% garanti' },
        { name: 'Aide à la migration documentaire', essential: false, professional: false, enterprise: true },
        { name: 'Intégrations sur mesure', essential: false, professional: false, enterprise: true },
    ];

    return (
        <div className="min-h-screen bg-[#0B132B] text-slate-100 font-sans selection:bg-blue-600 selection:text-white">
            <Head title="Tarifs & Offres SaaS — GEDAPP" />

            {/* Navigation Header */}
            <header className="border-b border-slate-800/80 bg-[#0B132B]/90 backdrop-blur-md sticky top-0 z-40">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-3 group">
                        <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 shrink-0 group-hover:scale-105 transition-transform">
                            <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z" fill="currentColor" fillOpacity="0.2"/>
                                <rect x="10" y="11" width="4" height="4" rx="1" fill="white" stroke="none" />
                                <path d="M11 11V9.5a1 1 0 0 1 2 0V11" stroke="white" strokeWidth="1.5" strokeLinecap="round"/>
                            </svg>
                        </div>
                        <div className="flex flex-col">
                            <span className="text-xl font-black tracking-tight text-white leading-none">
                                GED<span className="text-blue-500">APP</span>
                            </span>
                            <span className="text-[10px] text-slate-400 font-medium tracking-tight mt-1 leading-none">
                                Vos documents, plus loin
                            </span>
                        </div>
                    </Link>

                    <div className="flex items-center gap-4">
                        <Link
                            href="/login"
                            className="text-xs sm:text-sm font-semibold text-slate-300 hover:text-white transition px-3 py-2"
                        >
                            Connexion
                        </Link>
                        <Link
                            href="/login"
                            className="text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-500 text-white px-4 py-2.5 rounded-xl shadow-md shadow-blue-600/30 transition duration-150"
                        >
                            Essai gratuit 14 jours
                        </Link>
                    </div>
                </div>
            </header>

            {/* Hero Section */}
            <section className="pt-16 pb-12 text-center px-4 max-w-4xl mx-auto">
                <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full   text-blue-400 text-xs font-semibold mb-6">
                    <span>14 jours gratuits • Sans engagement • Sans carte bancaire</span>
                </div>

                <h1 className="text-3xl sm:text-5xl font-black tracking-tight text-white leading-tight">
                    Une GED adaptée à votre entreprise.
                </h1>
                <p className="mt-4 text-base sm:text-lg text-slate-400 max-w-2xl mx-auto leading-relaxed">
                    Centralisez, sécurisez et retrouvez vos documents plus simplement. Choisissez l'offre parfaitement calibrée pour votre organisation.
                </p>

                {/* Billing Cycle Toggle */}
                <div className="mt-10 flex items-center justify-center">
                    <div className="inline-flex p-1 rounded-2xl bg-slate-900 border border-slate-800 shadow-inner">
                        <button
                            type="button"
                            onClick={() => setBillingCycle('monthly')}
                            className={`px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all ${
                                billingCycle === 'monthly'
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25'
                                    : 'text-slate-400 hover:text-white'
                            }`}
                        >
                            Facturation mensuelle
                        </button>
                        <button
                            type="button"
                            onClick={() => setBillingCycle('annual')}
                            className={`px-6 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all flex items-center gap-2 ${
                                billingCycle === 'annual'
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-600/25'
                                    : 'text-slate-400 hover:text-white'
                            }`}
                        >
                            <span>Facturation annuelle</span>
                            <span className="text-[10px] px-2 py-0.5 rounded-full font-extrabold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                Économisez 2 mois
                            </span>
                        </button>
                    </div>
                </div>
            </section>

            {/* Pricing Cards */}
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                    
                    {/* 1. Plan Essentiel */}
                    <div className="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 flex flex-col justify-between hover:border-slate-700 transition relative">
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-xl font-bold text-white">Essentiel</h3>
                                <span className="text-[11px] font-semibold text-slate-400 px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700">
                                    Petites équipes
                                </span>
                            </div>
                            <p className="text-xs text-slate-400 mb-6 leading-relaxed">
                                Idéal pour structurer et centraliser vos documents au quotidien.
                            </p>

                            {/* Price */}
                            <div className="mb-6 pb-6 border-b border-slate-800">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-3xl sm:text-4xl font-black text-white">
                                        {billingCycle === 'monthly' ? '19 000' : '190 000'} FCFA
                                    </span>
                                    <span className="text-xs text-slate-400 font-medium">
                                        / {billingCycle === 'monthly' ? 'mois' : 'an'}
                                    </span>
                                </div>
                                {billingCycle === 'annual' && (
                                    <p className="text-[11px] text-emerald-400 font-semibold mt-1">
                                        190 000 FCFA/an au lieu de 228 000 FCFA/an
                                    </p>
                                )}
                            </div>

                            {/* Key Limits & Features */}
                            <ul className="space-y-3.5 text-xs text-slate-300">
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-500 shrink-0" />
                                    <span><strong>5</strong> utilisateurs inclus</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-500 shrink-0" />
                                    <span><strong>20 Go</strong> de stockage sécurisé</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-500 shrink-0" />
                                    <span><strong>3</strong> directions d'entreprise</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-500 shrink-0" />
                                    <span><strong>15</strong> types documentaires</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-500 shrink-0" />
                                    <span><strong>100 pages OCR</strong> / mois</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-500 shrink-0" />
                                    <span>Recherche plein texte & métadonnées</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-500 shrink-0" />
                                    <span>Support réactif par email</span>
                                </li>
                            </ul>
                        </div>

                        <div className="mt-8 pt-6 border-t border-slate-800">
                            <Link
                                href="/inscription"
                                className="w-full py-3 px-4 rounded-xl text-xs font-bold text-center block bg-slate-800 hover:bg-slate-700 text-white transition border border-slate-700"
                            >
                                Commencer gratuitement (14 jours)
                            </Link>
                        </div>
                    </div>

                    {/* 2. Plan Professionnel (Highlighted) */}
                    <div className="rounded-3xl bg-slate-900 border-2 border-blue-500 shadow-2xl shadow-blue-500/10 p-8 flex flex-col justify-between relative transform lg:-translate-y-2">
                        {/* Popular Badge */}
                        <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-blue-600 text-white text-[11px] font-extrabold px-3.5 py-1 rounded-full uppercase tracking-wider shadow-md shadow-blue-600/30 flex items-center gap-1.5">
                            <Crown className="w-3.5 h-3.5 text-amber-300" />
                            <span>⭐ Recommandé • Le plus choisi</span>
                        </div>

                        <div>
                            <div className="flex items-center justify-between mb-4 mt-2">
                                <h3 className="text-xl font-bold text-white">Professionnel</h3>
                                <span className="text-[11px] font-semibold text-blue-400 px-2.5 py-1 rounded-lg bg-blue-500/10 border border-blue-500/20">
                                    PME en croissance
                                </span>
                            </div>
                            <p className="text-xs text-slate-300 mb-6 leading-relaxed">
                                La suite documentaire complète avec validation collaborative et API.
                            </p>

                            {/* Price */}
                            <div className="mb-6 pb-6 border-b border-blue-900/50">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-3xl sm:text-4xl font-black text-white">
                                        {billingCycle === 'monthly' ? '39 000' : '390 000'} FCFA
                                    </span>
                                    <span className="text-xs text-blue-300 font-medium">
                                        / {billingCycle === 'monthly' ? 'mois' : 'an'}
                                    </span>
                                </div>
                                {billingCycle === 'annual' && (
                                    <p className="text-[11px] text-emerald-400 font-semibold mt-1">
                                        390 000 FCFA/an au lieu de 468 000 FCFA/an
                                    </p>
                                )}
                            </div>

                            {/* Key Limits & Features */}
                            <ul className="space-y-3.5 text-xs text-slate-200">
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span><strong>20</strong> utilisateurs inclus</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span><strong>100 Go</strong> de stockage haute performance</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span><strong>10</strong> directions d'entreprise</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span><strong>50</strong> types documentaires</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span><strong>1 000 pages OCR</strong> / mois</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span><strong>Workflows & Circuits de validation</strong></span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span><strong>Journal d'audit avancé</strong> & conformité</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span><strong>API REST</strong> pour intégrations</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-blue-400 shrink-0 font-bold" />
                                    <span>Support prioritaire</span>
                                </li>
                            </ul>
                        </div>

                        <div className="mt-8 pt-6 border-t border-blue-900/50">
                            <Link
                                href="/inscription"
                                className="w-full py-3 px-4 rounded-xl text-xs font-bold text-center block bg-blue-600 hover:bg-blue-500 text-white shadow-lg shadow-blue-600/30 transition duration-150"
                            >
                                Commencer gratuitement (14 jours)
                            </Link>
                        </div>
                    </div>

                    {/* 3. Plan Entreprise */}
                    <div className="rounded-3xl bg-slate-900/60 border border-slate-800 p-8 flex flex-col justify-between hover:border-slate-700 transition relative">
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-xl font-bold text-white">Entreprise</h3>
                                <span className="text-[11px] font-semibold text-purple-400 px-2.5 py-1 rounded-lg bg-purple-500/10 border border-purple-500/20">
                                    Grand compte
                                </span>
                            </div>
                            <p className="text-xs text-slate-400 mb-6 leading-relaxed">
                                Sécurité bancaire, volumes sur mesure, SLA garanti et accompagnement dédié.
                            </p>

                            {/* Price */}
                            <div className="mb-6 pb-6 border-b border-slate-800">
                                <div className="flex items-baseline gap-2">
                                    <span className="text-3xl sm:text-4xl font-black text-white">
                                        Sur devis
                                    </span>
                                </div>
                                <p className="text-[11px] text-slate-400 mt-1">
                                    Contrat sur mesure & facturation adaptée
                                </p>
                            </div>

                            {/* Key Limits & Features */}
                            <ul className="space-y-3.5 text-xs text-slate-300">
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-purple-400 shrink-0" />
                                    <span>Utilisateurs sur mesure & illimités</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-purple-400 shrink-0" />
                                    <span>Stockage dédié sur mesure (To+)</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-purple-400 shrink-0" />
                                    <span>Directions & Types documentaires illimités</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-purple-400 shrink-0" />
                                    <span>Volumes OCR personnalisés</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-purple-400 shrink-0" />
                                    <span><strong>SLA 99.9%</strong> garanti avec contrat</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-purple-400 shrink-0" />
                                    <span>Migration documentaire assistée</span>
                                </li>
                                <li className="flex items-center gap-3">
                                    <Check className="w-4 h-4 text-purple-400 shrink-0" />
                                    <span>Account Manager dédié & support 24/7</span>
                                </li>
                            </ul>
                        </div>

                        <div className="mt-8 pt-6 border-t border-slate-800">
                            <Link
                                href="/enterprise"
                                className="w-full py-3 px-4 rounded-xl text-xs font-bold text-center block bg-slate-800 hover:bg-slate-700 text-white transition border border-slate-700"
                            >
                                Demander une démonstration
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            {/* Comparison Matrix Table */}
            <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-24">
                <div className="text-center max-w-3xl mx-auto mb-12">
                    <h2 className="text-2xl sm:text-3xl font-black text-white">
                        Tableau comparatif détaillé
                    </h2>
                    <p className="mt-2 text-sm text-slate-400">
                        Visualisez toutes les capacités incluses dans chaque formule.
                    </p>
                </div>

                <div className="rounded-3xl border border-slate-800 bg-slate-900/40 overflow-hidden shadow-xl">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left border-collapse">
                            <thead>
                                <tr className="border-b border-slate-800 bg-slate-900/90 text-xs font-bold uppercase tracking-wider text-slate-400">
                                    <th className="py-4 px-6 w-2/5">Fonctionnalité</th>
                                    <th className="py-4 px-6 w-1/5 text-center">Essentiel</th>
                                    <th className="py-4 px-6 w-1/5 text-center text-blue-400">Professionnel</th>
                                    <th className="py-4 px-6 w-1/5 text-center text-purple-400">Entreprise</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60 text-xs sm:text-sm">
                                {featuresList.map((f, idx) => (
                                    <tr key={idx} className="hover:bg-slate-800/30 transition">
                                        <td className="py-3.5 px-6 font-medium text-slate-200">
                                            {f.name}
                                        </td>
                                        <td className="py-3.5 px-6 text-center text-slate-400">
                                            {typeof f.essential === 'boolean' ? (
                                                f.essential ? (
                                                    <Check className="w-4 h-4 text-emerald-400 mx-auto" />
                                                ) : (
                                                    <X className="w-4 h-4 text-slate-600 mx-auto" />
                                                )
                                            ) : (
                                                <span className="font-semibold text-slate-300">{f.essential}</span>
                                            )}
                                        </td>
                                        <td className="py-3.5 px-6 text-center text-slate-300 bg-blue-500/5">
                                            {typeof f.professional === 'boolean' ? (
                                                f.professional ? (
                                                    <Check className="w-4 h-4 text-blue-400 mx-auto" />
                                                ) : (
                                                    <X className="w-4 h-4 text-slate-600 mx-auto" />
                                                )
                                            ) : (
                                                <span className="font-bold text-white">{f.professional}</span>
                                            )}
                                        </td>
                                        <td className="py-3.5 px-6 text-center text-slate-300">
                                            {typeof f.enterprise === 'boolean' ? (
                                                f.enterprise ? (
                                                    <Check className="w-4 h-4 text-purple-400 mx-auto" />
                                                ) : (
                                                    <X className="w-4 h-4 text-slate-600 mx-auto" />
                                                )
                                            ) : (
                                                <span className="font-semibold text-purple-300">{f.enterprise}</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            {/* Trial Assurance Banner */}
            <section className="bg-slate-900 border-t border-slate-800 py-16 px-4">
                <div className="max-w-4xl mx-auto text-center">
                    <h3 className="text-2xl font-bold text-white mb-3">
                        Prêt à moderniser votre gestion de documents ?
                    </h3>
                    <p className="text-slate-400 text-sm max-w-xl mx-auto mb-8">
                        Rejoignez les entreprises qui font confiance à GEDAPP pour stocker, retrouver et valider leurs fichiers en toute sécurité.
                    </p>
                    <Link
                        href="/login"
                        className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-500 text-white font-bold px-8 py-3.5 rounded-xl shadow-lg shadow-blue-600/30 transition duration-150 text-sm"
                    >
                        <span>Créer un compte et tester gratuitement</span>
                        <ArrowRight className="w-4 h-4" />
                    </Link>
                </div>
            </section>
        </div>
    );
}
