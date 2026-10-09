import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { 
    Building2, 
    Users, 
    ShieldCheck, 
    TrendingUp, 
    Briefcase, 
    ArrowRight, 
    Layers, 
    CheckCircle2, 
    FileSpreadsheet, 
    Scale, 
    FileCheck 
} from 'lucide-react';
import Reveal, { IllustrativeBadge } from './Reveal';

const useCases = [
    {
        id: 'finance',
        icon: FileSpreadsheet,
        title: 'Direction Financière & Comptabilité',
        summary: 'Factures fournisseurs, états financiers, justificatifs comptables et pièces de dépenses.',
        image: '/images/landing/sector-comptabilite.jpg',
        benefits: [
            'Classement par exercice fiscal et fournisseur',
            'Champs de métadonnées dédiés (montant, date d’échéance, statut)',
            'Recherche instantanée et extraction OCR du contenu',
        ],
        badge: 'Comptabilité',
        color: 'from-blue-600 to-indigo-600',
    },
    {
        id: 'rh',
        icon: Users,
        title: 'Ressources Humaines',
        summary: 'Dossiers du personnel, contrats de travail, fiches de paie et demandes de congés.',
        image: '/images/landing/sector-rh.jpg',
        benefits: [
            'Cloisonnement strict des dossiers par Direction et Service',
            'Contrôle d’accès confidentiel par utilisateur et rôle',
            'Historique complet des versions et des avenants',
        ],
        badge: 'RH & Personnel',
        color: 'from-indigo-600 to-purple-600',
    },
    {
        id: 'juridique',
        icon: Scale,
        title: 'Direction Juridique & Conformité',
        summary: 'Contrats commerciaux, statuts, baux, accords de confidentialité et contentieux.',
        image: '/images/landing/sector-juridique.jpg',
        benefits: [
            'Suivi des versions contractuelles avec comparatif',
            'Traçabilité certifiée de chaque consultation et modification',
            'Partage interne sécurisé avec droits temporaires ou restreints',
        ],
        badge: 'Juridique',
        color: 'from-purple-600 to-pink-600',
    },
    {
        id: 'admin',
        icon: Briefcase,
        title: 'Administration & Direction Générale',
        summary: 'Procès-verbaux, courriers officiels, notes de service et décisions stratégiques.',
        image: '/images/landing/sector-admin.jpg',
        benefits: [
            'Vue d’ensemble des flux documentaires de l’organisation',
            'Gestion centralisée des Directions et Services rattachés',
            'Archivage structuré et pérenne de la mémoire de l’entreprise',
        ],
        badge: 'Direction',
        color: 'from-blue-600 to-cyan-600',
    },
];

const architecturePillars = [
    {
        icon: Layers,
        title: 'Arborescence métier Direction → Service',
        description: 'Fini les répertoires partagés anarchiques. APPGED structure vos documents selon le schéma organisationnel exact de votre entreprise.',
    },
    {
        icon: Users,
        title: 'Gestion unifiée des utilisateurs',
        description: 'Invitez vos collaborateurs, assignez-les à leurs services respectifs et gérez les départs sans risquer d’égarer le moindre document.',
    },
    {
        icon: ShieldCheck,
        title: 'Gouvernance des rôles et permissions',
        description: 'Définissez précisément qui consulte, dépose, valide ou archive. Chaque service conserve l’autonomie de ses espaces de travail.',
    },
    {
        icon: TrendingUp,
        title: 'Évolutivité garantie et sans friction',
        description: 'Votre structure grandit ? Ajoutez des directions, augmentez votre stockage et adaptez votre formule SaaS en quelques clics.',
    },
];

export default function EnterpriseSolutions() {
    const [activeUseCase, setActiveUseCase] = useState(useCases[0].id);
    const selectedCase = useCases.find((c) => c.id === activeUseCase) || useCases[0];

    return (
        <section id="solutions" className="py-24 bg-white border-t border-slate-100">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                {/* Heading */}
                <div className="text-center max-w-3xl mx-auto mb-16">
                    <Reveal>
                        <h2 className="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-950 tracking-tight leading-tight">
                            Une structure pensée pour votre organisation
                        </h2>
                        <p className="mt-4 text-base sm:text-lg text-slate-600 leading-relaxed">
                            APPGED s’intègre naturellement dans le fonctionnement des entreprises modernes en modélisant fidèlement votre arborescence interne.
                        </p>
                    </Reveal>
                </div>

                {/* 4 Pillars Grid */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-20">
                    {architecturePillars.map((pillar, index) => {
                        const Icon = pillar.icon;
                        return (
                            <Reveal
                                key={pillar.title}
                                delay={index * 100}
                                className="group relative p-6 rounded-2xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-blue-300 transition-all duration-300"
                            >
                                <div className="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center mb-5 group-hover:scale-110 transition-transform">
                                    <Icon className="w-6 h-6" />
                                </div>
                                <h3 className="text-base font-bold text-slate-950 mb-2">
                                    {pillar.title}
                                </h3>
                                <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
                                    {pillar.description}
                                </p>
                            </Reveal>
                        );
                    })}
                </div>

                {/* Cas d'usages Métiers Interactifs */}
                <Reveal className="rounded-3xl border border-slate-800 bg-[#0B1220] text-white p-6 sm:p-10 lg:p-12 relative overflow-hidden">
                    <div className="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-8 border-b border-white/10 pb-8">
                        <div>
                            <div className="flex items-center gap-2 mb-2">
                                <span className="text-xs font-bold uppercase tracking-wider text-cyan-400">
                                    Cas d'usage métiers
                                </span>
                                
                            </div>
                            <h3 className="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                                Comment chaque direction exploite APPGED
                            </h3>
                        </div>
                        <Link
                            href="/enterprise"
                            className="inline-flex items-center gap-2 text-sm font-semibold text-cyan-300 hover:text-white transition group shrink-0"
                        >
                            <span>Découvrir notre offre Enterprise</span>
                            <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>

                    {/* Tabs */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-8" role="tablist" aria-label="Cas d'usage métiers">
                        {useCases.map((uc) => {
                            const isSelected = uc.id === activeUseCase;
                            const Icon = uc.icon;
                            return (
                                <button
                                    key={uc.id}
                                    type="button"
                                    role="tab"
                                    aria-selected={isSelected}
                                    onClick={() => setActiveUseCase(uc.id)}
                                    className={`flex items-center gap-2.5 p-3 sm:p-4 rounded-xl text-left transition-all duration-200 border ${
                                        isSelected
                                            ? 'bg-white/15 border-white/30 text-white font-bold'
                                            : 'bg-white/5 border-transparent text-slate-400 hover:bg-white/10 hover:text-slate-200 font-medium'
                                    }`}
                                >
                                    <Icon className={`w-4 h-4 sm:w-5 sm:h-5 shrink-0 ${isSelected ? 'text-cyan-400' : 'text-slate-400'}`} />
                                    <span className="text-xs sm:text-sm truncate">{uc.badge}</span>
                                </button>
                            );
                        })}
                    </div>

                    {/* Active Tab Showcase Content */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center bg-white/[0.04] border border-white/10 rounded-2xl p-6 sm:p-8">
                        <div className="lg:col-span-7">
                           
                            <h4 className="text-xl sm:text-2xl font-bold text-white mb-3">
                                {selectedCase.title}
                            </h4>
                            <p className="text-sm sm:text-base text-slate-300 mb-6 leading-relaxed">
                                {selectedCase.summary}
                            </p>
                            <div className="space-y-3">
                                {selectedCase.benefits.map((benefit, i) => (
                                    <div key={i} className="flex items-start gap-3">
                                        <div className="w-5 h-5 rounded-full bg-cyan-400/20 text-cyan-300 flex items-center justify-center shrink-0 mt-0.5">
                                            <CheckCircle2 className="w-3.5 h-3.5" />
                                        </div>
                                        <span className="text-xs sm:text-sm text-slate-200">
                                            {benefit}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Visual Mock Card with real sector photo */}
                        <div className="lg:col-span-5">
                            <div className="relative rounded-2xl overflow-hidden border border-white/20 bg-night-soft group">
                                <div className="aspect-[4/3] w-full overflow-hidden relative">
                                    <img
                                        src={selectedCase.image}
                                        alt={selectedCase.title}
                                        className="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700"
                                        loading="lazy"
                                    />

                                    {/* Top glass pill */}
                                    <div className="absolute top-3 left-3 right-3 flex items-center justify-between">
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-night/80 text-cyan-300 backdrop-blur-md border border-white/15">
                                            <span className="w-2 h-2 rounded-full bg-cyan-400" />
                                            {selectedCase.badge}
                                        </span>
                                        <span className="text-[10px] text-white/90 bg-black/60 px-2 py-0.5 rounded-full font-mono uppercase tracking-wider backdrop-blur-xs">
                                            Espace dédié
                                        </span>
                                    </div>

                                    {/* Bottom floating details */}
                                    <div className="absolute bottom-3 left-3 right-3 p-3 rounded-xl bg-night/90 backdrop-blur-md border border-white/10">
                                        <div className="grid grid-cols-2 gap-2 text-[11px]">
                                            <div>
                                                <span className="text-slate-400 block text-[10px]">Périmètre</span>
                                                <span className="font-semibold text-white truncate block">{selectedCase.badge}</span>
                                            </div>
                                            <div>
                                                <span className="text-slate-400 block text-[10px]">Traçabilité</span>
                                                <span className="font-semibold text-emerald-400 block">100% audité</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </Reveal>
            </div>
        </section>
    );
}
