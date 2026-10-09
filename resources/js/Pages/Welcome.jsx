import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Sparkles,
    ShieldCheck,
    Check,
    FileText,
    Layers,
    Lock,
    Search,
    ChevronDown
} from 'lucide-react';

import LandingHeader from '@/Components/Landing/LandingHeader';
import HeroVisual from '@/Components/Landing/HeroVisual';
import ChaosToOrder from '@/Components/Landing/ChaosToOrder';
import CollaborationShowcase from '@/Components/Landing/CollaborationShowcase';
import FeatureGrid from '@/Components/Landing/FeatureGrid';
import SearchDemo from '@/Components/Landing/SearchDemo';
import SecurityFlow from '@/Components/Landing/SecurityFlow';
import EnterpriseSolutions from '@/Components/Landing/EnterpriseSolutions';
import ProductShowcase from '@/Components/Landing/ProductShowcase';
import PricingPreview from '@/Components/Landing/PricingPreview';
import LandingFooter from '@/Components/Landing/LandingFooter';
import Reveal, { SectionHeading, IllustrativeBadge } from '@/Components/Landing/Reveal';

export default function Welcome({ plans = [], stats = {} }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    return (
        <div className="min-h-screen bg-white text-slate-900 font-sans selection:bg-blue-600 selection:text-white antialiased">
            <Head>
                <title>APPGED — Vos documents. Votre organisation. Votre maîtrise.</title>
                <meta
                    name="description"
                    content="Centralisez, organisez et sécurisez vos documents dans une plateforme intelligente conçue pour les entreprises et les organisations."
                />
                <meta property="og:title" content="APPGED — Gestion Électronique de Documents B2B" />
                <meta
                    property="og:description"
                    content="Plateforme SaaS complète de GED : structure Direction/Service, métadonnées, recherche avancée, OCR, versions et traçabilité."
                />
                <meta property="og:type" content="website" />
            </Head>

            {/* Navigation Header */}
            <LandingHeader user={user} />

            <main id="contenu">
                {/* =========================================================================
                    1. HERO SECTION
                    ========================================================================= */}
                <section className="relative pt-32 pb-20 md:pt-40 md:pb-32 overflow-hidden bg-[#0B1220] text-white">
                    {/* Background Grid */}
                    <div className="absolute inset-0 lp-grid-bg opacity-30 pointer-events-none" aria-hidden="true" />

                    <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">

                            {/* Hero Text */}
                            <div className="lg:col-span-6 text-left">

                                {/* Main Title */}
                                <h1 className="lp-enter text-4xl sm:text-5xl lg:text-[3.5rem] font-black tracking-tight leading-[1.08] text-white mb-6" style={{ '--lp-delay': '200ms' }}>
                                    Vos documents.<br />
                                    Votre organisation.<br />
                                    <span className="text-cyan-400">Votre maîtrise.</span>
                                </h1>

                                {/* Subtitle */}
                                <p className="lp-enter text-base sm:text-lg text-slate-300 leading-relaxed max-w-xl mb-8" style={{ '--lp-delay': '350ms' }}>
                                    Centralisez, organisez et sécurisez vos documents dans une plateforme intelligente conçue pour les entreprises et les organisations.
                                </p>

                                {/* CTAs */}
                                <div className="lp-enter flex flex-wrap items-center gap-4 mb-8" style={{ '--lp-delay': '500ms' }}>
                                    <Link
                                        href={user ? "/dashboard" : "/inscription"}
                                        className="inline-flex items-center justify-center gap-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold px-7 py-3.5 rounded-full transition-all duration-200 text-sm sm:text-base group"
                                    >
                                        <span>{user ? "Accéder à mon espace" : "Commencer gratuitement"}</span>
                                        <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                    </Link>

                                    <a
                                        href="#demonstration"
                                        className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-full bg-white/10 hover:bg-white/15 text-slate-200 hover:text-white font-semibold border border-white/15 backdrop-blur-md transition-all duration-200 text-sm sm:text-base"
                                    >
                                        <span>Découvrir APPGED</span>
                                    </a>
                                </div>

                                {/* Microcopy reassurance */}
                                <div className="lp-enter flex flex-wrap items-center gap-y-2 gap-x-6 text-xs font-medium text-slate-400" style={{ '--lp-delay': '650ms' }}>
                                    <div className="flex items-center gap-1.5">
                                        <Check className="w-4 h-4 text-cyan-400 stroke-[3]" />
                                        <span>Une gestion documentaire pensée pour votre organisation</span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <Check className="w-4 h-4 text-cyan-400 stroke-[3]" />
                                        <span>Essai 14 jours sans engagement</span>
                                    </div>
                                </div>

                                {/* Social proof with client avatars & satisfaction */}
                                <div className="lp-enter mt-8 pt-6 border-t border-white/10 flex items-center gap-3.5" style={{ '--lp-delay': '750ms' }}>
                                    <div className="flex -space-x-2">
                                        <img src="/images/landing/avatar-aminata.jpg" alt="Aminata D. - Directrice Générale" className="w-8 h-8 rounded-full ring-2 ring-[#0B1220] object-cover" width="32" height="32" />
                                        <img src="/images/landing/avatar-jean.jpg" alt="Jean-Marc M. - Resp. Administratif" className="w-8 h-8 rounded-full ring-2 ring-[#0B1220] object-cover" width="32" height="32" />
                                        <img src="/images/landing/avatar-sophie.jpg" alt="Sophie B. - DRH" className="w-8 h-8 rounded-full ring-2 ring-[#0B1220] object-cover" width="32" height="32" />
                                    </div>
                                    <div className="text-xs">
                                        <div className="flex items-center gap-1.5 text-amber-400 font-semibold">
                                            <span>★★★★★</span>
                                            <span className="text-white font-bold text-[11px]">4.9 / 5</span>
                                        </div>
                                        <p className="text-[11px] text-slate-400">Adopté par plus de 120 directions & organisations</p>
                                    </div>
                                </div>
                            </div>

                            {/* Hero Visual Mockup */}
                            <div className="lg:col-span-6 relative">
                                <HeroVisual />
                            </div>

                        </div>
                    </div>

                    {/* Subtle scroll cue */}
                    <div className="absolute bottom-4 left-1/2 -translate-x-1/2 hidden md:flex items-center gap-1 text-[11px] text-slate-500 tracking-wider uppercase font-semibold">
                        <span>Explorer la plateforme</span>
                        <ChevronDown className="w-3.5 h-3.5 animate-bounce" />
                    </div>
                </section>

                {/* =========================================================================
                    2. CHAOS DEVIENT MAÎTRISE
                    ========================================================================= */}
                <section id="transformation" className="py-24 bg-slate-50/70 border-b border-slate-100">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            title="Passez du désordre documentaire à une organisation maîtrisée."
                            lead="Fini les fichiers disséminés dans des boîtes mail, disques durs ou dossiers réseau anarchiques. APPGED unifie l’ensemble de votre patrimoine documentaire dans un cadre structuré et gouverné."
                            align="center"
                            tone="light"
                        />

                        <div className="mt-16">
                            <ChaosToOrder />
                        </div>
                    </div>
                </section>

                {/* =========================================================================
                    COLLABORATION & TRAVAIL EN ENTREPRISE
                    ========================================================================= */}
                <section id="collaboration" className="py-24 bg-white border-b border-slate-100 overflow-hidden">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <CollaborationShowcase />
                    </div>
                </section>

                {/* =========================================================================
                    3. GRILLE DE FONCTIONNALITÉS
                    ========================================================================= */}
                <section id="fonctionnalites" className="py-24 bg-white border-b border-slate-100">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            title="Toutes les fonctions essentielles réunies dans un SaaS moderne."
                            lead="Des outils professionnels conçus pour simplifier la vie de vos équipes tout en garantissant une rigueur irréprochable."
                            align="center"
                            tone="light"
                        />

                        <div className="mt-16">
                            <FeatureGrid />
                        </div>
                    </div>
                </section>

                {/* =========================================================================
                    4. RECHERCHE DOCUMENTAIRE
                    ========================================================================= */}
                <section id="recherche" className="py-24 bg-slate-50/70 border-b border-slate-100">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            title="Une recherche qui a du sens."
                            lead="Retrouvez n’importe quelle pièce en quelques secondes en combinant arborescence métier, type documentaire, métadonnées et mots-clés."
                            align="center"
                            tone="light"
                        />

                        <div className="mt-14">
                            <SearchDemo />
                        </div>
                    </div>
                </section>

                {/* =========================================================================
                    5. SÉCURITÉ & ISOLATION
                    ========================================================================= */}
                <section id="securite" className="py-24 bg-[#0B1220] text-white border-b border-slate-800">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            title="La sécurité documentaire intégrée à votre organisation."
                            lead="Cloisonnement strict par organisation, authentification sécurisée, vérification granulaire des permissions et traçabilité systématique des actions."
                            align="center"
                            tone="dark"
                        />

                        <div className="mt-16">
                            <SecurityFlow />
                        </div>
                    </div>
                </section>

                {/* =========================================================================
                    6. SECTIONS ENTREPRISE & SAAS
                    ========================================================================= */}
                <EnterpriseSolutions />

                {/* =========================================================================
                    7. DÉMONSTRATION DU PRODUIT
                    ========================================================================= */}
                <section id="demonstration" className="py-24 bg-slate-50/70 border-b border-slate-100">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            title="Un espace de travail clair, fluide et intuitif."
                            lead="Explorez les différents modules d’APPGED : tableau de bord, consultation de documents, recherche multi-critères, métadonnées et gestion des versions."
                            align="center"
                            tone="light"
                        />

                        <div className="mt-14">
                            <ProductShowcase />
                        </div>
                    </div>
                </section>

                {/* =========================================================================
                    8. TARIFS & OFFRES SAAS
                    ========================================================================= */}
                <section id="tarifs" className="py-24 bg-white border-b border-slate-100">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading
                            title="Des formules claires adaptées à chaque étape de votre croissance."
                            lead="Démarrez avec un essai gratuit de 14 jours sans carte bancaire, puis choisissez la formule qui correspond au volume de votre organisation."
                            align="center"
                            tone="light"
                        />

                        <div className="mt-14">
                            <PricingPreview plans={plans} user={user} />
                        </div>
                    </div>
                </section>

                {/* =========================================================================
                    9. CALL TO ACTION FINAL
                    ========================================================================= */}
                <section className="py-20 bg-slate-50">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <Reveal className="relative rounded-3xl overflow-hidden bg-[#0B1220] px-8 py-16 sm:px-14 sm:py-20 text-white border border-white/10">
                            <div className="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-10">
                                <div className="max-w-2xl text-center lg:text-left">
                                    <h2 className="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight mb-4 text-white">
                                        Donnez à vos documents la place qu’ils méritent.
                                    </h2>
                                    <p className="text-slate-300 text-sm sm:text-base leading-relaxed">
                                        Rejoignez les organisations qui simplifient leur gestion documentaire au quotidien avec APPGED.
                                    </p>
                                </div>

                                <div className="flex flex-col sm:flex-row items-center gap-3 shrink-0">
                                    <Link
                                        href={user ? "/dashboard" : "/inscription"}
                                        className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold px-7 py-3.5 rounded-full transition duration-200 text-sm sm:text-base group"
                                    >
                                        <span>{user ? "Accéder à mon espace" : "Commencer gratuitement"}</span>
                                        <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                    </Link>

                                    <Link
                                        href="/tarifs"
                                        className="inline-flex items-center gap-2 bg-white/10 hover:bg-white/15 text-white font-semibold px-6 py-3.5 rounded-full border border-white/15 transition duration-200 text-sm sm:text-base"
                                    >
                                        <span>Découvrir les offres</span>
                                    </Link>
                                </div>
                            </div>
                        </Reveal>
                    </div>
                </section>
            </main>

            {/* Footer */}
            <LandingFooter />
        </div>
    );
}
