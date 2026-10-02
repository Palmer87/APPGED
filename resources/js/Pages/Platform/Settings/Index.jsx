import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Settings,
    Mail,
    Calendar,
    Save,
    Shield,
    CheckCircle2,
    DollarSign,
    Layers
} from 'lucide-react';

export default function SettingsIndex({ settings = {} }) {
    const { data, setData, put, processing, recentlySuccessful, errors } = useForm({
        support_email: settings.support_email || 'support@gedapp.com',
        billing_email: settings.billing_email || 'billing@gedapp.com',
        trial_period_days: settings.trial_period_days || 14,
        allow_new_registrations: settings.allow_new_registrations ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put('/platform/settings');
    };

    return (
        <PlatformLayout title="Paramètres Globaux du SaaS">
            <Head title="Paramètres SaaS — Console Propriétaire" />

            <div className="max-w-4xl space-y-6">
                {/* Info Card */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400">
                            <Settings className="w-5 h-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-white">Configuration SaaS & Politiques Commerciales</h3>
                            <p className="text-xs text-slate-400">
                                Ajustez les règles globales applicables aux nouvelles organisations et aux abonnements.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Form */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-8 shadow-xl">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {recentlySuccessful && (
                            <div className="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl flex items-center gap-2.5 text-sm text-emerald-400">
                                <CheckCircle2 className="w-4 h-4" /> Les paramètres ont été mis à jour avec succès.
                            </div>
                        )}

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                                    Nom de la Plateforme (config)
                                </label>
                                <input
                                    type="text"
                                    value={settings.app_name || 'GEDAPP'}
                                    disabled
                                    className="w-full px-4 py-2.5 bg-slate-950/40 border border-slate-800 rounded-xl text-sm text-slate-500 cursor-not-allowed"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">
                                    Devise de Facturation Principale
                                </label>
                                <input
                                    type="text"
                                    value={settings.default_currency || 'XOF'}
                                    disabled
                                    className="w-full px-4 py-2.5 bg-slate-950/40 border border-slate-800 rounded-xl text-sm text-slate-500 cursor-not-allowed"
                                />
                            </div>
                        </div>

                        <div className="border-t border-slate-800/80 pt-6">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-4 flex items-center gap-2">
                                <Calendar className="w-4 h-4" /> Politique des Périodes d'Essai (Trials)
                            </h4>

                            <div className="max-w-xs">
                                <label className="block text-xs font-semibold text-slate-300 mb-2">
                                    Durée de l'essai gratuit par défaut (jours)
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    max="60"
                                    value={data.trial_period_days}
                                    onChange={(e) => setData('trial_period_days', parseInt(e.target.value) || 14)}
                                    className="w-full px-4 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    required
                                />
                                {errors.trial_period_days && (
                                    <p className="text-xs text-rose-400 mt-1">{errors.trial_period_days}</p>
                                )}
                            </div>
                        </div>

                        <div className="border-t border-slate-800/80 pt-6">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-4 flex items-center gap-2">
                                <Mail className="w-4 h-4" /> Canaux de Communication Officiels
                            </h4>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-2">
                                        Email du Support Technique
                                    </label>
                                    <input
                                        type="email"
                                        value={data.support_email}
                                        onChange={(e) => setData('support_email', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                        required
                                    />
                                    {errors.support_email && (
                                        <p className="text-xs text-rose-400 mt-1">{errors.support_email}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-2">
                                        Email du Service Facturation
                                    </label>
                                    <input
                                        type="email"
                                        value={data.billing_email}
                                        onChange={(e) => setData('billing_email', e.target.value)}
                                        className="w-full px-4 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                        required
                                    />
                                    {errors.billing_email && (
                                        <p className="text-xs text-rose-400 mt-1">{errors.billing_email}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="border-t border-slate-800/80 pt-6">
                            <h4 className="text-xs font-bold uppercase tracking-wider text-indigo-400 mb-4 flex items-center gap-2">
                                <Shield className="w-4 h-4" /> Inscriptions & Accès
                            </h4>

                            <label className="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.allow_new_registrations}
                                    onChange={(e) => setData('allow_new_registrations', e.target.checked)}
                                    className="w-4 h-4 rounded text-indigo-600 bg-slate-950 border-slate-800 focus:ring-indigo-500 focus:ring-offset-slate-900"
                                />
                                <span className="text-sm text-slate-300 font-medium">
                                    Autoriser la création automatique de nouvelles organisations clientes
                                </span>
                            </label>
                        </div>

                        <div className="pt-4 flex justify-end">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-indigo-600/30"
                            >
                                <Save className="w-4 h-4" /> Enregistrer les Paramètres
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </PlatformLayout>
    );
}
