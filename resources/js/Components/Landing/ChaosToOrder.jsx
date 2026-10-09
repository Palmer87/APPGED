import React from 'react';
import { CheckCircle2, Copy, FileQuestion, FileText, RotateCcw, Search, ShieldCheck } from 'lucide-react';
import { useInView, useStepSequence } from './useLandingMotion';

/**
 * Each document has a scattered "chaos" position and an "order" slot.
 * Positions are percentages of the stage (wrappers fill the stage, so
 * translate(%) is relative to the stage size).
 */
const documents = [
    { id: 1, name: 'Facture_0142.pdf', meta: 'Fournisseur · 2026', column: 0, row: 0, chaos: [6, 17, -8], duplicate: false },
    { id: 2, name: 'Releve_fev.pdf', meta: 'Banque · Février', column: 0, row: 1, chaos: [58, 62, 6] },
    { id: 3, name: 'Budget_2026.xlsx', meta: 'Exercice · 2026', column: 0, row: 2, chaos: [36, 30, -4] },
    { id: 4, name: 'Contrat_K_Diallo.pdf', meta: 'CDI · Agent', column: 1, row: 0, chaos: [66, 6, 9], duplicate: true },
    { id: 5, name: 'Conges_mars.docx', meta: 'Demande · Mars', column: 1, row: 1, chaos: [8, 58, 5] },
    { id: 6, name: 'Fiche_poste.pdf', meta: 'Poste · Comptable', column: 1, row: 2, chaos: [30, 70, -10], unknown: true },
    { id: 7, name: 'Statuts_societe.pdf', meta: 'Statuts · Version 2', column: 2, row: 0, chaos: [44, 4, 3] },
    { id: 8, name: 'Bail_bureaux.pdf', meta: 'Bail · Siège', column: 2, row: 1, chaos: [67, 36, -6], duplicate: true },
    { id: 9, name: 'PV_AG_2026.pdf', meta: 'Assemblée · 2026', column: 2, row: 2, chaos: [14, 34, 11] },
];

const columns = ['Direction Financière', 'Ressources Humaines', 'Direction Juridique'];
const targetDocumentId = 1;

const steps = [
    { label: 'Avant', description: 'Des fichiers dispersés, des doublons, des noms peu explicites.' },
    { label: 'Classement', description: 'Chaque document est rattaché à sa Direction, son Service et son type documentaire.' },
    { label: 'Métadonnées', description: 'Les métadonnées renseignées décrivent chaque document.' },
    { label: 'Recherche', description: 'Une recherche par critères retrouve le bon document.' },
    { label: 'Accès', description: 'L’accès est accordé selon les droits de l’utilisateur.' },
];

const hierarchy = ['Organisation', 'Direction', 'Service', 'Type documentaire', 'Document'];

export default function ChaosToOrder() {
    const [stageRef, isInView] = useInView({ threshold: 0.35 });
    const { currentStep, replay } = useStepSequence(isInView, [1600, 1500, 1500, 1400]);

    const isOrdered = currentStep >= 1;
    const showMetadata = currentStep >= 2;
    const isSearching = currentStep >= 3;
    const isGranted = currentStep >= 4;

    const positionFor = (document) => {
        if (!isOrdered) {
            const [x, y, rotation] = document.chaos;
            return `translate3d(${x}%, ${y}%, 0) rotate(${rotation}deg)`;
        }
        const x = 1.5 + document.column * 33.5;
        const y = 15 + document.row * 22;
        return `translate3d(${x}%, ${y}%, 0)`;
    };

    return (
        <div className="grid items-center gap-10 lg:grid-cols-12 lg:gap-14">
            {/* Steps */}
            <div className="lg:col-span-4">
                <ol className="space-y-2" aria-label="Étapes de la transformation">
                    {steps.map((step, index) => {
                        const isActive = index === Math.min(currentStep, steps.length - 1);
                        const isDone = index < currentStep;
                        return (
                            <li
                                key={step.label}
                                className={`flex gap-4 rounded-2xl border p-4 transition-colors duration-500 ${
                                    isActive ? 'border-blue-200 bg-white' : 'border-transparent'
                                }`}
                                aria-current={isActive ? 'step' : undefined}
                            >
                                <span
                                    className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold transition-colors duration-500 ${
                                        isActive
                                            ? 'bg-blue-600 text-white'
                                            : isDone
                                              ? 'bg-blue-100 text-blue-700'
                                              : 'bg-slate-100 text-slate-400'
                                    }`}
                                >
                                    {isDone ? <CheckCircle2 className="h-4 w-4" aria-hidden="true" /> : index + 1}
                                </span>
                                <div>
                                    <p className={`text-sm font-semibold ${isActive || isDone ? 'text-slate-900' : 'text-slate-400'}`}>{step.label}</p>
                                    <p className={`mt-0.5 text-sm leading-relaxed ${isActive ? 'text-ink' : 'text-slate-400'}`}>{step.description}</p>
                                </div>
                            </li>
                        );
                    })}
                </ol>
                <button
                    type="button"
                    onClick={replay}
                    className="mt-4 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                >
                    <RotateCcw className="h-4 w-4" aria-hidden="true" /> Rejouer l’animation
                </button>
                <p className="sr-only" aria-live="polite">
                    {steps[Math.min(currentStep, steps.length - 1)].description}
                </p>
            </div>

            {/* Stage */}
            <div className="lg:col-span-8">
                <div
                    ref={stageRef}
                    className="relative h-[400px] overflow-hidden rounded-3xl border border-slate-200 bg-slate-50/40 sm:h-[440px]"
                    aria-hidden="true"
                >

                    {/* Chaos label */}
                    <div className={`absolute left-4 top-4 transition-opacity duration-500 ${isOrdered ? 'opacity-0' : 'opacity-100'}`}>
                        <span className="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600 ring-1 ring-rose-200">Avant APPGED</span>
                    </div>

                    {/* Column headers */}
                    {columns.map((column, index) => (
                        <div
                            key={column}
                            className="lp-move absolute top-0 w-[31%]"
                            style={{
                                left: `${1.5 + index * 33.5}%`,
                                transform: `translate3d(0, ${isOrdered ? 16 : 0}px, 0)`,
                                opacity: isOrdered ? 1 : 0,
                                transitionDelay: `${index * 120}ms`,
                            }}
                        >
                            <div className="rounded-xl bg-night px-2.5 py-2 text-center text-[10.5px] font-semibold text-white sm:text-xs">
                                <span className="block truncate">{column}</span>
                            </div>
                        </div>
                    ))}

                    {/* Documents */}
                    {documents.map((document, index) => {
                        const isTarget = document.id === targetDocumentId;
                        const isDimmed = isSearching && !isTarget;
                        return (
                            <div
                                key={document.id}
                                className="lp-move pointer-events-none absolute inset-0"
                                style={{ transform: positionFor(document), transitionDelay: `${index * 60}ms` }}
                            >
                                <div
                                    className={`w-[31%] rounded-xl border bg-white px-2.5 py-2 transition-all duration-500 ${
                                        isTarget && isSearching
                                            ? 'border-blue-400 ring-4 ring-blue-500/15'
                                            : 'border-slate-200'
                                    } ${isDimmed ? 'opacity-35' : 'opacity-100'}`}
                                >
                                    <div className="flex items-center gap-2">
                                        <span
                                            className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-lg ${
                                                !isOrdered && document.unknown
                                                    ? 'bg-amber-50 text-amber-500'
                                                    : 'bg-blue-50 text-blue-600'
                                            }`}
                                        >
                                            {!isOrdered && document.unknown ? <FileQuestion className="h-3.5 w-3.5" /> : <FileText className="h-3.5 w-3.5" />}
                                        </span>
                                        <span className="min-w-0 flex-1 truncate text-[10.5px] font-medium text-slate-800 sm:text-xs">{document.name}</span>
                                        {!isOrdered && document.duplicate && (
                                            <span className="hidden shrink-0 items-center gap-0.5 rounded bg-rose-50 px-1 py-0.5 text-[9px] font-semibold text-rose-500 sm:inline-flex">
                                                <Copy className="h-2.5 w-2.5" /> doublon
                                            </span>
                                        )}
                                    </div>
                                    <div
                                        className={`grid transition-[grid-template-rows,opacity] duration-500 ${
                                            showMetadata ? 'mt-1.5 grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'
                                        }`}
                                    >
                                        <span className="overflow-hidden truncate rounded-md bg-cyan-50 px-1.5 py-0.5 text-[9.5px] font-medium text-cyan-700 sm:text-[10px]">
                                            {document.meta}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        );
                    })}

                    {/* Search + access overlay */}
                    <div
                        className="lp-move absolute inset-x-4 bottom-4 flex flex-col gap-2 sm:flex-row sm:items-center"
                        style={{ opacity: isSearching ? 1 : 0, transform: `translate3d(0, ${isSearching ? 0 : 16}px, 0)` }}
                    >
                        <div className="flex flex-1 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs text-slate-600">
                            <Search className="h-4 w-4 text-blue-600" />
                            <span className="truncate">Type : Facture · Fournisseur · 2026</span>
                        </div>
                        <div
                            className={`flex items-center gap-2 rounded-xl px-3 py-2.5 text-xs font-semibold transition-all duration-500 ${
                                isGranted ? 'bg-emerald-600 text-white opacity-100' : 'bg-white text-slate-400 opacity-0'
                            }`}
                        >
                            <ShieldCheck className="h-4 w-4" />
                            Accès autorisé selon vos droits
                        </div>
                    </div>
                </div>

                {/* Hierarchy chain */}
                <div className="mt-5 flex flex-wrap items-center justify-center gap-x-2 gap-y-2 text-xs font-medium sm:text-sm">
                    {hierarchy.map((level, index) => {
                        const isLit = isOrdered && (currentStep >= 2 || index < 4);
                        return (
                            <React.Fragment key={level}>
                                <span
                                    className={`rounded-full px-3 py-1.5 transition-colors duration-500 ${
                                        isLit ? 'bg-night text-white' : 'bg-slate-100 text-slate-400'
                                    }`}
                                    style={{ transitionDelay: `${index * 120}ms` }}
                                >
                                    {level}
                                </span>
                                {index < hierarchy.length - 1 && <span className="text-slate-300" aria-hidden="true">→</span>}
                            </React.Fragment>
                        );
                    })}
                </div>
              
            </div>
        </div>
    );
}
