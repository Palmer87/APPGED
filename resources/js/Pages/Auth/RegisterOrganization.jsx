import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    Building2,
    Briefcase,
    Globe2,
    MapPin,
    ArrowRight,
    ShieldCheck,
    CheckCircle2,
    FolderSync,
    Users2,
    Sparkles,
    AlertCircle
} from 'lucide-react';
import RegistrationSteps from '../../Components/RegistrationSteps';

export default function RegisterOrganization({ organization = {}, isInscriptionFlow = false }) {
    const { flash } = usePage().props;

    const { data, setData, post, processing, errors } = useForm({
        name: organization?.name || '',
        activity: organization?.activity || '',
        country: organization?.country || '',
        city: organization?.city || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        const targetUrl = isInscriptionFlow ? '/inscription/organisation' : '/register/organization';
        post(targetUrl);
    };

    return (
        <div className="min-h-screen w-full flex bg-slate-50 text-slate-900 font-sans antialiased">
            <Head title="Créer votre organisation — Inscription GEDAPP" />

            {/* ZONE GAUCHE (Desktop >= lg) : Identité GEDAPP & Réassurance SaaS */}
            <div className="hidden lg:flex lg:w-1/2 xl:w-5/12 flex-col justify-between bg-slate-950 p-10 xl:p-14 text-white relative overflow-hidden border-r border-slate-800">
                {/* Subtle background glow */}
                <div className="absolute inset-0 opacity-15 pointer-events-none">
                    <div className="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-blue-600/30 blur-3xl" />
                    <div className="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-indigo-600/20 blur-3xl" />
                </div>

                {/* Logo & Identity */}
                <div className="relative z-10">
                    <Link href="/" className="inline-flex items-center gap-3 group">
                        <div className="w-11 h-11 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-lg tracking-wider shadow-lg shadow-blue-500/30 group-hover:scale-105 transition-transform">
                            GED
                        </div>
                        <div>
                            <span className="font-bold text-xl tracking-tight text-white">
                                GED<span className="text-blue-400">APP</span>
                            </span>
                            <span className="block text-[11px] font-medium text-slate-400 uppercase tracking-widest">
                                Espace Entreprise Sécurisé
                            </span>
                        </div>
                    </Link>
                </div>

                {/* Value proposition */}
                <div className="relative z-10 my-auto py-10 max-w-lg">
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-blue-950/70 border border-blue-800/50 text-blue-300 text-xs font-semibold mb-6">
                        <Sparkles className="w-4 h-4 text-blue-400" />
                        Essai gratuit de 14 jours • Sans carte bancaire
                    </div>

                    <h1 className="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight mb-4">
                        Créez l'espace documentaire de votre organisation.
                    </h1>

                    <p className="text-slate-400 text-sm xl:text-base leading-relaxed mb-8">
                        Centralisez vos documents d'entreprise, gérez vos directions et collaborez en toute sérénité dès aujourd'hui.
                    </p>

                    <div className="space-y-4">
                        <div className="flex items-start gap-3.5 p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs">
                            <div className="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
                                <Building2 className="w-4 h-4" />
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold text-slate-200">
                                    Cloisonnement multi-tenant garanti
                                </h2>
                                <p className="text-xs text-slate-400 leading-normal mt-0.5">
                                    Vos données sont hermétiquement isolées et protégées sous le contrôle strict de votre organisation.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3.5 p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs">
                            <div className="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center shrink-0 mt-0.5">
                                <FolderSync className="w-4 h-4" />
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold text-slate-200">
                                    Prêt en 2 minutes
                                </h2>
                                <p className="text-xs text-slate-400 leading-normal mt-0.5">
                                    Arborescence par défaut, types documentaires et métadonnées configurés dès la validation.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Footer badge */}
                <div className="relative z-10 pt-6 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
                    <div className="flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                        <span>Hébergement sécurisé & conforme</span>
                    </div>
                    <span>Support dédié 7j/7</span>
                </div>
            </div>

            {/* ZONE DROITE : Formulaire Étape 1 */}
            <div className="flex-1 flex flex-col justify-center items-center px-4 sm:px-8 lg:px-12 py-10 relative">
                <div className="w-full max-w-xl space-y-6">
                    {/* Stepper Header */}
                    <RegistrationSteps currentStep={1} />

                    {/* Step Title */}
                    <div className="text-left">
                        <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold mb-2">
                            <Building2 className="w-3.5 h-3.5 text-blue-600" />
                            Étape 1 sur 3
                        </div>
                        <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-950">
                            Votre organisation
                        </h2>
                        <p className="mt-1 text-xs sm:text-sm text-slate-600">
                            Renseignez les détails de votre entreprise ou institution pour initialiser votre espace.
                        </p>
                    </div>

                    {/* Flash messages */}
                    {flash?.error && (
                        <div className="rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 flex items-start gap-3 text-rose-800 text-xs leading-relaxed">
                            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                            <span>{flash.error}</span>
                        </div>
                    )}

                    {/* Form Card */}
                    <div className="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8">
                        <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                            {/* Nom de l'organisation */}
                            <div>
                                <label
                                    htmlFor="name"
                                    className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                >
                                    Nom de l'organisation <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative rounded-xl shadow-2xs">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                        <Building2 className="w-4 h-4" />
                                    </div>
                                    <input
                                        id="name"
                                        name="name"
                                        type="text"
                                        required
                                        autoFocus
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Ex: Entreprise ABC, Cabinet Kouassi & Associés"
                                        className={`w-full rounded-xl border pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                                            errors.name
                                                ? 'border-rose-400 bg-rose-50/20 focus:ring-rose-400'
                                                : 'border-slate-300 bg-white hover:border-slate-400'
                                        }`}
                                    />
                                </div>
                                {errors.name && (
                                    <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.name}
                                    </p>
                                )}
                            </div>

                            {/* Secteur d'activité */}
                            <div>
                                <label
                                    htmlFor="activity"
                                    className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                >
                                    Secteur d'activité <span className="text-slate-400 font-normal lowercase">(optionnel)</span>
                                </label>
                                <div className="relative rounded-xl shadow-2xs">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                        <Briefcase className="w-4 h-4" />
                                    </div>
                                    <input
                                        id="activity"
                                        name="activity"
                                        type="text"
                                        value={data.activity}
                                        onChange={(e) => setData('activity', e.target.value)}
                                        placeholder="Ex: Comptabilité, Conseil, Commerce, Santé, BTP, Droit..."
                                        className="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                    />
                                </div>
                                {errors.activity && (
                                    <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.activity}
                                    </p>
                                )}
                            </div>

                            {/* Pays & Ville (2 colonnes) */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        htmlFor="country"
                                        className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                    >
                                        Pays <span className="text-slate-400 font-normal lowercase">(optionnel)</span>
                                    </label>
                                    <div className="relative rounded-xl shadow-2xs">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <Globe2 className="w-4 h-4" />
                                        </div>
                                        <input
                                            id="country"
                                            name="country"
                                            type="text"
                                            value={data.country}
                                            onChange={(e) => setData('country', e.target.value)}
                                            placeholder="Ex: Côte d'Ivoire, Sénégal, France..."
                                            className="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                        />
                                    </div>
                                    {errors.country && (
                                        <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                            {errors.country}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="city"
                                        className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                    >
                                        Ville <span className="text-slate-400 font-normal lowercase">(optionnel)</span>
                                    </label>
                                    <div className="relative rounded-xl shadow-2xs">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <MapPin className="w-4 h-4" />
                                        </div>
                                        <input
                                            id="city"
                                            name="city"
                                            type="text"
                                            value={data.city}
                                            onChange={(e) => setData('city', e.target.value)}
                                            placeholder="Ex: Abidjan, Dakar, Paris..."
                                            className="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                        />
                                    </div>
                                    {errors.city && (
                                        <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                            {errors.city}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Submit CTA */}
                            <div className="pt-3">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full inline-flex items-center justify-center gap-2 font-bold rounded-full px-6 py-3.5 text-sm text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-md shadow-blue-500/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed transition-all cursor-pointer"
                                >
                                    {processing ? (
                                        <span>Validation...</span>
                                    ) : (
                                        <>
                                            <span>Continuer vers l'administrateur</span>
                                            <ArrowRight className="w-4 h-4" />
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Bottom link: Already have an account? */}
                    <div className="text-center pt-2 text-xs text-slate-500">
                        <span>Vous avez déjà un compte ? </span>
                        <Link href="/login" className="font-bold text-blue-600 hover:underline">
                            Se connecter
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
}
