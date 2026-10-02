import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import { ArrowLeft, Save } from 'lucide-react';

export default function PlansCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        description: '',
        monthly_price: '',
        annual_price: '',
        currency: 'XOF',
        max_users: 5,
        max_storage_gb: 20,
        max_directions: 3,
        max_document_types: 15,
        max_ocr_pages_month: 100,
        has_api: false,
        has_workflows: false,
        has_advanced_audit: false,
        has_priority_support: false,
        is_active: true,
        sort_order: 1,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/platform/plans');
    };

    return (
        <PlatformLayout title="Créer un Plan Tarifaire">
            <Head title="Créer un Plan — Console Propriétaire" />

            <div className="max-w-3xl mx-auto space-y-6">
                <Link
                    href="/platform/plans"
                    className="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition"
                >
                    <ArrowLeft className="w-4 h-4" />
                    <span>Retour aux plans</span>
                </Link>

                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 sm:p-8">
                    <h2 className="text-lg font-black text-white mb-6">Paramètres du Nouveau Plan</h2>

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-slate-300 mb-1">Nom du Plan *</label>
                                <input
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Ex: Entreprise Plus"
                                    required
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                                {errors.name && <p className="text-xs text-rose-400 mt-1">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-300 mb-1">Slug (optionnel)</label>
                                <input
                                    type="text"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                    placeholder="auto-généré si vide"
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-300 mb-1">Description</label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows="2"
                                placeholder="Description pour les clients..."
                                className="w-full p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-slate-800">
                            <div>
                                <label className="block text-xs font-bold text-slate-300 mb-1">Prix Mensuel (FCFA)</label>
                                <input
                                    type="number"
                                    value={data.monthly_price}
                                    onChange={(e) => setData('monthly_price', e.target.value)}
                                    placeholder="19000"
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-300 mb-1">Prix Annuel (FCFA)</label>
                                <input
                                    type="number"
                                    value={data.annual_price}
                                    onChange={(e) => setData('annual_price', e.target.value)}
                                    placeholder="190000"
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-300 mb-1">Devise</label>
                                <input
                                    type="text"
                                    value={data.currency}
                                    onChange={(e) => setData('currency', e.target.value)}
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-4 border-t border-slate-800">
                            <div>
                                <label className="block text-[11px] font-bold text-slate-400 mb-1">Utilisateurs</label>
                                <input
                                    type="number"
                                    value={data.max_users}
                                    onChange={(e) => setData('max_users', e.target.value)}
                                    className="w-full px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-[11px] font-bold text-slate-400 mb-1">Stockage (Go)</label>
                                <input
                                    type="number"
                                    value={data.max_storage_gb}
                                    onChange={(e) => setData('max_storage_gb', e.target.value)}
                                    className="w-full px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-[11px] font-bold text-slate-400 mb-1">Directions</label>
                                <input
                                    type="number"
                                    value={data.max_directions}
                                    onChange={(e) => setData('max_directions', e.target.value)}
                                    className="w-full px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-[11px] font-bold text-slate-400 mb-1">Types Doc</label>
                                <input
                                    type="number"
                                    value={data.max_document_types}
                                    onChange={(e) => setData('max_document_types', e.target.value)}
                                    className="w-full px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"
                                />
                            </div>
                            <div>
                                <label className="block text-[11px] font-bold text-slate-400 mb-1">Pages OCR/m</label>
                                <input
                                    type="number"
                                    value={data.max_ocr_pages_month}
                                    onChange={(e) => setData('max_ocr_pages_month', e.target.value)}
                                    className="w-full px-2.5 py-1.5 rounded-lg bg-slate-950 border border-slate-800 text-xs text-white"
                                />
                            </div>
                        </div>

                        <div className="pt-4 border-t border-slate-800 space-y-2">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.has_workflows}
                                    onChange={(e) => setData('has_workflows', e.target.checked)}
                                    className="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span className="text-xs text-slate-300 font-semibold">Inclure les workflows de validation</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.has_advanced_audit}
                                    onChange={(e) => setData('has_advanced_audit', e.target.checked)}
                                    className="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span className="text-xs text-slate-300 font-semibold">Inclure l'audit avancé</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded bg-slate-950 border-slate-800 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span className="text-xs text-slate-300 font-semibold">Rendre ce plan immédiatement actif</span>
                            </label>
                        </div>

                        <div className="pt-4 border-t border-slate-800 flex justify-end gap-3">
                            <Link
                                href="/platform/plans"
                                className="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-xs font-bold text-white shadow-lg shadow-indigo-600/30 transition disabled:opacity-50"
                            >
                                <Save className="w-4 h-4" />
                                <span>{processing ? 'Enregistrement...' : 'Créer le plan'}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </PlatformLayout>
    );
}
