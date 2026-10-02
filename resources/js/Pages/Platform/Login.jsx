import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Crown, Lock, Mail, ArrowRight, ShieldCheck } from 'lucide-react';

export default function PlatformLogin() {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/platform/login');
    };

    return (
        <div className="min-h-screen bg-slate-950 flex flex-col justify-center items-center p-4 selection:bg-indigo-500 selection:text-white relative overflow-hidden">
            <Head title="Connexion Propriétaire SaaS — GEDAPP" />

            {/* Subtle background glow */}
            <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none" />

            <div className="w-full max-w-md relative z-10">
                {/* Brand Header */}
                <div className="text-center mb-8">
                    <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white shadow-xl shadow-indigo-600/30 mb-4">
                        <Crown className="w-7 h-7" />
                    </div>
                    <div className="flex items-center justify-center gap-2">
                        <h1 className="text-2xl font-black tracking-tight text-white">
                            GED<span className="text-indigo-400">APP</span>
                        </h1>
                        <span className="px-2 py-0.5 rounded text-xs font-black uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                            PLATFORM
                        </span>
                    </div>
                    <p className="text-xs text-slate-400 mt-2 font-medium">
                        Accès réservé au propriétaire et administrateurs du SaaS
                    </p>
                </div>

                {/* Login Card */}
                <div className="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl backdrop-blur-xl">
                    <form onSubmit={submit} className="space-y-5">
                        {/* Email Field */}
                        <div>
                            <label className="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">
                                Email Administrateur
                            </label>
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                    <Mail className="w-4 h-4" />
                                </div>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="owner@gedapp.com"
                                    required
                                    className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                                />
                            </div>
                            {errors.email && (
                                <p className="mt-1.5 text-xs text-rose-400 font-medium">{errors.email}</p>
                            )}
                        </div>

                        {/* Password Field */}
                        <div>
                            <label className="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">
                                Mot de passe
                            </label>
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                    <Lock className="w-4 h-4" />
                                </div>
                                <input
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••••••"
                                    required
                                    className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition"
                                />
                            </div>
                            {errors.password && (
                                <p className="mt-1.5 text-xs text-rose-400 font-medium">{errors.password}</p>
                            )}
                        </div>

                        {/* Remember Me */}
                        <div className="flex items-center justify-between">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.remember}
                                    onChange={(e) => setData('remember', e.target.checked)}
                                    className="w-4 h-4 rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span className="text-xs text-slate-400">Rester connecté</span>
                            </label>
                        </div>

                        {/* Submit Button */}
                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 shadow-lg shadow-indigo-600/30 transition-all active:scale-[0.99] disabled:opacity-50 cursor-pointer"
                        >
                            <span>{processing ? 'Connexion en cours...' : 'Se connecter à la Console'}</span>
                            <ArrowRight className="w-4 h-4" />
                        </button>
                    </form>

                    {/* Security Notice */}
                    <div className="mt-6 pt-5 border-t border-slate-800/80 flex items-center gap-2 text-[11px] text-slate-400 justify-center">
                        <ShieldCheck className="w-4 h-4 text-emerald-400 shrink-0" />
                        <span>Sessions chiffrées & journalisées via audit plateforme</span>
                    </div>
                </div>

                {/* Back to Client App */}
                <div className="text-center mt-6">
                    <a
                        href="/login"
                        className="text-xs text-slate-400 hover:text-indigo-400 transition"
                    >
                        ← Retour à l'espace client organisation
                    </a>
                </div>
            </div>
        </div>
    );
}
