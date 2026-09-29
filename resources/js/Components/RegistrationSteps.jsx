import React from 'react';
import { Building2, User, Sparkles, Check, CheckCircle2 } from 'lucide-react';

export default function RegistrationSteps({ currentStep = 1 }) {
    const steps = [
        {
            number: 1,
            title: 'Organisation',
            subtitle: 'Votre entreprise',
            icon: Building2,
        },
        {
            number: 2,
            title: 'Administrateur',
            subtitle: 'Compte principal',
            icon: User,
        },
        {
            number: 3,
            title: 'Formule & Essai',
            subtitle: '14 jours gratuits',
            icon: Sparkles,
        },
        {
            number: 4,
            title: 'Confirmation',
            subtitle: 'Accès espace GED',
            icon: CheckCircle2,
        },
    ];

    return (
        <div className="w-full">
            {/* Desktop Stepper */}
            <div className="hidden sm:grid sm:grid-cols-4 gap-3">
                {steps.map((step) => {
                    const isCompleted = step.number < currentStep;
                    const isCurrent = step.number === currentStep;
                    const Icon = step.icon;

                    return (
                        <div
                            key={step.number}
                            className={`relative flex items-center gap-3 p-3 rounded-2xl border transition-all duration-200 ${
                                isCurrent
                                    ? 'bg-blue-50/70 border-blue-500 shadow-sm shadow-blue-500/10'
                                    : isCompleted
                                    ? 'bg-white border-emerald-300'
                                    : 'bg-white/60 border-slate-200 text-slate-400'
                            }`}
                        >
                            <div
                                className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 font-bold text-xs transition-colors ${
                                    isCompleted
                                        ? 'bg-emerald-600 text-white'
                                        : isCurrent
                                        ? 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
                                        : 'bg-slate-100 text-slate-400'
                                }`}
                            >
                                {isCompleted ? (
                                    <Check className="w-4 h-4 stroke-[3]" />
                                ) : (
                                    <span>{step.number}</span>
                                )}
                            </div>
                            <div className="min-w-0">
                                <div
                                    className={`text-xs font-bold truncate leading-tight ${
                                        isCurrent
                                            ? 'text-blue-950'
                                            : isCompleted
                                            ? 'text-slate-900'
                                            : 'text-slate-400'
                                    }`}
                                >
                                    {step.title}
                                </div>
                                <div
                                    className={`text-[10px] truncate mt-0.5 ${
                                        isCurrent
                                            ? 'text-blue-700 font-medium'
                                            : isCompleted
                                            ? 'text-emerald-700 font-medium'
                                            : 'text-slate-400'
                                    }`}
                                >
                                    {isCompleted ? 'Complété' : step.subtitle}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Mobile Compact Progress Bar */}
            <div className="sm:hidden bg-white p-3.5 rounded-2xl border border-slate-200 shadow-2xs">
                <div className="flex items-center justify-between text-xs mb-2">
                    <span className="font-bold text-slate-900 flex items-center gap-1.5">
                        <span className="w-5 h-5 rounded-md bg-blue-600 text-white text-[11px] font-black flex items-center justify-center">
                            {currentStep}
                        </span>
                        <span>{steps[currentStep - 1]?.title || 'Inscription'}</span>
                    </span>
                    <span className="text-[11px] text-slate-500 font-medium">
                        Étape {currentStep} sur 4
                    </span>
                </div>
                {/* Progress bar line */}
                <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                    <div
                        className="bg-blue-600 h-full rounded-full transition-all duration-300"
                        style={{ width: `${(currentStep / 4) * 100}%` }}
                    />
                </div>
            </div>
        </div>
    );
}
