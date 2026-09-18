import React, { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import {
    Mail,
    Lock,
    Eye,
    EyeOff,
    ShieldCheck,
    CheckCircle2,
    FolderSync,
    Users2,
    ShieldAlert,
    AlertCircle,
    X,
    Building2
} from 'lucide-react';

export default function Login() {
    const { flash } = usePage().props;
    const [showPassword, setShowPassword] = useState(false);
    const [showForgotModal, setShowForgotModal] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen w-full flex bg-slate-50 text-slate-900 font-sans antialiased">
            <Head title="Connexion — GEDAPP" />

            {/* ========================================================================= */}
            {/* ZONE GAUCHE (Desktop >= lg) : Identité GEDAPP, Promesse & Valeur SaaS    */}
            {/* ========================================================================= */}
            <div className="hidden lg:flex lg:w-1/2 xl:w-5/12 flex-col justify-between bg-slate-950 p-10 xl:p-14 text-white relative overflow-hidden border-r border-slate-800">
                {/* Subtle geometric background accents */}
                <div className="absolute inset-0 opacity-15 pointer-events-none">
                    <div className="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-indigo-600/30 blur-3xl" />
                    <div className="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-blue-600/20 blur-3xl" />
                    <svg className="absolute inset-0 w-full h-full stroke-slate-800/40" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="grid-pattern" width="32" height="32" patternUnits="userSpaceOnUse">
                                <path d="M0 32V.5H32" fill="none" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid-pattern)" />
                    </svg>
                </div>

                {/* Top: Logo & Platform Identity */}
                <div className="relative z-10">
                    <div className="flex items-center gap-3">
                        <div className="w-11 h-11 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-black text-lg tracking-wider shadow-lg shadow-indigo-500/30">
                            GED
                        </div>
                        <div>
                            <span className="font-bold text-xl tracking-tight text-white">
                                GED<span className="text-indigo-400">APP</span>
                            </span>
                            <span className="block text-[11px] font-medium text-slate-400 uppercase tracking-widest">
                                Plateforme SaaS Documentaire
                            </span>
                        </div>
                    </div>
                </div>

                {/* Middle: Value Proposition & 3 Pillars */}
                <div className="relative z-10 my-auto py-12 max-w-lg">
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-950/70 border border-indigo-800/50 text-indigo-300 text-xs font-semibold mb-6">
                        <ShieldCheck className="w-4 h-4 text-indigo-400" />
                        Environnement d'Entreprise Sécurisé
                    </div>

                    <h1 className="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight mb-4">
                        Votre espace documentaire, centralisé, sécurisé et organisé.
                    </h1>

                    <p className="text-slate-400 text-sm xl:text-base leading-relaxed mb-8">
                        Simplifiez la gestion du cycle de vie de vos fichiers professionnels, de la numérisation à la signature et l'archivage légal.
                    </p>

                    {/* 3 Benefits */}
                    <div className="space-y-4">
                        <div className="flex items-start gap-3.5 p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs">
                            <div className="w-8 h-8 rounded-lg bg-indigo-600/20 text-indigo-400 flex items-center justify-center shrink-0 mt-0.5">
                                <FolderSync className="w-4 h-4" />
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold text-slate-200">
                                    Centralisez vos documents
                                </h2>
                                <p className="text-xs text-slate-400 leading-normal mt-0.5">
                                    Arborescence unifiée, typage par métadonnées et recherche instantanée plein texte.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3.5 p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs">
                            <div className="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
                                <Users2 className="w-4 h-4" />
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold text-slate-200">
                                    Travaillez en équipe
                                </h2>
                                <p className="text-xs text-slate-400 leading-normal mt-0.5">
                                    Circuits de validation multi-étapes, gestion des versions et discussions contextualisées.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3.5 p-3 rounded-xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs">
                            <div className="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                                <ShieldCheck className="w-4 h-4" />
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold text-slate-200">
                                    Contrôlez vos accès
                                </h2>
                                <p className="text-xs text-slate-400 leading-normal mt-0.5">
                                    Cloisonnement multi-tenant hermétique, matrice de droits ACL et traçabilité d'audit complète.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom: Trust & Compliance Badge */}
                <div className="relative z-10 pt-6 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
                    <div className="flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                        <span>Chiffrement AES-256 au repos</span>
                    </div>
                    <span>Conforme RGPD & Sécurité B2B</span>
                </div>
            </div>

            {/* ========================================================================= */}
            {/* ZONE DROITE : Formulaire de Connexion                                     */}
            {/* ========================================================================= */}
            <div className="flex-1 flex flex-col justify-center items-center px-4 sm:px-8 lg:px-12 py-10 relative">
                <div className="w-full max-w-md space-y-8">
                    {/* Mobile Branding Header (< lg) */}
                    <div className="text-center lg:hidden mb-2">
                        <div className="inline-flex items-center gap-2.5 mb-3">
                            <div className="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-200">
                                GED
                            </div>
                            <span className="font-extrabold text-2xl tracking-tight text-slate-900">
                                GED<span className="text-indigo-600">APP</span>
                            </span>
                        </div>
                        <p className="text-xs font-medium text-slate-500">
                            Plateforme de gestion documentaire sécurisée
                        </p>
                    </div>

                    {/* Desktop / Global Title */}
                    <div className="text-center lg:text-left">
                        <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium mb-3">
                            <Building2 className="w-3.5 h-3.5 text-slate-400" />
                            Espace Professionnel
                        </div>
                        <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900">
                            Bienvenue
                        </h2>
                        <p className="mt-1 text-sm text-slate-600">
                            Connectez-vous à votre espace GED pour accéder à vos documents.
                        </p>
                    </div>

                    {/* Session Flash Messages */}
                    {flash?.success && (
                        <div className="rounded-xl border border-emerald-200 bg-emerald-50/80 p-3.5 flex items-start gap-3 text-emerald-800 text-xs leading-relaxed animate-in fade-in duration-200">
                            <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                            <span>{flash.success}</span>
                        </div>
                    )}

                    {flash?.error && (
                        <div className="rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 flex items-start gap-3 text-rose-800 text-xs leading-relaxed animate-in fade-in duration-200">
                            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                            <span>{flash.error}</span>
                        </div>
                    )}

                    {/* Form Card */}
                    <div className="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 sm:p-8">
                        <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                            {/* Email / Identifiant */}
                            <div>
                                <label
                                    htmlFor="email"
                                    className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                >
                                    Adresse email <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative rounded-lg shadow-2xs">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <Mail className="w-4 h-4" />
                                    </div>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        autoComplete="email"
                                        required
                                        autoFocus
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="nom@entreprise.com"
                                        aria-invalid={Boolean(errors.email)}
                                        aria-describedby={errors.email ? 'email-error' : undefined}
                                        className={`w-full rounded-lg border pl-9.5 pr-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${
                                            errors.email
                                                ? 'border-rose-400 bg-rose-50/20 focus:ring-rose-400 focus:border-rose-400'
                                                : 'border-slate-300 bg-white hover:border-slate-400'
                                        }`}
                                    />
                                </div>
                                {errors.email && (
                                    <p id="email-error" className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            {/* Mot de passe */}
                            <div>
                                <div className="flex items-center justify-between mb-1.5">
                                    <label
                                        htmlFor="password"
                                        className="block text-xs font-semibold uppercase tracking-wider text-slate-700"
                                    >
                                        Mot de passe <span className="text-rose-500">*</span>
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => setShowForgotModal(true)}
                                        className="text-xs font-medium text-indigo-600 hover:text-indigo-700 transition focus:outline-none focus:underline"
                                    >
                                        Mot de passe oublié ?
                                    </button>
                                </div>
                                <div className="relative rounded-lg shadow-2xs">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <Lock className="w-4 h-4" />
                                    </div>
                                    <input
                                        id="password"
                                        name="password"
                                        type={showPassword ? 'text' : 'password'}
                                        autoComplete="current-password"
                                        required
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="••••••••"
                                        aria-invalid={Boolean(errors.password)}
                                        aria-describedby={errors.password ? 'password-error' : undefined}
                                        className={`w-full rounded-lg border pl-9.5 pr-10 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 ${
                                            errors.password
                                                ? 'border-rose-400 bg-rose-50/20 focus:ring-rose-400 focus:border-rose-400'
                                                : 'border-slate-300 bg-white hover:border-slate-400'
                                        }`}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600 transition focus:outline-none"
                                        aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                                        title={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                                    >
                                        {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p id="password-error" className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.password}
                                    </p>
                                )}
                            </div>

                            {/* Options: Remember me */}
                            <div className="flex items-center justify-between pt-1">
                                <label className="relative flex items-center gap-2.5 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        id="remember"
                                        name="remember"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 transition cursor-pointer"
                                    />
                                    <span className="text-xs text-slate-600 font-medium">
                                        Se souvenir de moi
                                    </span>
                                </label>
                            </div>

                            {/* Submit Button */}
                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full inline-flex items-center justify-center font-medium rounded-lg px-4 py-2.5 text-sm text-white bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 shadow-xs focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed transition-all cursor-pointer"
                                >
                                    {processing ? (
                                        <>
                                            <svg
                                                className="animate-spin -ml-0.5 mr-2 h-4 w-4 text-white"
                                                xmlns="http://www.w3.org/2000/svg"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    className="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    strokeWidth="4"
                                                />
                                                <path
                                                    className="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8v8H4z"
                                                />
                                            </svg>
                                            <span>Connexion...</span>
                                        </>
                                    ) : (
                                        <span>Se connecter</span>
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Discret security note in footer */}
                    <div className="text-center pt-2">
                        <p className="inline-flex items-center gap-1.5 text-xs text-slate-400">
                            <ShieldCheck className="w-3.5 h-3.5 text-slate-400" />
                            Accès sécurisé à votre espace GED — Connexion chiffrée SSL
                        </p>
                    </div>
                </div>
            </div>

            {/* ========================================================================= */}
            {/* MODAL : Assistance mot de passe oublié (B2B Multi-tenant)                 */}
            {/* ========================================================================= */}
            {showForgotModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-150"
                    onClick={() => setShowForgotModal(false)}
                >
                    <div
                        className="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 relative"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            type="button"
                            onClick={() => setShowForgotModal(false)}
                            className="absolute top-4 right-4 p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition"
                            aria-label="Fermer"
                        >
                            <X className="w-4 h-4" />
                        </button>

                        <div className="flex items-center gap-3 mb-4">
                            <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <Lock className="w-5 h-5" />
                            </div>
                            <div>
                                <h3 className="text-base font-bold text-slate-900">
                                    Réinitialisation du mot de passe
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Sécurité du compte d'organisation
                                </p>
                            </div>
                        </div>

                        <div className="space-y-3 text-xs text-slate-600 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-100 mb-5">
                            <p>
                                Pour garantir l'intégrité et la conformité des données au sein de votre espace GED d'entreprise, la réinitialisation des mots de passe est encadrée par votre administrateur organisationnel.
                            </p>
                            <p className="font-medium text-slate-800">
                                Veuillez contacter le responsable informatique ou l'administrateur de votre organisation afin de recevoir un lien de réinitialisation sécurisé.
                            </p>
                        </div>

                        <button
                            type="button"
                            onClick={() => setShowForgotModal(false)}
                            className="w-full inline-flex items-center justify-center rounded-lg px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition"
                        >
                            Compris, fermer
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
