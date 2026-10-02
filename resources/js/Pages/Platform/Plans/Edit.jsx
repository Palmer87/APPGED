import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import { ArrowLeft, Save } from 'lucide-react';

export default function PlansEdit({ plan }) {
    const { data, setData, put, processing, errors } = useForm({
        name: plan.name || '',
        description: plan.description || '',
        monthly_price: plan.monthly_price || '',
        annual_price: plan.annual_price || '',
        currency: plan.currency || 'XOF',
        max_users: plan.max_users || '',
        max_storage_gb: plan.max_storage_bytes ? Math.round(plan.max_storage_bytes / (1024 * 1024 * 1024)) : '',
        max_directions: plan.max_directions || '',
        max_document_types: plan.max_document_types || '',
        max_ocr_pages_month: plan.max_ocr_pages_month || '',
        has_api: !!plan.has_api,
        has_workflows: !!plan.has_workflows,
        has_advanced_audit: !!plan.has_advanced_audit,
        has_priority_support: !!plan.has_priority_support,
        is_active: !!plan.is_active,
        sort_order: plan.sort_order || 0,
    });

    const submit = (e) => {
        e.preventDefault();
        put(`/platform/plans/${plan.id}`);
    };

    return (
        <PlatformLayout title={`Modifier le Plan : ${plan.name}`}>
            <Head title={`Modifier ${plan.name} — Console Propriétaire`} />

            <div className="max-w-3xl mx-auto space-y-6">
                <Link
                    href="/platform/plans"
                    className="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition"
                >
                    <ArrowLeft className="w-4 h-4" />
                    <span>Retour aux plans</span>
                </Link>

                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-6 sm:p-8">
                    <h2 className="text-lg font-black text-white mb-6">Paramètres du Plan : {plan.name}</h2>

                    <form onSubmit={submit} className="space-y-6">
                        <div>
                            <label className="block text-xs font-bold text-slate-300 mb-1">Nom du Plan *</label>
                            <input
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                            />
                            {errors.name && <p className="text-xs text-rose-400 mt-1">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-slate-300 mb-1">Description</label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows="2"
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
                                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-bold text-slate-300 mb-1">Prix Annuel (FCFA)</label>
                                <input
                                    type="number"
                                    value={data.annual_price}
                                    onChange={(e) => setData('annual_price', e.target.value)}
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
                                    className="rounded bg-slate-950 border-slate-800 text-indigo-600"
                                />
                                <span className="text-xs text-slate-300 font-semibold">Inclure les workflows</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.has_advanced_audit}
                                    onChange={(e) => setData('has_advanced_audit', e.target.checked)}
                                    className="rounded bg-slate-950 border-slate-800 text-indigo-600"
                                />
                                <span className="text-xs text-slate-300 font-semibold">Inclure l'audit avancé</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded bg-slate-950 border-slate-800 text-indigo-600"
                                />
                                <span className="text-xs text-slate-300 font-semibold">Plan actif</span>
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
                                <span>{processing ? 'Enregistrement...' : 'Mettre à jour'}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </PlatformLayout>
    );
}
