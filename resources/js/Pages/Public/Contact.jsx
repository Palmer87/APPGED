import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    Mail,
    Phone,
    MapPin,
    Send,
    CheckCircle2,
    ArrowRight,
    MessageSquare,
    Clock,
    Building2
} from 'lucide-react';

export default function Contact() {
    const { flash } = usePage().props;

    const { data, setData, post, processing, reset, recentlySuccessful, errors } = useForm({
        name: '',
        email: '',
        subject: '',
        message: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/contact', {
            onSuccess: () => reset(),
        });
    };

    return (
        <div className="min-h-screen bg-[#0B132B] text-slate-100 font-sans selection:bg-blue-600 selection:text-white">
            <Head title="Contactez-nous — GEDAPP Support & Ventes" />

            {/* Header */}
            <header className="border-b border-slate-800/80 bg-[#0B132B]/90 backdrop-blur-md sticky top-0 z-40">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-3 group">
                        <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 shrink-0 group-hover:scale-105 transition-transform">
                            GED
                        </div>
                        <span className="font-black text-xl tracking-tight text-white">
                            GED<span className="text-blue-500">APP</span>
                        </span>
                    </Link>

                    <nav className="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-300">
                        <Link href="/" className="hover:text-white transition">Accueil</Link>
                        <Link href="/fonctionnalites" className="hover:text-white transition">Fonctionnalités</Link>
                        <Link href="/tarifs" className="hover:text-white transition">Tarifs</Link>
                        <Link href="/enterprise" className="hover:text-white transition">Enterprise</Link>
                        <Link href="/contact" className="text-blue-400 font-bold">Contact</Link>
                    </nav>

                    <div className="flex items-center gap-3">
                        <Link href="/login" className="text-xs sm:text-sm font-semibold text-slate-300 hover:text-white px-3 py-2 transition">
                            Connexion
                        </Link>
                        <Link
                            href="/inscription"
                            className="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-500 text-white transition shadow-lg shadow-blue-600/30"
                        >
                            <span>Essai gratuit 14j</span>
                            <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 space-y-12">
                <div className="text-center space-y-4 max-w-2xl mx-auto">
                    <h1 className="text-3xl sm:text-5xl font-black text-white tracking-tight">
                        Nous sommes à votre <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-300">écoute</span>
                    </h1>
                    <p className="text-slate-400 text-sm sm:text-base">
                        Une question sur la plateforme, besoin d'aide pour votre déploiement ou d'un devis sur-mesure ? Écrivez-nous.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
                    {/* Left Info Column */}
                    <div className="md:col-span-5 space-y-6">
                        <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 sm:p-8 space-y-6">
                            <h3 className="text-lg font-bold text-white">Coordonnées directes</h3>

                            <div className="space-y-4 text-sm text-slate-300">
                                <div className="flex items-center gap-3.5">
                                    <div className="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-400 flex items-center justify-center shrink-0 border border-blue-500/20">
                                        <Mail className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <span className="block text-xs text-slate-500 font-semibold uppercase">Email</span>
                                        <a href="mailto:support@gedapp.com" className="text-white hover:text-blue-400 font-medium transition">
                                            support@gedapp.com
                                        </a>
                                    </div>
                                </div>

                                <div className="flex items-center gap-3.5">
                                    <div className="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20">
                                        <Phone className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <span className="block text-xs text-slate-500 font-semibold uppercase">Téléphone</span>
                                        <span className="text-white font-medium">+225 27 20 00 00 00</span>
                                    </div>
                                </div>

                                <div className="flex items-center gap-3.5">
                                    <div className="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center shrink-0 border border-purple-500/20">
                                        <Clock className="w-5 h-5" />
                                    </div>
                                    <div>
                                        <span className="block text-xs text-slate-500 font-semibold uppercase">Horaires</span>
                                        <span className="text-white font-medium">Lundi – Vendredi : 08h30 – 18h00 GMT</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-slate-900/40 border border-slate-800 rounded-3xl p-6 text-xs text-slate-400 space-y-2">
                            <strong className="text-slate-200 block">Support technique existant ?</strong>
                            <p>Les clients disposant d'un compte peuvent créer des tickets directement depuis leur interface d'administration.</p>
                        </div>
                    </div>

                    {/* Right Form Column */}
                    <div className="md:col-span-7 bg-slate-900/90 border border-slate-800 rounded-3xl p-8 sm:p-10 shadow-xl">
                        <h3 className="text-xl font-bold text-white mb-6">Envoyez-nous un message</h3>

                        {recentlySuccessful && (
                            <div className="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex items-center gap-3 text-sm text-emerald-400">
                                <CheckCircle2 className="w-5 h-5 shrink-0" />
                                <span>Votre message a été transmis avec succès. Nous vous répondrons rapidement.</span>
                            </div>
                        )}

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Votre nom complet <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Jean Dupont"
                                    className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    required
                                />
                                {errors.name && <p className="text-xs text-rose-400 mt-1">{errors.name}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Votre adresse email <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="vous@entreprise.com"
                                    className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    required
                                />
                                {errors.email && <p className="text-xs text-rose-400 mt-1">{errors.email}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Objet <span className="text-rose-400">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="Renseignements sur les offres"
                                    className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    required
                                />
                                {errors.subject && <p className="text-xs text-rose-400 mt-1">{errors.subject}</p>}
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1">
                                    Message <span className="text-rose-400">*</span>
                                </label>
                                <textarea
                                    rows={4}
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    placeholder="Expliquez-nous votre demande..."
                                    className="w-full px-3.5 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    required
                                />
                                {errors.message && <p className="text-xs text-rose-400 mt-1">{errors.message}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full py-3.5 px-6 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm shadow-xl shadow-blue-600/30 flex items-center justify-center gap-2 transition disabled:opacity-60"
                            >
                                <Send className="w-4 h-4" />
                                <span>Envoyer mon message</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {/* Footer */}
            <footer className="border-t border-slate-800/80 bg-[#0B132B] py-12 px-4 sm:px-6 lg:px-8 text-xs text-slate-500 text-center">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span>© {new Date().getFullYear()} GEDAPP SaaS. Tous droits réservés.</span>
                    <div className="flex gap-6">
                        <Link href="/fonctionnalites" className="hover:text-slate-300 transition">Fonctionnalités</Link>
                        <Link href="/tarifs" className="hover:text-slate-300 transition">Tarifs</Link>
                        <Link href="/enterprise" className="hover:text-slate-300 transition">Enterprise</Link>
                        <Link href="/contact" className="hover:text-slate-300 transition">Contact</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
