import React, { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Folder,
    Search,
    ShieldCheck,
    Users,
    TrendingUp,
    CheckCircle2,
    Play,
    ArrowRight,
    Star,
    Menu,
    X,
    ChevronDown,
    Building2,
    FileText,
    Shield,
    Heart,
    Mail,
    Phone,
    FileSpreadsheet,
    FileCheck,
    Send,
    ExternalLink,
    Lock,
    Sparkles,
    Check
} from 'lucide-react';

export default function Welcome() {
    const { auth } = usePage().props;
    const user = auth?.user;

    // Billing toggle: monthly or annually (-20%)
    const [billingPeriod, setBillingPeriod] = useState('monthly');
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [resourcesOpen, setResourcesOpen] = useState(false);
    const [videoModalOpen, setVideoModalOpen] = useState(false);
    const [newsletterEmail, setNewsletterEmail] = useState('');
    const [newsletterSubmitted, setNewsletterSubmitted] = useState(false);
    const [activeTestimonialTab, setActiveTestimonialTab] = useState(0);

    const handleNewsletterSubmit = (e) => {
        e.preventDefault();
        if (newsletterEmail.trim()) {
            setNewsletterSubmitted(true);
            setTimeout(() => {
                setNewsletterSubmitted(false);
                setNewsletterEmail('');
            }, 4000);
        }
    };

    return (
        <div className="min-h-screen bg-white text-slate-900 font-sans selection:bg-blue-600 selection:text-white">
            <Head title="GEDAPP - Vos documents. Plus loin." />

            {/* Top Navigation Bar */}
            <header className="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-100">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24 h-20 flex items-center justify-between">
                    {/* Brand Logo */}
                    <div className="flex items-center gap-3">
                        <Link href="/" className="flex items-center gap-2.5 group">
                            <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform duration-200">
                                <svg className="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M7 4V20C7 20.5523 7.44772 21 8 21H18C18.5523 21 19 20.5523 19 20V8.5L14.5 4H8C7.44772 4 7 4.44772 7 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    <path d="M14 4V9H19" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    <path d="M4 8V17" stroke="currentColor" strokeWidth="2" strokeLinecap="round" opacity="0.6"/>
                                    <path d="M10 13H15" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                                    <path d="M10 17H13" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                                </svg>
                            </div>
                            <div className="flex flex-col">
                                <span className="text-xl font-bold tracking-tight text-slate-950 font-sans leading-none">
                                    GED<span className="text-blue-600">APP</span>
                                </span>
                                <span className="text-[11px] text-slate-500 font-medium tracking-tight mt-1 leading-none">
                                    Vos documents. Plus loin.
                                </span>
                            </div>
                        </Link>
                    </div>

                    {/* Desktop Navigation Links */}
                    <nav className="hidden lg:flex items-center gap-8 text-[14px] font-medium text-slate-600">
                        <Link href="/" className="text-blue-600 font-semibold transition-colors">
                            Accueil
                        </Link>
                        <Link href="/fonctionnalites" className="hover:text-blue-600 transition-colors">
                            Fonctionnalités
                        </Link>
                        <Link href="/tarifs" className="hover:text-blue-600 transition-colors">
                            Tarifs
                        </Link>
                        <Link href="/enterprise" className="hover:text-blue-600 transition-colors">
                            Enterprise
                        </Link>
                        
                        {/* Ressources Dropdown */}
                        <div className="relative">
                            <button
                                type="button"
                                onClick={() => setResourcesOpen(!resourcesOpen)}
                                onBlur={() => setTimeout(() => setResourcesOpen(false), 200)}
                                className="flex items-center gap-1 hover:text-blue-600 transition-colors focus:outline-none"
                            >
                                <span>Ressources</span>
                                <ChevronDown className={`w-3.5 h-3.5 transition-transform duration-200 ${resourcesOpen ? 'rotate-180' : ''}`} />
                            </button>
                            {resourcesOpen && (
                                <div className="absolute top-full left-0 mt-3 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 z-50">
                                    <Link href="/fonctionnalites" className="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-blue-600 text-sm transition">
                                        <FileText className="w-4 h-4 text-blue-600" />
                                        <span>Fonctionnalités détaillées</span>
                                    </Link>
                                    <Link href="/enterprise" className="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-blue-600 text-sm transition">
                                        <Building2 className="w-4 h-4 text-purple-600" />
                                        <span>Offre Enterprise</span>
                                    </Link>
                                    <a href="#temoignages" className="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-50 text-slate-700 hover:text-blue-600 text-sm transition">
                                        <Star className="w-4 h-4 text-amber-500" />
                                        <span>Témoignages</span>
                                    </a>
                                </div>
                            )}
                        </div>

                        <Link href="/contact" className="hover:text-blue-600 transition-colors">
                            Contact
                        </Link>
                    </nav>

                    {/* Auth / Action CTA */}
                    <div className="hidden sm:flex items-center gap-3">
                        {user ? (
                            <Link
                                href="/dashboard"
                                className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2.5 rounded-full shadow-sm hover:shadow transition"
                            >
                                <span>Tableau de bord</span>
                                <ArrowRight className="w-4 h-4" />
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href="/login"
                                    className="text-sm font-semibold text-slate-700 hover:text-blue-600 transition px-3.5 py-2"
                                >
                                    Se connecter
                                </Link>
                                <Link
                                    href="/inscription"
                                    className="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-5 py-2.5 rounded-full shadow-sm hover:shadow transition group"
                                >
                                    <span>Commencer gratuitement</span>
                                    <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-0.5" />
                                </Link>
                            </>
                        )}
                    </div>

                    {/* Mobile Hamburger Button */}
                    <div className="lg:hidden flex items-center">
                        <button
                            type="button"
                            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                            className="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition"
                            aria-label="Menu"
                        >
                            {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
                        </button>
                    </div>
                </div>

                {/* Mobile Menu Drawer */}
                {mobileMenuOpen && (
                    <div className="lg:hidden border-t border-slate-100 bg-white px-4 pt-3 pb-6 space-y-3 shadow-xl">
                        <Link
                            href="/"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 text-base font-medium text-blue-600 bg-blue-50/60 rounded-xl"
                        >
                            Accueil
                        </Link>
                        <Link
                            href="/fonctionnalites"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50 rounded-xl"
                        >
                            Fonctionnalités
                        </Link>
                        <Link
                            href="/tarifs"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50 rounded-xl"
                        >
                            Tarifs
                        </Link>
                        <Link
                            href="/enterprise"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50 rounded-xl"
                        >
                            Offre Enterprise
                        </Link>
                        <Link
                            href="/contact"
                            onClick={() => setMobileMenuOpen(false)}
                            className="block px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50 rounded-xl"
                        >
                            Contact
                        </Link>
                        <div className="pt-4 border-t border-slate-100 flex flex-col gap-2">
                            {user ? (
                                <Link
                                    href="/dashboard"
                                    className="w-full text-center bg-blue-600 text-white font-semibold py-3 rounded-xl shadow-sm"
                                >
                                    Accéder au tableau de bord
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href="/login"
                                        className="w-full text-center py-2.5 font-semibold text-slate-700 hover:bg-slate-50 rounded-xl"
                                    >
                                        Se connecter
                                    </Link>
                                    <Link
                                        href="/inscription"
                                        className="w-full text-center bg-blue-600 text-white font-semibold py-3 rounded-xl shadow-sm"
                                    >
                                        Commencer gratuitement →
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                )}
            </header>

            {/* HERO SECTION */}
            <section className="relative pt-10 pb-20 md:pt-16 md:pb-28 overflow-hidden bg-gradient-to-b from-blue-50/30 via-white to-white">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                        
                        {/* Hero Left Content */}
                        <div className="lg:col-span-6 xl:col-span-5 text-left">
                            {/* Pill Badge */}
                            <div className="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold tracking-wider text-blue-700 bg-blue-50 border border-blue-200/80 uppercase mb-6">
                                <Sparkles className="w-3.5 h-3.5 text-blue-600" />
                                <span>La GED nouvelle génération</span>
                            </div>

                            {/* Main Heading */}
                            <h1 className="text-5xl sm:text-6xl font-black text-slate-950 tracking-tight leading-[1.08] mb-6">
                                Simplifiez.<br />
                                Sécurisez.<br />
                                <span className="text-blue-600">Avancez.</span>
                            </h1>

                            {/* Subtitle */}
                            <p className="text-base sm:text-lg text-slate-600 leading-relaxed mb-8 max-w-xl 2xl:max-w-2xl">
                                GEDAPP est la solution de gestion électronique de documents pensée pour les entreprises modernes. Centralisez, organisez, recherchez et partagez vos documents en toute sécurité.
                            </p>

                            {/* CTAs */}
                            <div className="flex flex-wrap items-center gap-4 mb-8">
                                <Link
                                    href={user ? "/dashboard" : "/inscription"}
                                    className="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-7 py-3.5 rounded-full shadow-lg shadow-blue-500/25 hover:shadow-xl hover:shadow-blue-500/30 transition-all duration-200 group text-sm sm:text-base"
                                >
                                    <span>Commencer gratuitement</span>
                                    <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                </Link>

                                <button
                                    type="button"
                                    onClick={() => setVideoModalOpen(true)}
                                    className="inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-full bg-white hover:bg-slate-50 text-slate-800 font-semibold border border-slate-200/90 shadow-2xs transition-all duration-200 text-sm sm:text-base group"
                                >
                                    <div className="w-6 h-6 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <Play className="w-3 h-3 fill-current ml-0.5" />
                                    </div>
                                    <span>Voir la vidéo</span>
                                </button>
                            </div>

                            {/* Trust badges row */}
                            <div className="flex flex-wrap items-center gap-y-2 gap-x-6 text-xs sm:text-sm font-medium text-slate-600">
                                <div className="flex items-center gap-1.5">
                                    <div className="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0">
                                        <Check className="w-2.5 h-2.5 stroke-[3]" />
                                    </div>
                                    <span>Essai gratuit 14 jours</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <div className="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0">
                                        <Check className="w-2.5 h-2.5 stroke-[3]" />
                                    </div>
                                    <span>Sans carte bancaire</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <div className="w-4 h-4 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0">
                                        <Check className="w-2.5 h-2.5 stroke-[3]" />
                                    </div>
                                    <span>Mise en place rapide</span>
                                </div>
                            </div>
                        </div>

                        {/* Hero Right Mockup with Hand-Drawn Annotation */}
                        <div className="lg:col-span-6 xl:col-span-7 relative">
                            {/* Hand-drawn style note and arrow */}
                            <div className="hidden md:flex items-center gap-2 absolute -top-11 right-6 z-20 pointer-events-none">
                                <span style={{ fontFamily: 'Caveat, cursive' }} className="text-xl md:text-2xl text-slate-600 font-semibold tracking-wide">
                                    Une interface moderne pour des équipes plus efficaces
                                </span>
                                <svg className="w-12 h-8 text-slate-500 transform rotate-12" viewBox="0 0 50 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 25C18 5 35 12 45 22" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeDasharray="3 3"/>
                                    <path d="M41 24L45 22L44 17" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"/>
                                </svg>
                            </div>

                            {/* Banner Image Container */}
                            <div className="relative group">
                                {/* Ambient glow backdrop */}
                                <div className="absolute -inset-1.5 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-3xl blur-xl opacity-20 group-hover:opacity-30 transition duration-500"></div>

                                {/* Main Image Card */}
                                <div className="relative rounded-2xl md:rounded-3xl overflow-hidden border border-slate-200/80 bg-white shadow-2xl shadow-blue-900/10">
                                    <img
                                        src="/images/landing/baniere.png"
                                        alt="GEDAPP - Solution complète de dématérialisation et gestion de documents"
                                        className="w-full h-auto object-cover transform group-hover:scale-[1.01] transition-transform duration-500"
                                        loading="eager"
                                    />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* 5 PILLARS / KEY BENEFITS ROW */}
            <section id="fonctionnalites" className="py-14 border-y border-slate-100 bg-white">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 lg:gap-6">
                        
                        {/* 1. Organisation */}
                        <div className="flex flex-col items-center text-center p-3 rounded-2xl hover:bg-slate-50 transition-colors">
                            <div className="w-12 h-12 rounded-2xl bg-blue-500 text-white flex items-center justify-center mb-4 shadow-md shadow-blue-500/20">
                                <Folder className="w-6 h-6" />
                            </div>
                            <h3 className="font-bold text-slate-900 text-base mb-1.5">
                                Organisation intelligente
                            </h3>
                            <p className="text-xs text-slate-500 leading-relaxed max-w-xs">
                                Classez vos documents par direction et type documentaire.
                            </p>
                        </div>

                        {/* 2. Recherche */}
                        <div className="flex flex-col items-center text-center p-3 rounded-2xl hover:bg-slate-50 transition-colors">
                            <div className="w-12 h-12 rounded-2xl bg-sky-500 text-white flex items-center justify-center mb-4 shadow-md shadow-sky-500/20">
                                <Search className="w-6 h-6" />
                            </div>
                            <h3 className="font-bold text-slate-900 text-base mb-1.5">
                                Recherche puissante
                            </h3>
                            <p className="text-xs text-slate-500 leading-relaxed max-w-xs">
                                Retrouvez un document en quelques secondes grâce aux métadonnées et à l'OCR.
                            </p>
                        </div>

                        {/* 3. Sécurité */}
                        <div className="flex flex-col items-center text-center p-3 rounded-2xl hover:bg-slate-50 transition-colors">
                            <div className="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center mb-4 shadow-md shadow-emerald-500/20">
                                <Lock className="w-6 h-6" />
                            </div>
                            <h3 className="font-bold text-slate-900 text-base mb-1.5">
                                Sécurité maximale
                            </h3>
                            <p className="text-xs text-slate-500 leading-relaxed max-w-xs">
                                Vos données sont chiffrées et hébergées de manière sécurisée sur Cloudflare R2.
                            </p>
                        </div>

                        {/* 4. Collaboration */}
                        <div className="flex flex-col items-center text-center p-3 rounded-2xl hover:bg-slate-50 transition-colors">
                            <div className="w-12 h-12 rounded-2xl bg-purple-500 text-white flex items-center justify-center mb-4 shadow-md shadow-purple-500/20">
                                <Users className="w-6 h-6" />
                            </div>
                            <h3 className="font-bold text-slate-900 text-base mb-1.5">
                                Collaboration facilitée
                            </h3>
                            <p className="text-xs text-slate-500 leading-relaxed max-w-xs">
                                Partagez et travaillez ensemble en toute simplicité.
                            </p>
                        </div>

                        {/* 5. Productivité */}
                        <div className="flex flex-col items-center text-center p-3 rounded-2xl hover:bg-slate-50 transition-colors">
                            <div className="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center mb-4 shadow-md shadow-amber-500/20">
                                <TrendingUp className="w-6 h-6" />
                            </div>
                            <h3 className="font-bold text-slate-900 text-base mb-1.5">
                                Productivité accrue
                            </h3>
                            <p className="text-xs text-slate-500 leading-relaxed max-w-xs">
                                Gagnez du temps et concentrez-vous sur l'essentiel.
                            </p>
                        </div>

                    </div>
                </div>
            </section>

            {/* VALUE PROPOSITION (MIDDLE SECTION WITH PHOTO) */}
            <section id="a-propos" className="py-20 lg:py-24 bg-slate-50/70">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                        
                        {/* Text description */}
                        <div className="lg:col-span-6">
                            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold tracking-wider text-blue-700 bg-blue-50 border border-blue-200/80 uppercase mb-4">
                                Une solution adaptée à vos métiers
                            </div>

                            <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-950 tracking-tight leading-tight mb-5">
                                Bien plus qu'une GED,<br />
                                un véritable levier de performance
                            </h2>

                            <p className="text-slate-600 text-base leading-relaxed mb-6">
                                GEDAPP s'adapte à tous les secteurs d'activité (Comptabilité, RH, Commercial, Juridique, Administration, etc.) avec des formulaires dynamiques, des métadonnées, un OCR intégré et une recherche intelligente.
                            </p>

                            <div className="space-y-3.5 mb-8">
                                {[
                                    'Adaptée à toutes les organisations',
                                    'Conforme aux standards de sécurité',
                                    'Accessible partout, sur tout appareil',
                                    'Support réactif et accompagnement personnalisé'
                                ].map((item, index) => (
                                    <div key={index} className="flex items-center gap-3">
                                        <div className="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center shrink-0">
                                            <Check className="w-3 h-3 stroke-[3]" />
                                        </div>
                                        <span className="text-slate-800 font-semibold text-sm sm:text-base">
                                            {item}
                                        </span>
                                    </div>
                                ))}
                            </div>

                            <a
                                href="#fonctionnalites"
                                className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-full shadow-md shadow-blue-500/20 transition-all group text-sm"
                            >
                                <span>Découvrir toutes les fonctionnalités</span>
                                <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                            </a>
                        </div>

                        {/* Photo with floating badge */}
                        <div className="lg:col-span-6 relative">
                            <div className="relative rounded-2xl overflow-hidden shadow-2xl border border-slate-200/80 group">
                                <img
                                    src="/images/landing/hero-feature.jpg"
                                    alt="Professionnelle utilisant GEDAPP"
                                    className="w-full h-auto object-cover transform group-hover:scale-105 transition-transform duration-700"
                                    loading="lazy"
                                />
                                <div className="absolute inset-0 bg-gradient-to-t from-slate-950/40 via-transparent to-transparent"></div>
                                
                                {/* Floating Badge bottom-right */}
                                <div className="absolute bottom-5 right-5 sm:bottom-6 sm:right-6 bg-white/95 backdrop-blur-md rounded-2xl p-3 sm:p-4 shadow-xl border border-slate-200/80 flex items-center gap-3 max-w-xs">
                                    <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                        <ShieldCheck className="w-6 h-6" />
                                    </div>
                                    <span className="text-xs sm:text-sm font-bold text-slate-900 leading-snug">
                                        Des documents bien gérés, une entreprise plus sereine.
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* DARK STATS BAR */}
            <section className="bg-[#0B1120] text-white py-14 border-y border-slate-800">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center sm:text-left">
                        
                        {/* Stat 1 */}
                        <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-blue-400 flex items-center justify-center shrink-0">
                                <Building2 className="w-6 h-6" />
                            </div>
                            <div>
                                <div className="text-3xl sm:text-4xl font-black text-white tracking-tight">+200</div>
                                <div className="text-xs sm:text-sm text-slate-400 mt-1">entreprises nous font confiance</div>
                            </div>
                        </div>

                        {/* Stat 2 */}
                        <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 flex items-center justify-center shrink-0">
                                <FileText className="w-6 h-6" />
                            </div>
                            <div>
                                <div className="text-3xl sm:text-4xl font-black text-white tracking-tight">+1 million</div>
                                <div className="text-xs sm:text-sm text-slate-400 mt-1">documents gérés</div>
                            </div>
                        </div>

                        {/* Stat 3 */}
                        <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 flex items-center justify-center shrink-0">
                                <Shield className="w-6 h-6" />
                            </div>
                            <div>
                                <div className="text-3xl sm:text-4xl font-black text-white tracking-tight">99,9%</div>
                                <div className="text-xs sm:text-sm text-slate-400 mt-1">de disponibilité</div>
                            </div>
                        </div>

                        {/* Stat 4 */}
                        <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4">
                            <div className="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 flex items-center justify-center shrink-0">
                                <Heart className="w-6 h-6" />
                            </div>
                            <div>
                                <div className="text-3xl sm:text-4xl font-black text-white tracking-tight">100%</div>
                                <div className="text-xs sm:text-sm text-slate-400 mt-1">conforme RGPD</div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* SECTOR SOLUTIONS ("UNE SOLUTION POUR CHAQUE MÉTIER") */}
            <section id="metiers" className="py-20 lg:py-24 bg-white">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <div className="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold tracking-wider text-blue-700 bg-blue-50 border border-blue-200/80 uppercase mb-3">
                            Des usages dans tous les secteurs
                        </div>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-950 tracking-tight mb-4">
                            Une solution pour chaque métier
                        </h2>
                        <p className="text-slate-600 text-sm sm:text-base leading-relaxed">
                            GEDAPP s'adapte à vos besoins spécifiques grâce à des types documentaires et des formulaires sur mesure.
                        </p>
                    </div>

                    {/* 5 Cards Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
                        
                        {/* 1. Comptabilité */}
                        <div className="group rounded-2xl overflow-hidden border border-slate-200 bg-white hover:shadow-xl transition-all duration-300 flex flex-col">
                            <div className="h-44 overflow-hidden relative">
                                <img
                                    src="/images/landing/sector-comptabilite.jpg"
                                    alt="Comptabilité"
                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div className="absolute top-3 left-3 w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-md">
                                    <FileSpreadsheet className="w-4 h-4" />
                                </div>
                            </div>
                            <div className="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 className="font-bold text-slate-900 text-base mb-1">
                                        Comptabilité
                                    </h3>
                                    <p className="text-xs text-slate-500 leading-relaxed">
                                        Factures, relevés, justificatifs...
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* 2. Ressources Humaines */}
                        <div className="group rounded-2xl overflow-hidden border border-slate-200 bg-white hover:shadow-xl transition-all duration-300 flex flex-col">
                            <div className="h-44 overflow-hidden relative">
                                <img
                                    src="/images/landing/sector-rh.jpg"
                                    alt="Ressources Humaines"
                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div className="absolute top-3 left-3 w-8 h-8 rounded-lg bg-purple-600 text-white flex items-center justify-center shadow-md">
                                    <Users className="w-4 h-4" />
                                </div>
                            </div>
                            <div className="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 className="font-bold text-slate-900 text-base mb-1">
                                        Ressources Humaines
                                    </h3>
                                    <p className="text-xs text-slate-500 leading-relaxed">
                                        Contrats, CV, congés, bulletins de salaire...
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* 3. Commercial */}
                        <div className="group rounded-2xl overflow-hidden border border-slate-200 bg-white hover:shadow-xl transition-all duration-300 flex flex-col">
                            <div className="h-44 overflow-hidden relative">
                                <img
                                    src="/images/landing/sector-commercial.jpg"
                                    alt="Commercial"
                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div className="absolute top-3 left-3 w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shadow-md">
                                    <FileCheck className="w-4 h-4" />
                                </div>
                            </div>
                            <div className="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 className="font-bold text-slate-900 text-base mb-1">
                                        Commercial
                                    </h3>
                                    <p className="text-xs text-slate-500 leading-relaxed">
                                        Devis, bons de commande, contrats clients...
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* 4. Juridique */}
                        <div className="group rounded-2xl overflow-hidden border border-slate-200 bg-white hover:shadow-xl transition-all duration-300 flex flex-col">
                            <div className="h-44 overflow-hidden relative">
                                <img
                                    src="/images/landing/sector-juridique.jpg"
                                    alt="Juridique"
                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div className="absolute top-3 left-3 w-8 h-8 rounded-lg bg-amber-600 text-white flex items-center justify-center shadow-md">
                                    <ShieldCheck className="w-4 h-4" />
                                </div>
                            </div>
                            <div className="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 className="font-bold text-slate-900 text-base mb-1">
                                        Juridique
                                    </h3>
                                    <p className="text-xs text-slate-500 leading-relaxed">
                                        Contrats, statuts, documents légaux...
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* 5. Administration */}
                        <div className="group rounded-2xl overflow-hidden border border-slate-200 bg-white hover:shadow-xl transition-all duration-300 flex flex-col">
                            <div className="h-44 overflow-hidden relative">
                                <img
                                    src="/images/landing/sector-admin.jpg"
                                    alt="Administration"
                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                    loading="lazy"
                                />
                                <div className="absolute top-3 left-3 w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center shadow-md">
                                    <Building2 className="w-4 h-4" />
                                </div>
                            </div>
                            <div className="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 className="font-bold text-slate-900 text-base mb-1">
                                        Administration
                                    </h3>
                                    <p className="text-xs text-slate-500 leading-relaxed">
                                        Courriers, notes de service, procédures...
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            {/* TESTIMONIALS SECTION ("CE QUE NOS CLIENTS DISENT DE GEDAPP") */}
            <section id="temoignages" className="py-20 lg:py-24 bg-slate-50/70 border-t border-slate-100">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-16">
                        <div className="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold tracking-wider text-blue-700 bg-blue-50 border border-blue-200/80 uppercase mb-3">
                            Ils nous font confiance
                        </div>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-950 tracking-tight">
                            Ce que nos clients disent de GEDAPP
                        </h2>
                    </div>

                    {/* Testimonial Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-10">
                        
                        {/* Card 1 */}
                        <div className="bg-white p-7 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                            <div>
                                <div className="text-blue-600 text-2xl font-serif font-black mb-3">
                                    “
                                </div>
                                <p className="text-sm text-slate-700 leading-relaxed mb-6">
                                    GEDAPP nous a permis de structurer toute notre documentation et de gagner un temps précieux dans la recherche de nos factures.
                                </p>
                            </div>
                            <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <img
                                        src="/images/landing/avatar-aminata.jpg"
                                        alt="Aminata Koné"
                                        className="w-10 h-10 rounded-full object-cover border border-slate-200"
                                    />
                                    <div>
                                        <div className="font-bold text-xs text-slate-900">Aminata Koné</div>
                                        <div className="text-[11px] text-slate-500">Directrice Administrative</div>
                                    </div>
                                </div>
                                <div className="flex text-amber-400">
                                    {[...Array(5)].map((_, i) => (
                                        <Star key={i} className="w-3.5 h-3.5 fill-current" />
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Card 2 */}
                        <div className="bg-white p-7 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                            <div>
                                <div className="text-blue-600 text-2xl font-serif font-black mb-3">
                                    “
                                </div>
                                <p className="text-sm text-slate-700 leading-relaxed mb-6">
                                    Une solution complète, simple à utiliser et parfaitement adaptée à nos besoins. Le support est toujours réactif !
                                </p>
                            </div>
                            <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <img
                                        src="/images/landing/avatar-jean.jpg"
                                        alt="Jean Traoré"
                                        className="w-10 h-10 rounded-full object-cover border border-slate-200"
                                    />
                                    <div>
                                        <div className="font-bold text-xs text-slate-900">Jean Traoré</div>
                                        <div className="text-[11px] text-slate-500">Responsable RH</div>
                                    </div>
                                </div>
                                <div className="flex text-amber-400">
                                    {[...Array(5)].map((_, i) => (
                                        <Star key={i} className="w-3.5 h-3.5 fill-current" />
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Card 3 */}
                        <div className="bg-white p-7 rounded-2xl border border-slate-200/90 shadow-sm flex flex-col justify-between hover:shadow-md transition">
                            <div>
                                <div className="text-blue-600 text-2xl font-serif font-black mb-3">
                                    “
                                </div>
                                <p className="text-sm text-slate-700 leading-relaxed mb-6">
                                    La gestion des contrats et des documents juridiques est enfin centralisée et sécurisée. Je recommande vivement.
                                </p>
                            </div>
                            <div className="pt-4 border-t border-slate-100 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <img
                                        src="/images/landing/avatar-sophie.jpg"
                                        alt="Sophie Diallo"
                                        className="w-10 h-10 rounded-full object-cover border border-slate-200"
                                    />
                                    <div>
                                        <div className="font-bold text-xs text-slate-900">Sophie Diallo</div>
                                        <div className="text-[11px] text-slate-500">Directrice Juridique</div>
                                    </div>
                                </div>
                                <div className="flex text-amber-400">
                                    {[...Array(5)].map((_, i) => (
                                        <Star key={i} className="w-3.5 h-3.5 fill-current" />
                                    ))}
                                </div>
                            </div>
                        </div>

                    </div>

                    {/* Pagination Indicator Dots */}
                    <div className="flex justify-center items-center gap-2">
                        <button
                            type="button"
                            onClick={() => setActiveTestimonialTab(0)}
                            className={`h-2 rounded-full transition-all ${activeTestimonialTab === 0 ? 'w-6 bg-blue-600' : 'w-2 bg-slate-300'}`}
                            aria-label="Page 1"
                        />
                        <button
                            type="button"
                            onClick={() => setActiveTestimonialTab(1)}
                            className={`h-2 rounded-full transition-all ${activeTestimonialTab === 1 ? 'w-6 bg-blue-600' : 'w-2 bg-slate-300'}`}
                            aria-label="Page 2"
                        />
                    </div>

                </div>
            </section>

            {/* PRICING SECTION ("CHOISISSEZ LE PLAN QUI VOUS CORRESPOND") */}
            <section id="tarifs" className="py-20 lg:py-24 bg-white">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    
                    {/* Header */}
                    <div className="text-center max-w-3xl mx-auto mb-12">
                        <div className="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold tracking-wider text-blue-700 bg-blue-50 border border-blue-200/80 uppercase mb-3">
                            Des tarifs simples et transparents
                        </div>
                        <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-950 tracking-tight mb-8">
                            Choisissez le plan qui vous correspond
                        </h2>

                        {/* Monthly / Annually Switch */}
                        <div className="inline-flex items-center p-1 bg-slate-100 rounded-full border border-slate-200">
                            <button
                                type="button"
                                onClick={() => setBillingPeriod('monthly')}
                                className={`px-5 py-2 rounded-full text-xs sm:text-sm font-semibold transition ${
                                    billingPeriod === 'monthly'
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                Mensuel
                            </button>
                            <button
                                type="button"
                                onClick={() => setBillingPeriod('annually')}
                                className={`px-5 py-2 rounded-full text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 ${
                                    billingPeriod === 'annually'
                                        ? 'bg-blue-600 text-white shadow-xs'
                                        : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                <span>Annuel</span>
                                <span className={`text-[10px] px-1.5 py-0.2 rounded-full font-bold ${
                                    billingPeriod === 'annually'
                                        ? 'bg-white/20 text-white'
                                        : 'bg-emerald-100 text-emerald-800'
                                }`}>
                                    -20%
                                </span>
                            </button>
                        </div>
                    </div>

                    {/* Pricing Cards */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch w-full mx-auto">
                        
                        {/* 1. Essentiel */}
                        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-lg transition">
                            <div>
                                <h3 className="text-xl font-bold text-slate-950 mb-1">Essentiel</h3>
                                <p className="text-xs text-slate-500 mb-6">Idéal pour les petites équipes</p>
                                
                                <div className="mb-6">
                                    <span className="text-3xl sm:text-4xl font-black text-slate-950">
                                        {billingPeriod === 'monthly' ? '19 000' : '15 200'} FCFA
                                    </span>
                                    <span className="text-xs text-slate-500 font-medium"> / mois</span>
                                </div>

                                <div className="space-y-3 pt-6 border-t border-slate-100 mb-8 text-xs sm:text-sm">
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Jusqu'à 5 utilisateurs</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>50 Go de stockage</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Fonctionnalités de base</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Support par email</span>
                                    </div>
                                </div>
                            </div>

                            <Link
                                href="/inscription"
                                className="w-full text-center py-3 rounded-full border border-blue-600 text-blue-600 hover:bg-blue-50 font-semibold text-sm transition"
                            >
                                Essayer gratuitement
                            </Link>
                        </div>

                        {/* 2. Professionnel (Most Popular) */}
                        <div className="bg-white rounded-3xl p-8 border-2 border-blue-600 shadow-xl shadow-blue-500/10 flex flex-col justify-between relative transform lg:-translate-y-2">
                            {/* Popular pill */}
                            <div className="absolute -top-3.5 right-8 bg-blue-600 text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                                Le plus populaire
                            </div>

                            <div>
                                <h3 className="text-xl font-bold text-slate-950 mb-1">Professionnel</h3>
                                <p className="text-xs text-slate-500 mb-6">Pour les entreprises en croissance</p>
                                
                                <div className="mb-6">
                                    <span className="text-3xl sm:text-4xl font-black text-slate-950">
                                        {billingPeriod === 'monthly' ? '39 000' : '31 200'} FCFA
                                    </span>
                                    <span className="text-xs text-slate-500 font-medium"> / mois</span>
                                </div>

                                <div className="space-y-3 pt-6 border-t border-slate-100 mb-8 text-xs sm:text-sm">
                                    <div className="flex items-center gap-2.5 text-slate-700 font-medium">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Jusqu'à 20 utilisateurs</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700 font-medium">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>200 Go de stockage</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700 font-medium">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>OCR inclus</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700 font-medium">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Partage et workflows</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700 font-medium">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Support prioritaire</span>
                                    </div>
                                </div>
                            </div>

                            <Link
                                href="/inscription"
                                className="w-full text-center py-3.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-md shadow-blue-500/25 transition"
                            >
                                Essayer gratuitement
                            </Link>
                        </div>

                        {/* 3. Entreprise */}
                        <div className="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm flex flex-col justify-between hover:shadow-lg transition">
                            <div>
                                <h3 className="text-xl font-bold text-slate-950 mb-1">Entreprise</h3>
                                <p className="text-xs text-slate-500 mb-6">Pour les grandes organisations</p>
                                
                                <div className="mb-6">
                                    <span className="text-3xl sm:text-4xl font-black text-slate-950">
                                        Sur devis
                                    </span>
                                </div>

                                <div className="space-y-3 pt-6 border-t border-slate-100 mb-8 text-xs sm:text-sm">
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Utilisateurs illimités</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Stockage sur mesure</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Intégrations personnalisées</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Support dédié</span>
                                    </div>
                                    <div className="flex items-center gap-2.5 text-slate-700">
                                        <Check className="w-4 h-4 text-blue-600 shrink-0" />
                                        <span>Accompagnement à la mise en place</span>
                                    </div>
                                </div>
                            </div>

                            <Link
                                href="/enterprise"
                                className="w-full text-center py-3 rounded-full border border-slate-300 text-slate-800 hover:bg-slate-50 font-semibold text-sm transition"
                            >
                                Demander une démonstration
                            </Link>
                        </div>

                    </div>
                </div>
            </section>

            {/* CALL TO ACTION BANNER */}
            <section className="py-16 bg-white">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#071329] via-[#0b244d] to-[#091b38] px-8 py-14 sm:px-14 sm:py-16 text-white shadow-2xl">
                        {/* Background radial glow */}
                        <div className="absolute top-1/2 left-1/3 -translate-y-1/2 w-96 h-96 bg-blue-500/20 blur-3xl pointer-events-none rounded-full"></div>

                        <div className="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-8">
                            <div className="max-w-2xl text-center lg:text-left">
                                <h2 className="text-3xl sm:text-4xl font-extrabold tracking-tight mb-4">
                                    Prêt à transformer votre gestion documentaire ?
                                </h2>
                                <p className="text-slate-300 text-sm sm:text-base leading-relaxed">
                                    Rejoignez dès aujourd'hui les entreprises qui nous font confiance et passez à une GED plus simple, plus sécurisée et plus performante.
                                </p>
                            </div>

                            <div className="flex flex-col items-center sm:items-end gap-2 shrink-0">
                                <Link
                                    href={user ? "/dashboard" : "/inscription"}
                                    className="inline-flex items-center gap-2 bg-white hover:bg-slate-100 text-blue-900 font-bold px-7 py-3.5 rounded-full shadow-lg transition duration-200 group text-sm sm:text-base"
                                >
                                    <span>Commencer gratuitement</span>
                                    <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                                </Link>
                                <span className="text-[11px] text-slate-400">
                                    Essai gratuit 14 jours • Sans carte bancaire
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* FOOTER */}
            <footer id="contact" className="bg-[#0B1120] text-slate-400 pt-16 pb-12 border-t border-slate-800 text-xs sm:text-sm">
                <div className="w-full px-4 sm:px-6 lg:px-12 xl:px-16 2xl:px-24">
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 mb-12">
                        
                        {/* Col 1: Brand */}
                        <div className="lg:col-span-4">
                            <div className="flex items-center gap-2.5 mb-4">
                                <div className="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold shadow-md shadow-blue-500/20">
                                    <svg className="w-5 h-5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M7 4V20C7 20.5523 7.44772 21 8 21H18C18.5523 21 19 20.5523 19 20V8.5L14.5 4H8C7.44772 4 7 4.44772 7 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                        <path d="M14 4V9H19" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    </svg>
                                </div>
                                <div className="flex flex-col">
                                    <span className="text-xl font-bold tracking-tight text-white leading-none">
                                        GED<span className="text-blue-500">APP</span>
                                    </span>
                                    <span className="text-[10px] text-slate-400 font-medium tracking-tight mt-1 leading-none">
                                        Vos documents. Plus loin.
                                    </span>
                                </div>
                            </div>
                            <p className="text-slate-400 text-xs leading-relaxed max-w-sm mb-6">
                                Une solution de gestion documentaire simple, sécurisée et puissante pour les entreprises.
                            </p>
                            
                            {/* Social Icons */}
                            <div className="flex items-center gap-3 text-slate-400">
                                <a href="#" className="w-8 h-8 rounded-lg bg-slate-800 hover:bg-blue-600 hover:text-white flex items-center justify-center transition">
                                    <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.28 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.75M6.46 10.9h2.8v8.37h-2.8v-8.37M7.86 6.54a1.63 1.63 0 0 0-1.63 1.63 1.63 1.63 0 0 0 1.63 1.63 1.63 1.63 0 0 0 1.63-1.63c0-.9-.73-1.63-1.63-1.63Z"/></svg>
                                </a>
                                <a href="#" className="w-8 h-8 rounded-lg bg-slate-800 hover:bg-blue-600 hover:text-white flex items-center justify-center transition">
                                    <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                                <a href="#" className="w-8 h-8 rounded-lg bg-slate-800 hover:bg-blue-600 hover:text-white flex items-center justify-center transition">
                                    <svg className="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                </a>
                            </div>
                        </div>

                        {/* Col 2: Produit */}
                        <div className="lg:col-span-2">
                            <h4 className="text-white font-bold mb-4">Produit</h4>
                            <ul className="space-y-2.5 text-xs">
                                <li><a href="#fonctionnalites" className="hover:text-white transition">Fonctionnalités</a></li>
                                <li><a href="#tarifs" className="hover:text-white transition">Tarifs</a></li>
                                <li><a href="#fonctionnalites" className="hover:text-white transition">Sécurité</a></li>
                                <li><a href="#metiers" className="hover:text-white transition">Intégrations</a></li>
                                <li><a href="#metiers" className="hover:text-white transition">Changelog</a></li>
                            </ul>
                        </div>

                        {/* Col 3: Ressources */}
                        <div className="lg:col-span-2">
                            <h4 className="text-white font-bold mb-4">Ressources</h4>
                            <ul className="space-y-2.5 text-xs">
                                <li><a href="#fonctionnalites" className="hover:text-white transition">Documentation</a></li>
                                <li><a href="#metiers" className="hover:text-white transition">Blog</a></li>
                                <li><a href="#contact" className="hover:text-white transition">Centre d'aide</a></li>
                                <li><a href="#contact" className="hover:text-white transition">Tutoriels</a></li>
                                <li><a href="#contact" className="hover:text-white transition">Webinaires</a></li>
                            </ul>
                        </div>

                        {/* Col 4: Entreprise */}
                        <div className="lg:col-span-2">
                            <h4 className="text-white font-bold mb-4">Entreprise</h4>
                            <ul className="space-y-2.5 text-xs">
                                <li><a href="#a-propos" className="hover:text-white transition">À propos</a></li>
                                <li><a href="#contact" className="hover:text-white transition">Contact</a></li>
                                <li><a href="#contact" className="hover:text-white transition">Carrières</a></li>
                                <li><a href="#contact" className="hover:text-white transition">Mentions légales</a></li>
                                <li><a href="#contact" className="hover:text-white transition">Politique de confidentialité</a></li>
                            </ul>
                        </div>

                        {/* Col 5: Newsletter */}
                        <div className="lg:col-span-2">
                            <h4 className="text-white font-bold mb-4">Restez informé</h4>
                            <p className="text-xs text-slate-400 mb-3">
                                Recevez nos actualités et conseils.
                            </p>
                            <form onSubmit={handleNewsletterSubmit} className="flex items-center gap-1.5">
                                <input
                                    type="email"
                                    required
                                    value={newsletterEmail}
                                    onChange={(e) => setNewsletterEmail(e.target.value)}
                                    placeholder="Votre email"
                                    className="bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 flex-1 min-w-0"
                                />
                                <button
                                    type="submit"
                                    className="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-xl transition shrink-0"
                                    aria-label="Envoyer"
                                >
                                    <Send className="w-3.5 h-3.5" />
                                </button>
                            </form>
                            {newsletterSubmitted && (
                                <p className="text-[11px] text-emerald-400 mt-2 font-medium">
                                    Merci pour votre inscription !
                                </p>
                            )}
                        </div>

                    </div>

                    <div className="pt-8 border-t border-slate-800/80 text-center text-xs text-slate-500">
                        © 2026 GEDAPP. Tous droits réservés.
                    </div>
                </div>
            </footer>

            {/* VIDEO MODAL */}
            {videoModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-200">
                    <div className="bg-slate-900 border border-slate-800 rounded-3xl max-w-3xl w-full p-6 text-white shadow-2xl relative">
                        <button
                            type="button"
                            onClick={() => setVideoModalOpen(false)}
                            className="absolute top-5 right-5 text-slate-400 hover:text-white p-1 rounded-xl bg-slate-800 hover:bg-slate-700 transition"
                            aria-label="Fermer"
                        >
                            <X className="w-5 h-5" />
                        </button>
                        
                        <div className="flex items-center gap-3 mb-4">
                            <div className="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                                <Play className="w-4 h-4 fill-current ml-0.5" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-white">Démonstration GEDAPP</h3>
                                <p className="text-xs text-slate-400">Découvrez l'interface moderne et intuitive</p>
                            </div>
                        </div>

                        {/* Video Frame Mockup */}
                        <div className="relative aspect-video rounded-2xl overflow-hidden bg-slate-950 border border-slate-800 flex items-center justify-center group">
                            <img
                                src="/images/landing/hero-feature.jpg"
                                alt="Démo vidéo"
                                className="w-full h-full object-cover opacity-60"
                            />
                            <div className="absolute inset-0 bg-blue-950/40 flex flex-col items-center justify-center text-center p-6">
                                <div className="w-16 h-16 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/30 mb-4 transform group-hover:scale-110 transition-transform">
                                    <Play className="w-7 h-7 fill-current ml-1" />
                                </div>
                                <h4 className="text-xl font-bold text-white mb-2">Visite guidée en 2 minutes</h4>
                                <p className="text-xs text-slate-300 max-w-md mb-4">
                                    Indexation automatique par OCR, formulaires métiers et workflows d'approbation simplifiés.
                                </p>
                                <Link
                                    href={user ? "/dashboard" : "/register/organization"}
                                    className="bg-white text-blue-900 font-bold px-6 py-2.5 rounded-full text-xs shadow hover:bg-slate-100 transition"
                                >
                                    Essayer directement l'application →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
