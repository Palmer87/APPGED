import React, { useMemo, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowRight, Check, Minus } from 'lucide-react';
import Reveal from './Reveal';

const formatAmount = (amount, currency) => {
    const unit = !currency || currency === 'XOF' ? 'FCFA' : currency;
    return `${new Intl.NumberFormat('fr-FR').format(amount)} ${unit}`;
};


const formatStorage = (bytes) => {
    if (bytes === null || bytes === undefined) {
        return 'Stockage sur mesure';
    }
    const gigabytes = bytes / 1024 ** 3;
    return `${new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 1 }).format(gigabytes)} Go de stockage`;
};

const formatCount = (value, singular, plural, unlimitedLabel) => {
    if (value === null || value === undefined) {
        return unlimitedLabel;
    }
    return `${new Intl.NumberFormat('fr-FR').format(value)} ${value > 1 ? plural : singular}`;
};

/**
 * Builds the visible feature list strictly from the plan's stored attributes.
 *
 * @returns {{ label: string, included: boolean }[]}
 */
const featuresForPlan = (plan) => [
    { label: formatCount(plan.max_users, 'utilisateur', 'utilisateurs', 'Utilisateurs illimités'), included: true },
    { label: formatStorage(plan.max_storage_bytes), included: true },
    { label: formatCount(plan.max_directions, 'Direction', 'Directions', 'Directions illimitées'), included: true },
    { label: formatCount(plan.max_document_types, 'type documentaire', 'types documentaires', 'Types documentaires illimités'), included: true },
    {
        label:
            plan.max_ocr_pages_month === null || plan.max_ocr_pages_month === undefined
                ? 'OCR sur mesure'
                : `${new Intl.NumberFormat('fr-FR').format(plan.max_ocr_pages_month)} pages OCR / mois`,
        included: true,
    },
    { label: 'Workflows de validation', included: Boolean(plan.has_workflows) },
    { label: 'Audit avancé', included: Boolean(plan.has_advanced_audit) },
    { label: 'Accès API', included: Boolean(plan.has_api) },
    { label: plan.has_dedicated_support ? 'Support dédié' : 'Support prioritaire', included: Boolean(plan.has_dedicated_support || plan.has_priority_support) },
    ...(plan.has_sla ? [{ label: 'Engagement de service (SLA)', included: true }] : []),
    ...(plan.has_custom_migration ? [{ label: 'Accompagnement à la migration', included: true }] : []),
    ...(plan.has_custom_integrations ? [{ label: 'Intégrations sur mesure', included: true }] : []),
];

/**
 * Pricing cards rendered from the active plans passed by PublicLandingController.
 */
export default function PricingPreview({ plans = [], user }) {
    const [billingPeriod, setBillingPeriod] = useState('monthly');

    const hasAnnualPricing = useMemo(() => plans.some((plan) => plan.annual_price), [plans]);
    const maximumAnnualSavingsPercent = useMemo(() => {
        const percents = plans
            .filter((plan) => plan.monthly_price && plan.annual_price)
            .map((plan) => Math.round((1 - plan.annual_price / (plan.monthly_price * 12)) * 100))
            .filter((percent) => percent > 0);
        return percents.length ? Math.max(...percents) : 0;
    }, [plans]);

    if (plans.length === 0) {
        return (
            <div className="rounded-3xl border border-slate-200 bg-white p-10 text-center">
                <p className="text-slate-600">Les offres sont en cours de mise à jour.</p>
                <Link href="/tarifs" className="mt-4 inline-flex font-semibold text-blue-600 hover:text-blue-700">Consulter la page Tarifs</Link>
            </div>
        );
    }

    const highlightedPlanSlug = plans.length >= 3 ? plans[1].slug : null;

    return (
        <div>
            {hasAnnualPricing && (
                <div className="mb-12 flex justify-center">
                    <div role="group" aria-label="Période de facturation" className="inline-flex items-center rounded-full border border-slate-200 bg-white p-1">
                        {[
                            { value: 'monthly', label: 'Mensuel' },
                            { value: 'annual', label: 'Annuel' },
                        ].map((option) => (
                            <button
                                key={option.value}
                                type="button"
                                onClick={() => setBillingPeriod(option.value)}
                                aria-pressed={billingPeriod === option.value}
                                className={`flex items-center gap-2 rounded-full px-5 py-2 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 ${
                                    billingPeriod === option.value ? 'bg-night text-white' : 'text-slate-600 hover:text-slate-900'
                                }`}
                            >
                                {option.label}
                                {option.value === 'annual' && maximumAnnualSavingsPercent > 0 && (
                                    <span className={`rounded-full px-1.5 py-0.5 text-[10px] font-bold ${billingPeriod === 'annual' ? 'bg-white/15 text-cyan-200' : 'bg-emerald-100 text-emerald-700'}`}>
                                        jusqu’à -{maximumAnnualSavingsPercent}%
                                    </span>
                                )}
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <div className={`mx-auto grid max-w-6xl gap-6 ${plans.length >= 3 ? 'lg:grid-cols-3' : plans.length === 2 ? 'md:grid-cols-2' : ''}`}>
                {plans.map((plan, index) => {
                    const isHighlighted = plan.slug === highlightedPlanSlug;
                    const isCustom = Boolean(plan.is_custom) || !plan.monthly_price;
                    const price = billingPeriod === 'annual' && plan.annual_price ? plan.annual_price : plan.monthly_price;
                    const periodLabel = billingPeriod === 'annual' && plan.annual_price ? '/ an' : '/ mois';
                    const ctaHref = isCustom ? '/enterprise' : user ? '/settings/subscription' : '/inscription';
                    const ctaLabel = isCustom ? 'Demander une démonstration' : user ? 'Gérer mon abonnement' : 'Commencer l’essai';

                    return (
                        <Reveal
                            key={plan.id ?? plan.slug}
                            delay={index * 120}
                            className={`group relative flex flex-col rounded-3xl p-7 transition-transform duration-300 hover:-translate-y-1 sm:p-8 ${
                                isHighlighted
                                    ? 'bg-night text-white ring-1 ring-white/10'
                                    : 'border border-slate-200 bg-white'
                            }`}
                        >
                            {isHighlighted && (
                                <span className="absolute -top-3 left-8 rounded-full bg-blue-600 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-white">
                                    Recommandé
                                </span>
                            )}

                            <h3 className={`text-xl font-bold ${isHighlighted ? 'text-white' : 'text-slate-950'}`}>{plan.name}</h3>
                            {plan.description && (
                                <p className={`mt-2 min-h-[3rem] text-sm leading-relaxed ${isHighlighted ? 'text-slate-400' : 'text-ink'}`}>{plan.description}</p>
                            )}

                            <div className="mt-6 flex items-baseline gap-1.5">
                                {isCustom ? (
                                    <span className={`text-3xl font-bold tracking-tight ${isHighlighted ? 'text-white' : 'text-slate-950'}`}>Sur devis</span>
                                ) : (
                                    <>
                                        <span className={`text-3xl font-bold tracking-tight sm:text-4xl ${isHighlighted ? 'text-white' : 'text-slate-950'}`}>
                                            {formatAmount(price, plan.currency)}
                                        </span>
                                        <span className={`text-sm ${isHighlighted ? 'text-slate-400' : 'text-slate-500'}`}>{periodLabel}</span>
                                    </>
                                )}
                            </div>

                            <ul className={`mt-7 flex-1 space-y-3 border-t pt-7 text-sm ${isHighlighted ? 'border-white/10' : 'border-slate-100'}`}>
                                {featuresForPlan(plan).map((feature) => (
                                    <li key={feature.label} className={`flex items-start gap-3 ${feature.included ? '' : 'opacity-50'}`}>
                                        {feature.included ? (
                                            <Check className={`mt-0.5 h-4 w-4 shrink-0 ${isHighlighted ? 'text-cyan-300' : 'text-blue-600'}`} aria-hidden="true" />
                                        ) : (
                                            <Minus className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                                        )}
                                        <span className={isHighlighted ? 'text-slate-200' : 'text-slate-700'}>
                                            {feature.label}
                                            {!feature.included && <span className="sr-only"> (non inclus)</span>}
                                        </span>
                                    </li>
                                ))}
                            </ul>

                            <Link
                                href={ctaHref}
                                className={`mt-8 inline-flex items-center justify-center gap-2 rounded-full py-3.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 ${
                                    isHighlighted
                                        ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                        : 'border border-slate-200 text-slate-900 hover:border-blue-300 hover:bg-blue-50'
                                }`}
                            >
                                {ctaLabel}
                                <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                            </Link>
                        </Reveal>
                    );
                })}
            </div>
        </div>
    );
}
