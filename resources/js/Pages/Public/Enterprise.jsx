import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    Building2,
    ShieldCheck,
    CheckCircle2,
    Crown,
    ArrowRight,
    Users,
    HardDrive,
    Cpu,
    Headphones,
    Sliders,
    Sparkles,
    Send,
    Check,
    AlertCircle
} from 'lucide-react';

export default function Enterprise({ prefill = {} }) {
    const { flash } = usePage().props;

    const { data, setData, post, processing, reset, recentlySuccessful, errors } = useForm({
        name: prefill.name || '',
        company: prefill.company || '',
        email: prefill.email || '',
        phone: prefill.phone || '',
        estimated_users: '50-200',
        needs: 'Migration documentaire & volumétrie sur-mesure',
        message: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/enterprise', {
            onSuccess: () => reset('message'),
        });
    };

    return (
        <div className="min-h-screen bg-[#0B132B] text-slate-100 font-sans selection:bg-blue-600 selection:text-white">
            <Head title="Offre Enterprise & Démonstration sur-mesure — GEDAPP" />

            {/* Header */}
            <header className="border-b border-slate-800/80 bg-[#0B132B]/90 backdrop-blur-md sticky top-0 z-40">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-3 group">
                        <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 shrink-0 group-hover:scale-105 transition-transform">
                            GED
                        </div>
                        <span className="font-black text-xl tracking-tight text-white">
                            GED<span className="text-blue-500">APP</span>
                        </span>
                    </Link>

                    <nav className="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-300">
                        <Link href="/" className="hover:text-white transition">Accueil</Link>
                        <Link href="/fonctionnalites" className="hover:text-white transition">Fonctionnalités</Link>
                        <Link href="/tarifs" className="hover:text-white transition">Tarifs</Link>
                        <Link href="/enterprise" className="text-blue-400 font-bold">Enterprise</Link>
                        <Link href="/contact" className="hover:text-white transition">Contact</Link>
                    </nav>

                    <div className="flex items-center gap-3">
                        <Link href="/login" className="text-xs sm:text-sm font-semibold text-slate-300 hover:text-white px-3 py-2 transition">
                            Connexion
                        </Link>
                        <Link
                            href="/inscription"
                            className="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-500 text-white transition shadow-lg shadow-blue-600/30"
                        >
                            <span>Essai gratuit 14j</span>
                            <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </header>

            {/* Content Container */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-16">
                {/* Intro */}
                <div className="text-center space-y-4 max-w-3xl mx-auto">
                    <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <Crown className="w-3.5 h-3.5" />
                        <span>Formule Grands Comptes & Administrations</span>
                    </div>

                    <h1 className="text-3xl sm:text-5xl font-black text-white tracking-tight leading-tight">
                        Une GED haute performance façonnée pour vos <span className="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-orange-400">exigences d'entreprise</span>
                    </h1>

                    <p className="text-slate-400 text-base sm:text-lg">
                        Bénéficiez d'une infrastructure dédiée, de volumes de stockage et d'OCR sans restriction et d'un accompagnement personnalisé par nos ingénieurs.
                    </p>
                </div>

                {/* Grid : Avantages Enterprise + Formulaire de Lead */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
                    {/* Left Column : Enterprise Advantages */}
                    <div className="lg:col-span-6 space-y-6">
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-8 space-y-6">
                            <h3 className="text-xl font-bold text-white flex items-center gap-2.5">
                                <Sparkles className="w-5 h-5 text-amber-400" />
                                <span>Inclus dans l'offre Enterprise :</span>
                            </h3>

                            <div className="space-y-4 text-sm text-slate-300">
                                <div className="flex items-start gap-3">
                                    <div className="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <Check className="w-3.5 h-3.5 stroke-[3]" />
                                    </div>
                                    <div>
                                        <strong className="text-white block font-semibold">Nombre d'utilisateurs sur mesure</strong>
                                        <span className="text-slate-400 text-xs">Déploiement pour 50, 500 ou 5 000+ collaborateurs sans dégradation de vitesse.</span>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <Check className="w-3.5 h-3.5 stroke-[3]" />
                                    </div>
                                    <div>
                                        <strong className="text-white block font-semibold">Stockage & Archivage Extensible</strong>
                                        <span className="text-slate-400 text-xs">Plafonds sur mesure jusqu'à plusieurs dizaines de téraoctets sur Cloudflare R2 / S3.</span>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <Check className="w-3.5 h-3.5 stroke-[3]" />
                                    </div>
                                    <div>
                                        <strong className="text-white block font-semibold">Moteur OCR Haute Capacité</strong>
                                        <span className="text-slate-400 text-xs">Indexation massive par lots de vos archives historiques numérisées.</span>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <Check className="w-3.5 h-3.5 stroke-[3]" />
                                    </div>
                                    <div>
                                        <strong className="text-white block font-semibold">Accompagnement & Migration Clé-en-main</strong>
                                        <span className="text-slate-400 text-xs">Prise en charge de la reprise de vos données depuis vos serveurs actuels.</span>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <div className="w-6 h-6 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <Check className="w-3.5 h-3.5 stroke-[3]" />
                                    </div>
                                    <div>
                                        <strong className="text-white block font-semibold">Support Prioritaire 24/7 & SLA Garanti</strong>
                                        <span className="text-slate-400 text-xs">Engagement contractuel de disponibilité (99.9%) et interlocuteur dédié.</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-blue-900/20 border border-blue-500/20 rounded-3xl p-6 flex items-center gap-4 text-xs text-blue-200">
                            <ShieldCheck className="w-8 h-8 text-blue-400 shrink-0" />
                            <div>
                                <strong>Isolation et gouvernance des données :</strong> Vos données d'entreprise restent la propriété exclusive de votre organisation et font l'objet d'un audit de sécurité rigoureux.
                            </div>
                        </div>
                    </div>

                    {/* Right Column : Demo & Commercial Lead Form */}
                    <div className="lg:col-span-6 bg-slate-900/90 border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-2xl relative">
                        <h3 className="text-2xl font-black text-white mb-2">
                            Demander une démonstration
                        </h3>
                        <p className="text-slate-400 text-xs sm:text-sm mb-6">
                            Présentez-nous vos besoins pour recevoir un devis personnalisé et planifier une démo avec nos consultants.
                        </p>

                        {/* Flash Messages */}
                        {recentlySuccessful && (
                            <div className="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex items-center gap-3 text-sm text-emerald-400">
                                <CheckCircle2 className="w-5 h-5 shrink-0" />
                                <span>Votre demande a bien été envoyée ! Un expert GED vous contactera sous 24h.</span>
                            </div>
                        )}

                        {flash?.info && (
                            <div className="mb-6 p-4 bg-blue-500/10 border border-blue-500/30 rounded-2xl flex items-center gap-3 text-sm text-blue-400">
                                <Sparkles className="w-5 h-5 shrink-0" />
                                <span>{flash.info}</span>
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                        Nom & Prénom <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Jean Dupont"
                                        className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                        required
                                    />
                                    {errors.name && <p className="text-xs text-rose-400 mt-1">{errors.name}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                        Entreprise / Organisation <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.company}
                                        onChange={(e) => setData('company', e.target.value)}
                                        placeholder="Société SA"
                                        className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                        required
                                    />
                                    {errors.company && <p className="text-xs text-rose-400 mt-1">{errors.company}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                        Email professionnel <span className="text-rose-400">*</span>
                                    </label>
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="j.dupont@societe.com"
                                        className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                        required
                                    />
                                    {errors.email && <p className="text-xs text-rose-400 mt-1">{errors.email}</p>}
                                </div>

                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                        Téléphone professionnel
                                    </label>
                                    <input
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        placeholder="+225 07 00 00 00 00"
                                        className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    />
                                    {errors.phone && <p className="text-xs text-rose-400 mt-1">{errors.phone}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                        Utilisateurs estimés
                                    </label>
                                    <select
                                        value={data.estimated_users}
                                        onChange={(e) => setData('estimated_users', e.target.value)}
                                        className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option value="20-50">20 à 50 utilisateurs</option>
                                        <option value="50-200">50 à 200 utilisateurs</option>
                                        <option value="200-500">200 à 500 utilisateurs</option>
                                        <option value="500+">Plus de 500 utilisateurs</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                        Type de besoin principal
                                    </label>
                                    <select
                                        value={data.needs}
                                        onChange={(e) => setData('needs', e.target.value)}
                                        className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    >
                                        <option value="Migration GED existante">Migration d'une GED existante</option>
                                        <option value="Déploiement initial">Premier déploiement GED</option>
                                        <option value="OCR volumique & numérisation">OCR volumique & numérisation</option>
                                        <option value="Workflows & circuits d'approbation">Workflows complexes & signatures</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Détails de votre projet <span className="text-rose-400">*</span>
                                </label>
                                <textarea
                                    rows={4}
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    placeholder="Décrivez vos objectifs documentaires, contraintes de calendrier ou intégrations requises..."
                                    className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    required
                                />
                                {errors.message && <p className="text-xs text-rose-400 mt-1">{errors.message}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold text-sm shadow-xl shadow-blue-600/30 flex items-center justify-center gap-2 transition disabled:opacity-60"
                            >
                                <Send className="w-4 h-4" />
                                <span>Envoyer ma demande de démonstration</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {/* Footer */}
            <footer className="border-t border-slate-800/80 bg-[#0B132B] py-12 px-4 sm:px-6 lg:px-8 text-xs text-slate-500 text-center">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span>© {new Date().getFullYear()} GEDAPP SaaS. Tous droits réservés.</span>
                    <div className="flex gap-6">
                        <Link href="/fonctionnalites" className="hover:text-slate-300 transition">Fonctionnalités</Link>
                        <Link href="/tarifs" className="hover:text-slate-300 transition">Tarifs</Link>
                        <Link href="/enterprise" className="hover:text-slate-300 transition">Enterprise</Link>
                        <Link href="/contact" className="hover:text-slate-300 transition">Contact</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
