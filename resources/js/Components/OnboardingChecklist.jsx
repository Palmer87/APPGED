import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    Circle,
    ArrowRight,
    X,
    Building2,
    Users,
    FileText,
    UploadCloud,
    FolderKanban,
    Sparkles
} from 'lucide-react';

export default function OnboardingChecklist({ initialDismissed = false }) {
    const [dismissed, setDismissed] = useState(initialDismissed);

    if (dismissed) {
        return null;
    }

    const handleDismiss = () => {
        setDismissed(true);
        router.post('/dashboard/onboarding/dismiss', {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const steps = [
        {
            id: 1,
            title: 'Organisation créée',
            description: 'Votre compte administrateur et votre espace SaaS sont opérationnels.',
            done: true,
            icon: Building2,
            actionLabel: 'Validé',
            actionHref: null,
        },
        {
            id: 2,
            title: 'Ajouter vos Directions',
            description: 'Structurez les pôles de votre entreprise (RH, Finance, Juridique...).',
            done: false,
            icon: FolderKanban,
            actionLabel: 'Configurer les directions',
            actionHref: '/directions',
        },
        {
            id: 3,
            title: 'Ajouter vos Services',
            description: 'Rattachez vos services opérationnels à chaque direction.',
            done: false,
            icon: Users,
            actionLabel: 'Créer un service',
            actionHref: '/services',
        },
        {
            id: 4,
            title: 'Créer vos Types documentaires',
            description: 'Définissez les modèles (Factures, Contrats, Bulletins de paie).',
            done: false,
            icon: FileText,
            actionLabel: 'Ajouter un type',
            actionHref: '/document-types',
        },
        {
            id: 5,
            title: 'Importer votre premier document',
            description: 'Indexation sécurisée, OCR automatique et stockage souverain.',
            done: false,
            icon: UploadCloud,
            actionLabel: 'Importer un document',
            actionHref: '/documents/create',
        },
    ];

    return (
        <div className="relative overflow-hidden bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-3xl p-6 md:p-8 text-white shadow-xl shadow-blue-950/20 mb-8 border border-blue-700/40">
            {/* Background ambient lighting */}
            <div className="absolute top-0 right-0 -mt-12 -mr-12 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl pointer-events-none" />
            <div className="absolute bottom-0 left-1/3 -mb-16 w-80 h-80 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />

            {/* Header with Title and Dismiss */}
            <div className="relative z-10 flex flex-col md:flex-row md:items-start justify-between gap-4 pb-6 border-b border-white/10">
                <div className="flex items-start gap-4">
                    <div className="w-12 h-12 rounded-2xl bg-blue-500/20 border border-blue-400/30 flex items-center justify-center shrink-0 text-blue-300">
                        <Sparkles className="w-6 h-6" />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="text-xs uppercase font-extrabold tracking-wider px-2.5 py-0.5 rounded-full bg-blue-500/30 text-blue-200 border border-blue-400/30">
                                Démarrage rapide
                            </span>
                            <span className="text-xs text-blue-200/80">Essai 14 jours activé</span>
                        </div>
                        <h2 className="text-xl md:text-2xl font-bold tracking-tight text-white mt-1">
                            Bienvenue sur GEDAPP 👋 — Votre organisation est prête
                        </h2>
                        <p className="text-sm text-slate-300 mt-1 max-w-2xl">
                            Suivez ces quelques étapes pour configurer la gestion documentaire de votre entreprise. Vous pouvez explorer votre espace à votre rythme.
                        </p>
                    </div>
                </div>

                <div className="flex items-center gap-2 self-end md:self-auto shrink-0">
                    <button
                        type="button"
                        onClick={handleDismiss}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-slate-300 hover:text-white hover:bg-white/10 transition border border-white/10"
                        title="Masquer le guide de démarrage"
                    >
                        <span>Masquer</span>
                        <X className="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            {/* Steps Timeline Grid */}
            <div className="relative z-10 grid grid-cols-1 md:grid-cols-5 gap-4 mt-6">
                {steps.map((step) => {
                    const IconComponent = step.icon;
                    return (
                        <div
                            key={step.id}
                            className={`rounded-2xl p-4 transition-all duration-200 flex flex-col justify-between border ${
                                step.done
                                    ? 'bg-emerald-950/40 border-emerald-500/30 text-emerald-100'
                                    : 'bg-white/5 border-white/10 hover:border-blue-400/40 hover:bg-white/10'
                            }`}
                        >
                            <div>
                                <div className="flex items-center justify-between mb-3">
                                    <span className="w-8 h-8 rounded-xl flex items-center justify-center text-xs font-bold bg-white/10">
                                        <IconComponent className="w-4 h-4" />
                                    </span>
                                    {step.done ? (
                                        <span className="flex items-center gap-1 text-[11px] font-bold text-emerald-400">
                                            <CheckCircle2 className="w-4 h-4 fill-emerald-500/20 text-emerald-400" />
                                            <span>Fait</span>
                                        </span>
                                    ) : (
                                        <Circle className="w-4 h-4 text-slate-400" />
                                    )}
                                </div>

                                <h3 className="font-semibold text-sm text-white mb-1">
                                    {step.title}
                                </h3>
                                <p className="text-xs text-slate-300 leading-relaxed mb-4">
                                    {step.description}
                                </p>
                            </div>

                            {step.actionHref ? (
                                <Link
                                    href={step.actionHref}
                                    className="w-full inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl text-xs font-semibold bg-white/10 hover:bg-blue-600 hover:text-white text-slate-200 transition shadow-xs"
                                >
                                    <span>{step.actionLabel}</span>
                                    <ArrowRight className="w-3 h-3" />
                                </Link>
                            ) : (
                                <div className="text-[11px] font-medium text-emerald-300/80 text-center py-1">
                                    ✓ Étape complétée
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Quick Action Footer CTAs */}
            <div className="relative z-10 flex flex-wrap items-center justify-between gap-4 mt-6 pt-6 border-t border-white/10">
                <div className="flex items-center gap-2 text-xs text-slate-300">
                    <span className="w-2 h-2 rounded-full bg-emerald-400 animate-pulse" />
                    <span>Progression de l'onboarding : <strong>20%</strong> (1 / 5 étapes)</span>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <Link
                        href="/directions"
                        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-white text-slate-900 hover:bg-slate-100 transition shadow-sm"
                    >
                        <span>Configurer mon organisation</span>
                        <ArrowRight className="w-3.5 h-3.5" />
                    </Link>
                    <Link
                        href="/documents/create"
                        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-500 text-white transition shadow-sm"
                    >
                        <UploadCloud className="w-3.5 h-3.5" />
                        <span>Importer mon premier document</span>
                    </Link>
                </div>
            </div>
        </div>
    );
}
