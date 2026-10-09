import React from 'react';
import { Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, Share2, Users2, ShieldCheck, Zap } from 'lucide-react';
import Reveal, { IllustrativeBadge } from './Reveal';

/**
 * Collaboration & Corporate Workflow Showcase
 * Highlights team synergy, cross-departmental document flows, and digital transformation.
 */
export default function CollaborationShowcase() {
    const pillars = [
        {
            title: 'Partage de l’information fluide',
            desc: 'Finies les versions divergentes en pièces jointes. Vos collaborateurs consultent et partagent la même version officielle en un clic.',
        },
        {
            title: 'Collaboration inter-services décloisonnée',
            desc: 'RH, Finance, Juridique et Opérations collaborent avec des droits précis et des métadonnées harmonisées.',
        },
        {
            title: 'Circulation maîtrisée et traçable',
            desc: 'Chaque consultation, téléchargement et commentaire est historisé pour une gouvernance sereine et transparente.',
        },
        {
            title: 'Accélération de la transformation numérique',
            desc: 'Supprimez la friction du papier et des classeurs physiques pour des processus décisionnels jusqu’à 4x plus rapides.',
        },
    ];

    return (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            {/* Visual Column */}
            <div className="lg:col-span-6 relative">
                <Reveal direction="up">
                    <div className="relative group">
                        {/* Main Photography Frame */}
                        <div className="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-900">
                            <img
                                src="/images/landing/hero-feature.jpg"
                                alt="Équipe professionnelle collaborant sur la plateforme APPGED dans un espace moderne"
                                className="w-full h-auto max-h-[480px] object-cover object-center transition-transform duration-700 ease-out group-hover:scale-105"
                                loading="lazy"
                                width="720"
                                height="480"
                            />

                            {/* Floating Glass Pill inside photography */}
                            <div className="absolute bottom-5 left-5 right-5 sm:left-6 sm:right-6 rounded-2xl bg-white/95 backdrop-blur-md border border-slate-200/80 p-4">
                                <div className="flex items-center gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">
                                        <Users2 className="h-5 w-5" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="text-xs font-semibold uppercase tracking-wider text-blue-600">
                                            Sérénité d’équipe
                                        </p>
                                        <p className="text-sm font-bold text-slate-900 truncate">
                                            Des documents bien gérés, une organisation plus sereine.
                                        </p>
                                    </div>
                                    <div className="hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-[11px] font-bold text-emerald-700">
                                        <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                        <span>Synchronisé</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Floating Micro-Badge Top Right */}
                        <div className="absolute -top-4 -right-4 hidden sm:flex items-center gap-2 rounded-2xl bg-slate-900/90 text-white border border-slate-700/80 px-4 py-2.5 backdrop-blur-md">
                            <Share2 className="w-4 h-4 text-cyan-400" />
                            <span className="text-xs font-medium">Partage sécurisé inter-services</span>
                        </div>
                    </div>
                </Reveal>
            </div>

            {/* Content Column */}
            <div className="lg:col-span-6">
                <Reveal direction="up" delay={150}>
    

                    <h2 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight leading-tight mb-5">
                        Fédérez vos équipes autour d’un patrimoine documentaire unifié.
                    </h2>

                    <p className="text-base text-slate-600 leading-relaxed mb-8">
                        La gestion documentaire ne doit plus être un frein bureaucratique, mais le moteur de votre productivité collective. APPGED permet à chaque collaborateur d’accéder instantanément aux documents dont il a besoin, dans le respect strict des habilitations.
                    </p>

                    <div className="space-y-5 mb-8">
                        {pillars.map((pillar, idx) => (
                            <div key={pillar.title} className="flex items-start gap-3.5">
                                <div className="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                    <CheckCircle2 className="h-4 w-4 stroke-[2.5]" />
                                </div>
                                <div>
                                    <h3 className="text-sm font-bold text-slate-900">{pillar.title}</h3>
                                    <p className="text-xs text-slate-600 leading-relaxed mt-0.5">{pillar.desc}</p>
                                </div>
                            </div>
                        ))}
                    </div>

                    <div className="flex flex-wrap items-center gap-4 pt-2">
                        <Link
                            href="/fonctionnalites"
                            className="inline-flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-700 group"
                        >
                            <span>Découvrir les fonctions de partage et collaboration</span>
                            <ArrowRight className="w-4 h-4 transition-transform group-hover:translate-x-1" />
                        </Link>
                    </div>
                </Reveal>
            </div>
        </div>
    );
}
