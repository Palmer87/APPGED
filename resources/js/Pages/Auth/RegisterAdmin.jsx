import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    User,
    Mail,
    Phone,
    Lock,
    Eye,
    EyeOff,
    ArrowRight,
    ArrowLeft,
    ShieldCheck,
    CheckCircle2,
    Building2,
    AlertCircle,
    KeyRound
} from 'lucide-react';
import RegistrationSteps from '../../Components/RegistrationSteps';

export default function RegisterAdmin({ organization = {}, admin = {} }) {
    const { flash } = usePage().props;
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        first_name: admin?.first_name || '',
        last_name: admin?.last_name || '',
        email: admin?.email || '',
        phone: admin?.phone || '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/register/admin');
    };

    return (
        <div className="min-h-screen w-full flex bg-slate-50 text-slate-900 font-sans antialiased">
            <Head title="Créer votre compte administrateur — GEDAPP" />

            {/* ZONE GAUCHE (Desktop >= lg) */}
            <div className="hidden lg:flex lg:w-1/2 xl:w-5/12 flex-col justify-between bg-slate-950 p-10 xl:p-14 text-white relative overflow-hidden border-r border-slate-800">
                <div className="absolute inset-0 opacity-15 pointer-events-none">
                    <div className="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-blue-600/30 blur-3xl" />
                    <div className="absolute -bottom-32 -right-32 w-96 h-96 rounded-full bg-indigo-600/20 blur-3xl" />
                </div>

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

                <div className="relative z-10 my-auto py-10 max-w-lg">
                    {/* Organization Recap Badge */}
                    <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-800 text-slate-300 text-xs font-semibold mb-6">
                        <Building2 className="w-4 h-4 text-blue-400" />
                        <span>Organisation : <strong className="text-white">{organization?.name || 'Votre entreprise'}</strong></span>
                    </div>

                    <h1 className="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight mb-4">
                        Vous serez l'administrateur principal de votre espace.
                    </h1>

                    <p className="text-slate-400 text-sm xl:text-base leading-relaxed mb-8">
                        Ce compte aura tous les privilèges pour inviter vos collaborateurs, configurer les permissions et administrer l'organisation.
                    </p>

                    <div className="space-y-4">
                        <div className="flex items-start gap-3.5 p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs">
                            <div className="w-8 h-8 rounded-lg bg-blue-600/20 text-blue-400 flex items-center justify-center shrink-0 mt-0.5">
                                <KeyRound className="w-4 h-4" />
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold text-slate-200">
                                    Contrôle total & rôles avancés
                                </h2>
                                <p className="text-xs text-slate-400 leading-normal mt-0.5">
                                    Gérez les accès par direction, attribuez des rôles (manager, lecteur, utilisateur) et pilotez les droits.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3.5 p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-xs">
                            <div className="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                                <ShieldCheck className="w-4 h-4" />
                            </div>
                            <div>
                                <h2 className="text-sm font-semibold text-slate-200">
                                    Sécurité et conformité RGPD
                                </h2>
                                <p className="text-xs text-slate-400 leading-normal mt-0.5">
                                    Chaque action est enregistrée dans le journal d'audit pour une traçabilité totale.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="relative z-10 pt-6 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
                    <div className="flex items-center gap-2">
                        <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                        <span>Chiffrement AES-256</span>
                    </div>
                    <span>Conforme B2B & Sécurité des données</span>
                </div>
            </div>

            {/* ZONE DROITE : Formulaire Étape 2 */}
            <div className="flex-1 flex flex-col justify-center items-center px-4 sm:px-8 lg:px-12 py-10 relative">
                <div className="w-full max-w-xl space-y-6">
                    {/* Stepper Header */}
                    <RegistrationSteps currentStep={2} />

                    {/* Step Title */}
                    <div className="text-left">
                        <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold mb-2">
                            <User className="w-3.5 h-3.5 text-blue-600" />
                            Étape 2 sur 3
                        </div>
                        <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-950">
                            Administrateur principal
                        </h2>
                        <p className="mt-1 text-xs sm:text-sm text-slate-600">
                            Créez vos identifiants pour accéder à l'organisation <strong>{organization?.name}</strong>.
                        </p>
                    </div>

                    {/* Flash Error */}
                    {flash?.error && (
                        <div className="rounded-xl border border-rose-200 bg-rose-50/80 p-3.5 flex items-start gap-3 text-rose-800 text-xs leading-relaxed">
                            <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />
                            <span>{flash.error}</span>
                        </div>
                    )}

                    {/* Form Card */}
                    <div className="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8">
                        <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                            {/* Prénom & Nom */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        htmlFor="first_name"
                                        className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                    >
                                        Prénom <span className="text-rose-500">*</span>
                                    </label>
                                    <div className="relative rounded-xl shadow-2xs">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <User className="w-4 h-4" />
                                        </div>
                                        <input
                                            id="first_name"
                                            name="first_name"
                                            type="text"
                                            required
                                            autoFocus
                                            value={data.first_name}
                                            onChange={(e) => setData('first_name', e.target.value)}
                                            placeholder="Ex: Jean"
                                            className={`w-full rounded-xl border pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                                                errors.first_name
                                                    ? 'border-rose-400 bg-rose-50/20 focus:ring-rose-400'
                                                    : 'border-slate-300 bg-white hover:border-slate-400'
                                            }`}
                                        />
                                    </div>
                                    {errors.first_name && (
                                        <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                            {errors.first_name}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="last_name"
                                        className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                    >
                                        Nom <span className="text-rose-500">*</span>
                                    </label>
                                    <div className="relative rounded-xl shadow-2xs">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <User className="w-4 h-4" />
                                        </div>
                                        <input
                                            id="last_name"
                                            name="last_name"
                                            type="text"
                                            required
                                            value={data.last_name}
                                            onChange={(e) => setData('last_name', e.target.value)}
                                            placeholder="Ex: Kouassi"
                                            className={`w-full rounded-xl border pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                                                errors.last_name
                                                    ? 'border-rose-400 bg-rose-50/20 focus:ring-rose-400'
                                                    : 'border-slate-300 bg-white hover:border-slate-400'
                                            }`}
                                        />
                                    </div>
                                    {errors.last_name && (
                                        <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                            {errors.last_name}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Email */}
                            <div>
                                <label
                                    htmlFor="email"
                                    className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                >
                                    Email professionnel <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative rounded-xl shadow-2xs">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                        <Mail className="w-4 h-4" />
                                    </div>
                                    <input
                                        id="email"
                                        name="email"
                                        type="email"
                                        required
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="jean.kouassi@entreprise.com"
                                        className={`w-full rounded-xl border pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                                            errors.email
                                                ? 'border-rose-400 bg-rose-50/20 focus:ring-rose-400'
                                                : 'border-slate-300 bg-white hover:border-slate-400'
                                        }`}
                                    />
                                </div>
                                {errors.email && (
                                    <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            {/* Téléphone */}
                            <div>
                                <label
                                    htmlFor="phone"
                                    className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                >
                                    Téléphone <span className="text-slate-400 font-normal lowercase">(optionnel)</span>
                                </label>
                                <div className="relative rounded-xl shadow-2xs">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                        <Phone className="w-4 h-4" />
                                    </div>
                                    <input
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        value={data.phone}
                                        onChange={(e) => setData('phone', e.target.value)}
                                        placeholder="+225 07 00 00 00 00"
                                        className="w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 hover:border-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                                    />
                                </div>
                                {errors.phone && (
                                    <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                        <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                        {errors.phone}
                                    </p>
                                )}
                            </div>

                            {/* Mot de passe & Confirmation */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        htmlFor="password"
                                        className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                    >
                                        Mot de passe <span className="text-rose-500">*</span>
                                    </label>
                                    <div className="relative rounded-xl shadow-2xs">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <Lock className="w-4 h-4" />
                                        </div>
                                        <input
                                            id="password"
                                            name="password"
                                            type={showPassword ? 'text' : 'password'}
                                            required
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="8 caractères min."
                                            className={`w-full rounded-xl border pl-10 pr-10 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                                                errors.password
                                                    ? 'border-rose-400 bg-rose-50/20 focus:ring-rose-400'
                                                    : 'border-slate-300 bg-white hover:border-slate-400'
                                            }`}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                                            aria-label={showPassword ? 'Masquer' : 'Afficher'}
                                        >
                                            {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                        </button>
                                    </div>
                                    {errors.password && (
                                        <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                            {errors.password}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="password_confirmation"
                                        className="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5"
                                    >
                                        Confirmer <span className="text-rose-500">*</span>
                                    </label>
                                    <div className="relative rounded-xl shadow-2xs">
                                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                            <Lock className="w-4 h-4" />
                                        </div>
                                        <input
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            type={showPassword ? 'text' : 'password'}
                                            required
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            placeholder="Répétez le mot de passe"
                                            className={`w-full rounded-xl border pl-10 pr-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-blue-500 ${
                                                errors.password_confirmation
                                                    ? 'border-rose-400 bg-rose-50/20 focus:ring-rose-400'
                                                    : 'border-slate-300 bg-white hover:border-slate-400'
                                            }`}
                                        />
                                    </div>
                                    {errors.password_confirmation && (
                                        <p className="mt-1.5 text-xs text-rose-600 font-medium flex items-center gap-1">
                                            <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                            {errors.password_confirmation}
                                        </p>
                                    )}
                                </div>
                            </div>

                            {/* Actions Buttons: Précédent & Continuer */}
                            <div className="pt-4 flex items-center gap-3">
                                <Link
                                    href="/register/organization"
                                    className="inline-flex items-center justify-center gap-1.5 px-4 py-3 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50 font-semibold text-xs sm:text-sm transition"
                                >
                                    <ArrowLeft className="w-4 h-4" />
                                    <span>Précédent</span>
                                </Link>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex-1 inline-flex items-center justify-center gap-2 font-bold rounded-full px-6 py-3.5 text-sm text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 shadow-md shadow-blue-500/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-60 disabled:cursor-not-allowed transition-all cursor-pointer"
                                >
                                    {processing ? (
                                        <span>Validation...</span>
                                    ) : (
                                        <>
                                            <span>Choisir mon plan & essai</span>
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
