import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    FileStack,
    PlusCircle,
    Check,
    X,
    Users,
    HardDrive,
    ScanText,
    Building2,
    ToggleLeft,
    ToggleRight,
    Edit3
} from 'lucide-react';

export default function PlansIndex({ plans = [] }) {
    const handleToggle = (plan) => {
        router.post(`/platform/plans/${plan.id}/toggle-active`);
    };

    const formatBytes = (bytes) => {
        if (!bytes) return 'Illimité';
        return Math.round(bytes / (1024 * 1024 * 1024)) + ' Go';
    };

    return (
        <PlatformLayout title="Catalogue des Plans Tarifaires">
            <Head title="Plans Tarifaires — Console Propriétaire" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-black text-white">Plans & Grille Tarifaire SaaS</h2>
                        <p className="text-xs text-slate-400 mt-1">
                            Configuration des tarifs, quotas de stockage, utilisateurs et fonctionnalités par palier.
                        </p>
                    </div>
                    <Link
                        href="/platform/plans/create"
                        className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 transition"
                    >
                        <PlusCircle className="w-4 h-4" />
                        <span>Créer un nouveau plan</span>
                    </Link>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {plans.map((p) => (
                        <div
                            key={p.id}
                            className={`rounded-3xl border p-6 flex flex-col justify-between transition ${
                                p.is_active
                                    ? 'bg-slate-900/60 border-slate-800'
                                    : 'bg-slate-950/80 border-slate-800/40 opacity-70'
                            }`}
                        >
                            <div>
                                <div className="flex items-center justify-between">
                                    <span className="text-lg font-black text-white">{p.name}</span>
                                    <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                        p.is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'
                                    }`}>
                                        {p.is_active ? 'Actif' : 'Désactivé'}
                                    </span>
                                </div>
                                <p className="text-xs text-slate-400 mt-2 min-h-8">
                                    {p.description || 'Aucune description fournie.'}
                                </p>

                                <div className="mt-4 pt-4 border-t border-slate-800">
                                    <div className="text-2xl font-black text-white">
                                        {p.monthly_price ? p.monthly_price.toLocaleString() : 'Sur mesure'}{' '}
                                        <span className="text-xs font-semibold text-slate-400">{p.monthly_price ? `${p.currency} / mois` : ''}</span>
                                    </div>
                                    {p.annual_price && (
                                        <div className="text-xs text-slate-400 mt-0.5">
                                            {p.annual_price.toLocaleString()} {p.currency} / an (facturation annuelle)
                                        </div>
                                    )}
                                </div>

                                <div className="mt-6 space-y-2.5 text-xs text-slate-300">
                                    <div className="flex items-center justify-between">
                                        <span className="text-slate-400">Utilisateurs autorisés :</span>
                                        <span className="font-bold text-white">{p.max_users || 'Illimité'}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-slate-400">Stockage dédié :</span>
                                        <span className="font-bold text-white">{formatBytes(p.max_storage_bytes)}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-slate-400">Directions max :</span>
                                        <span className="font-bold text-white">{p.max_directions || 'Illimité'}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-slate-400">Types documentaires :</span>
                                        <span className="font-bold text-white">{p.max_document_types || 'Illimité'}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-slate-400">Pages OCR / mois :</span>
                                        <span className="font-bold text-white">{p.max_ocr_pages_month || 'Illimité'}</span>
                                    </div>
                                    <div className="flex items-center justify-between pt-2 border-t border-slate-800/60">
                                        <span className="text-slate-400">Workflows d'approbation :</span>
                                        <span className="font-bold text-white">{p.has_workflows ? 'Inclus' : 'Non inclus'}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-slate-400">Audit avancé :</span>
                                        <span className="font-bold text-white">{p.has_advanced_audit ? 'Inclus' : 'Standard'}</span>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between gap-2">
                                <Link
                                    href={`/platform/plans/${p.id}/edit`}
                                    className="flex-1 inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white transition"
                                >
                                    <Edit3 className="w-3.5 h-3.5" />
                                    <span>Modifier</span>
                                </Link>
                                <button
                                    type="button"
                                    onClick={() => handleToggle(p)}
                                    className={`p-2 rounded-xl text-xs font-semibold transition cursor-pointer ${
                                        p.is_active ? 'bg-amber-500/10 hover:bg-amber-500/20 text-amber-300' : 'bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300'
                                    }`}
                                    title={p.is_active ? 'Désactiver' : 'Activer'}
                                >
                                    {p.is_active ? 'Désactiver' : 'Activer'}
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </PlatformLayout>
    );
}
