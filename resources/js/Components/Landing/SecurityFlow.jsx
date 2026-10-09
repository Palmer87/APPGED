import React, { useState } from 'react';
import { Ban, Building2, CheckCircle2, FileClock, KeyRound, ScrollText, ShieldCheck, UserRound } from 'lucide-react';
import { useInView, useStepSequence } from './useLandingMotion';

const scenarios = {
    granted: {
        label: 'Utilisateur autorisé',
        user: 'Comptable · Direction Financière',
        outcome: 'Accès autorisé',
        log: 'Consultation du document enregistrée',
    },
    denied: {
        label: 'Utilisateur non autorisé',
        user: 'Agent · Ressources Humaines',
        outcome: 'Accès refusé',
        log: 'Tentative refusée enregistrée',
    },
};

/**
 * Animated access-control pipeline with a toggle between an authorised and a
 * refused request, followed by an organisation isolation illustration.
 */
export default function SecurityFlow() {
    const [scenarioKey, setScenarioKey] = useState('granted');
    const [flowRef, isInView] = useInView({ threshold: 0.35 });
    const { currentStep, replay } = useStepSequence(isInView, [500, 700, 700, 700, 700]);
    const scenario = scenarios[scenarioKey];
    const isGranted = scenarioKey === 'granted';

    const nodes = [
        { icon: UserRound, title: 'Utilisateur', detail: scenario.user },
        { icon: KeyRound, title: 'Authentification', detail: 'Session ou jeton vérifié' },
        { icon: ShieldCheck, title: 'Permissions', detail: 'Rôle et périmètre contrôlés' },
        {
            icon: isGranted ? CheckCircle2 : Ban,
            title: scenario.outcome,
            detail: isGranted ? 'Document accessible' : 'Document non accessible',
            isOutcome: true,
        },
        { icon: ScrollText, title: 'Traçabilité', detail: scenario.log },
    ];

    const selectScenario = (key) => {
        setScenarioKey(key);
        replay();
    };

    return (
        <div className="space-y-6">
            <div ref={flowRef} className="rounded-3xl border border-white/10 bg-white/[0.03] p-5 sm:p-8">
                <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
                    <div role="group" aria-label="Choisir un scénario" className="inline-flex rounded-full border border-white/10 bg-night p-1">
                        {Object.entries(scenarios).map(([key, item]) => (
                            <button
                                key={key}
                                type="button"
                                onClick={() => selectScenario(key)}
                                aria-pressed={scenarioKey === key}
                                className={`rounded-full px-4 py-2 text-xs font-semibold transition sm:text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 ${
                                    scenarioKey === key ? 'bg-white text-slate-900' : 'text-slate-400 hover:text-white'
                                }`}
                            >
                                {item.label}
                            </button>
                        ))}
                    </div>
                  
                </div>

                <ol className="relative grid gap-4 md:grid-cols-5 md:gap-3">
                    {/* Progress line (desktop) */}
                    <span className="absolute left-[10%] right-[10%] top-7 hidden h-px bg-white/10 md:block" aria-hidden="true" />
                    <span
                        className="lp-move absolute left-[10%] top-7 hidden h-px w-[80%] origin-left bg-blue-500 md:block"
                        style={{ transform: `scaleX(${Math.min(Math.max(currentStep - 1, 0) / 4, 1)})` }}
                        aria-hidden="true"
                    />
                    {nodes.map((node, index) => {
                        const Icon = node.icon;
                        const isLit = currentStep > index;
                        const outcomeTone = isGranted
                            ? 'bg-emerald-600'
                            : 'bg-rose-600';
                        return (
                            <li key={node.title + index} className="relative flex items-center gap-4 md:flex-col md:text-center">
                                <span
                                    className={`relative z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border transition-all duration-500 ${
                                        isLit
                                            ? node.isOutcome
                                                ? `border-transparent text-white ${outcomeTone}`
                                                : 'border-blue-400/40 bg-blue-500/15 text-blue-200'
                                            : 'border-white/10 bg-night text-slate-600'
                                    }`}
                                >
                                    <Icon className="h-6 w-6" aria-hidden="true" />
                                </span>
                                <div className={`transition-opacity duration-500 ${isLit ? 'opacity-100' : 'opacity-40'}`}>
                                    <p className="text-sm font-semibold text-white">{node.title}</p>
                                    <p className="mt-0.5 text-xs leading-relaxed text-slate-400">{node.detail}</p>
                                </div>
                            </li>
                        );
                    })}
                </ol>
                <p className="sr-only" aria-live="polite">
                    Scénario : {scenario.label}. Résultat : {scenario.outcome}. {scenario.log}.
                </p>
            </div>

            {/* Organisation isolation & Architecture illustration */}
            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                {/* Visual 3D Security Architecture */}
                <div className="lg:col-span-5 rounded-3xl border border-white/10 bg-white/[0.03] p-5 flex flex-col justify-between overflow-hidden relative group">
                    <div className="relative rounded-2xl overflow-hidden border border-white/10 aspect-[16/10] bg-night">
                        <img
                            src="/images/landing/security-architecture.jpg"
                            alt="Architecture de sécurité et d'isolation des documents APPGED"
                            className="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-700"
                            loading="lazy"
                        />
                        <span className="absolute bottom-3 left-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-night/80 text-cyan-300 border border-white/15 backdrop-blur-md">
                            <ShieldCheck className="w-3.5 h-3.5 text-cyan-400" />
                            Protection en couches
                        </span>
                    </div>
                    <div className="mt-4">
                        <h4 className="text-sm font-bold text-white mb-1">
                            Architecture de contrôle d'accès
                        </h4>
                        <p className="text-xs text-slate-400 leading-relaxed">
                            Chaque document est chiffré et soumis à une vérification stricte des permissions avant tout accès ou téléchargement.
                        </p>
                    </div>
                </div>

                {/* Organisation isolation diagram */}
                <div className="lg:col-span-7 rounded-3xl border border-white/10 bg-white/[0.03] p-5 flex flex-col justify-center">
                    <p className="text-xs font-bold uppercase tracking-wider text-cyan-400 mb-4 flex items-center gap-2">
                        <Building2 className="w-3.5 h-3.5" />
                        Isolation stricte des organisations
                    </p>
                    <div className="grid items-stretch gap-4 md:grid-cols-[1fr_auto_1fr]">
                        {['Organisation A', 'Organisation B'].map((organization, index) => (
                            <React.Fragment key={organization}>
                                {index === 1 && (
                                    <div className="flex items-center justify-center" aria-hidden="true">
                                        <div className="flex items-center gap-2 rounded-full border border-rose-400/30 bg-rose-500/10 px-3 py-1.5 text-xs font-semibold text-rose-300 md:flex-col md:rounded-2xl md:px-2 md:py-4">
                                            <Ban className="h-4 w-4" />
                                            <span className="md:[writing-mode:vertical-rl]">Cloisonnement</span>
                                        </div>
                                    </div>
                                )}
                                <div className="rounded-2xl border border-white/10 bg-night/80 p-4">
                                    <p className="mb-3 flex items-center gap-2 text-xs font-semibold text-white">
                                        <Building2 className={`h-3.5 w-3.5 ${index === 0 ? 'text-blue-400' : 'text-cyan-400'}`} aria-hidden="true" />
                                        {organization}
                                    </p>
                                    <ul className="space-y-2" aria-label={`Espace de l’${organization}`}>
                                        {['Utilisateurs et rôles', 'Documents et versions', 'Journal d’activité'].map((item) => (
                                            <li key={item} className="flex items-center gap-2 rounded-lg bg-white/5 px-2.5 py-2 text-[11px] text-slate-300 ring-1 ring-white/5">
                                                <FileClock className="h-3 w-3 text-slate-500" aria-hidden="true" />
                                                {item}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </React.Fragment>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
