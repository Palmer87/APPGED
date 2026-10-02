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
    ShieldCheck,
    CheckCircle2,
    Building2,
    Sparkles,
    AlertCircle,
    Check
} from 'lucide-react';

export default function RegisterAccount({ account = {}, organization = {} }) {
    const { flash } = usePage().props;
    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        first_name: account?.first_name || '',
        last_name: account?.last_name || '',
        email: account?.email || '',
        phone: account?.phone || '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/inscription');
    };

    return (
        <div className="min-h-screen w-full flex bg-slate-50 text-slate-900 font-sans antialiased">
            <Head title="Créer votre compte — Inscription GEDAPP" />

            {/* ZONE GAUCHE (Desktop >= lg) : Identité GEDAPP & Réassurance SaaS */}
            <div className="hidden lg:flex lg:w-1/2 xl:w-5/12 flex-col justify-between bg-slate-950 p-10 xl:p-14 text-white relative overflow-hidden border-r border-slate-800">
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
                                Plateforme GED Cloud B2B
                            </span>
                        </div>
                    </Link>
                </div>

                {/* Main Copy & Trial Value Proposition */}
                <div className="relative z-10 my-auto py-10 space-y-6">
                    <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20 backdrop-blur-sm">
                        <Sparkles className="w-3.5 h-3.5" />
                        <span>Essai gratuit 14 jours sans engagement</span>
                    </div>

                    <h1 className="text-3xl xl:text-4xl font-extrabold tracking-tight text-white leading-tight">
                        Passez à la gestion documentaire <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">d'entreprise</span>.
                    </h1>

                    <p className="text-slate-400 text-sm xl:text-base leading-relaxed">
                        Créez votre compte administrateur en quelques secondes pour configurer votre organisation et centraliser vos documents professionnels.
                    </p>

                    <div className="space-y-3 pt-2">
                        <div className="flex items-center gap-3 text-slate-300 text-sm">
                            <div className="w-5 h-5 rounded-full bg-blue-600/20 text-blue-400 flex items-center justify-center shrink-0">
                                <Check className="w-3 h-3 stroke-[3]" />
                            </div>
                            <span>Accès complet immédiat pendant 14 jours</span>
                        </div>
                        <div className="flex items-center gap-3 text-slate-300 text-sm">
                            <div className="w-5 h-5 rounded-full bg-blue-600/20 text-blue-400 flex items-center justify-center shrink-0">
                                <Check className="w-3 h-3 stroke-[3]" />
                            </div>
                            <span>Aucune carte bancaire requise pour débuter</span>
                        </div>
                        <div className="flex items-center gap-3 text-slate-300 text-sm">
                            <div className="w-5 h-5 rounded-full bg-blue-600/20 text-blue-400 flex items-center justify-center shrink-0">
                                <Check className="w-3 h-3 stroke-[3]" />
                            </div>
                            <span>Données isolées et stockées en coffre privé R2/S3</span>
                        </div>
                    </div>
                </div>

                {/* Footer Assurance */}
                <div className="relative z-10 pt-6 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-500">
                    <span className="flex items-center gap-1.5">
                        <ShieldCheck className="w-4 h-4 text-emerald-400" />
                        Multi-tenant sécurisé
                    </span>
                    <span>© {new Date().getFullYear()} GEDAPP SaaS</span>
                </div>
            </div>

            {/* ZONE DROITE : Formulaire d'inscription Étape 1 */}
            <div className="flex-1 flex flex-col justify-between p-6 sm:p-10 lg:p-12 xl:p-16 overflow-y-auto">
                <div className="max-w-xl w-full mx-auto space-y-8">
                    {/* Header Mobile Brand */}
                    <div className="lg:hidden flex items-center justify-between pb-4 border-b border-slate-200">
                        <Link href="/" className="inline-flex items-center gap-2">
                            <div className="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-black text-sm">
                                GED
                            </div>
                            <span className="font-bold text-lg text-slate-900">
                                GED<span className="text-blue-600">APP</span>
                            </span>
                        </Link>
                        <Link href="/login" className="text-xs font-semibold text-blue-600 hover:text-blue-700">
                            Connexion
                        </Link>
                    </div>

                    {/* Progress Indicator */}
                    <div className="space-y-2">
                        <div className="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-slate-400">
                            <span className="text-blue-600 font-bold">Étape 1 sur 3</span>
                            <span>Création du compte</span>
                        </div>
                        <div className="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
                            <div className="bg-blue-600 h-1.5 rounded-full w-1/3 transition-all duration-300" />
                        </div>
                    </div>

                    {/* Title & Subtitle */}
                    <div>
                        <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                            Créer votre compte administrateur
                        </h2>
                        <p className="text-sm text-slate-500 mt-2">
                            Ce compte sera le premier administrateur habilité de votre espace GEDAPP.
                        </p>
                    </div>

                    {/* Flash Error Banner */}
                    {flash?.error && (
                        <div className="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-3 text-sm text-rose-700">
                            <AlertCircle className="w-5 h-5 shrink-0 text-rose-500" />
                            <span>{flash.error}</span>
                        </div>
                    )}

                    {/* Registration Form */}
                    <form onSubmit={handleSubmit} className="space-y-5">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                    Prénom <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative">
                                    <User className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                    <input
                                        type="text"
                                        value={data.first_name}
                                        onChange={(e) => setData('first_name', e.target.value)}
                                        placeholder="Ex: Jean"
                                        className="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                                        required
                                    />
                                </div>
                                {errors.first_name && <p className="text-xs text-rose-600 mt-1">{errors.first_name}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                    Nom <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative">
                                    <User className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                    <input
                                        type="text"
                                        value={data.last_name}
                                        onChange={(e) => setData('last_name', e.target.value)}
                                        placeholder="Ex: Dupont"
                                        className="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                                        required
                                    />
                                </div>
                                {errors.last_name && <p className="text-xs text-rose-600 mt-1">{errors.last_name}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                Adresse email professionnelle <span className="text-rose-500">*</span>
                            </label>
                            <div className="relative">
                                <Mail className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="vous@entreprise.com"
                                    className="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                                    required
                                />
                            </div>
                            {errors.email && <p className="text-xs text-rose-600 mt-1">{errors.email}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                Numéro de téléphone <span className="text-xs font-normal text-slate-400 lowercase">(facultatif)</span>
                            </label>
                            <div className="relative">
                                <Phone className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                <input
                                    type="tel"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    placeholder="+225 07 00 00 00 00"
                                    className="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                                />
                            </div>
                            {errors.phone && <p className="text-xs text-rose-600 mt-1">{errors.phone}</p>}
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                    Mot de passe <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative">
                                    <Lock className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                    <input
                                        type={showPassword ? 'text' : 'password'}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="Min. 8 caractères"
                                        className="w-full pl-10 pr-10 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                                    </button>
                                </div>
                                {errors.password && <p className="text-xs text-rose-600 mt-1">{errors.password}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                    Confirmation <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative">
                                    <Lock className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                    <input
                                        type={showPassword ? 'text' : 'password'}
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        placeholder="Retapez le mot de passe"
                                        className="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600/30 focus:border-blue-600 transition"
                                        required
                                    />
                                </div>
                                {errors.password_confirmation && (
                                    <p className="text-xs text-rose-600 mt-1">{errors.password_confirmation}</p>
                                )}
                            </div>
                        </div>

                        <div className="pt-4">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-blue-600/25 flex items-center justify-center gap-2 transition disabled:opacity-60"
                            >
                                <span>Continuer vers mon organisation</span>
                                <ArrowRight className="w-4 h-4" />
                            </button>
                        </div>
                    </form>

                    <p className="text-center text-xs text-slate-500 pt-2">
                        Vous avez déjà un compte ?{' '}
                        <Link href="/login" className="font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                            Se connecter
                        </Link>
                    </p>
                </div>

                <div className="pt-8 text-center text-xs text-slate-400">
                    En continuant, vous acceptez les{' '}
                    <a href="#" className="underline hover:text-slate-600">Conditions Générales d'Utilisation</a> et notre{' '}
                    <a href="#" className="underline hover:text-slate-600">Politique de Confidentialité</a>.
                </div>
            </div>
        </div>
    );
}
