import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Modal from '../../Components/Modal';
import Table from '../../Components/Table';
import Badge from '../../Components/Badge';
import ConfirmDialog from '../../Components/ConfirmDialog';
import EmptyState from '../../Components/EmptyState';
import { Plus, Trash2, KeyRound, User, Building2, Network, FileStack, Shield, Clock, Search, Filter } from 'lucide-react';

export default function AccessScopesIndex({
    scopes = { data: [] },
    users = [],
    directions = [],
    services = [],
    documentTypes = [],
    scopeTypes = [],
    filters = {},
    can = {},
}) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [revokeScope, setRevokeScope] = useState(null);

    const form = useForm({
        user_id: '',
        scope_type: 'service',
        scope_id: '',
        permissions: ['documents.view', 'documents.create', 'documents.download'],
        expires_at: '',
    });

    const handleCreate = (e) => {
        e.preventDefault();
        const payload = {
            user_id: form.data.user_id,
            scope_type: form.data.scope_type,
            direction_id: form.data.scope_type === 'direction' ? form.data.scope_id : null,
            service_id: form.data.scope_type === 'service' ? form.data.scope_id : null,
            folder_id: (form.data.scope_type === 'document_type' || form.data.scope_type === 'folder') ? form.data.scope_id : null,
            document_id: form.data.scope_type === 'document' ? form.data.scope_id : null,
            scope_id: form.data.scope_id || null,
            expires_at: form.data.expires_at || null,
        };

        router.post('/access-scopes', payload, {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
            onError: (err) => {
                form.setError(err);
            },
        });
    };

    const handleRevoke = () => {
        if (!revokeScope) return;
        router.delete(`/access-scopes/${revokeScope.id}`, {
            onSuccess: () => setRevokeScope(null),
        });
    };

    const handleFilterChange = (key, val) => {
        router.get('/access-scopes', { ...filters, [key]: val || undefined }, { preserveState: true });
    };

    const renderScopeIcon = (type) => {
        switch (type) {
            case 'organization':
                return <Shield className="w-3.5 h-3.5 text-amber-600" />;
            case 'direction':
                return <Building2 className="w-3.5 h-3.5 text-blue-600" />;
            case 'service':
                return <Network className="w-3.5 h-3.5 text-indigo-600" />;
            case 'document_type':
                return <FileStack className="w-3.5 h-3.5 text-purple-600" />;
            default:
                return <KeyRound className="w-3.5 h-3.5 text-slate-600" />;
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Périmètres d'Accès (V2)" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 rounded-xl bg-purple-50 text-purple-600">
                                <KeyRound className="w-5 h-5" />
                            </span>
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Périmètres d'accès</h1>
                            <span className="px-2 py-0.5 text-xs font-semibold bg-purple-100 text-purple-700 rounded-full">
                                {scopes.total ?? scopes.data.length}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-1">
                            Définissez <span className="font-semibold text-slate-700">OÙ</span> un utilisateur peut exercer ses permissions (Organisation, Direction, Service, etc.).
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {can.create && (
                            <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                                <Plus className="w-4 h-4 mr-1.5" />
                                Nouveau périmètre
                            </Button>
                        )}
                    </div>
                </div>

                {/* Conceptual Access Control Pipeline */}
                <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-5 text-white shadow-sm">
                    <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        <div className="space-y-1">
                            <span className="text-[10px] font-bold tracking-widest text-indigo-400 uppercase">
                                Architecture de Contrôle d'Accès
                            </span>
                            <h2 className="text-sm font-semibold">
                                Modèle de résolution des droits effectifs
                            </h2>
                        </div>
                        <div className="flex flex-wrap items-center gap-2 text-xs">
                            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-xs border border-white/10 font-medium">
                                <span className="w-2 h-2 rounded-full bg-blue-400"></span>
                                <span>Utilisateur</span>
                            </div>
                            <span className="text-slate-400">→</span>
                            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-xs border border-white/10 font-medium">
                                <span className="w-2 h-2 rounded-full bg-indigo-400"></span>
                                <span>Rôle</span>
                            </div>
                            <span className="text-slate-400">→</span>
                            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-xs border border-white/10 font-medium">
                                <span className="w-2 h-2 rounded-full bg-emerald-400"></span>
                                <span>Permissions <span className="text-[10px] text-slate-300 font-normal">(Quoi faire)</span></span>
                            </div>
                            <span className="text-slate-400">→</span>
                            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-500/30 backdrop-blur-xs border border-purple-400/30 text-purple-200 font-semibold shadow-xs">
                                <span className="w-2 h-2 rounded-full bg-purple-400"></span>
                                <span>Périmètre <span className="text-[10px] text-purple-300 font-normal">(Où l'exercer)</span></span>
                            </div>
                            <span className="text-slate-400">→</span>
                            <div className="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/10 backdrop-blur-xs border border-white/10 font-medium">
                                <span className="w-2 h-2 rounded-full bg-amber-400"></span>
                                <span>Ressources</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filter bar */}
                <div className="flex flex-col sm:flex-row items-center gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div className="flex items-center gap-2 text-xs font-semibold text-slate-500 shrink-0">
                        <Filter className="w-3.5 h-3.5" />
                        Filtres :
                    </div>

                    <select
                        value={filters.user_id || ''}
                        onChange={(e) => handleFilterChange('user_id', e.target.value)}
                        className="w-full sm:w-64 px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                    >
                        <option value="">Tous les utilisateurs</option>
                        {users.map((u) => (
                            <option key={u.id} value={u.id}>
                                {u.name} ({u.email})
                            </option>
                        ))}
                    </select>

                    <select
                        value={filters.scope_type || ''}
                        onChange={(e) => handleFilterChange('scope_type', e.target.value)}
                        className="w-full sm:w-56 px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                    >
                        <option value="">Tous les types de périmètre</option>
                        {scopeTypes.map((t) => (
                            <option key={t.value} value={t.value}>
                                {t.label}
                            </option>
                        ))}
                    </select>

                    {(filters.user_id || filters.scope_type) && (
                        <button
                            onClick={() => router.get('/access-scopes')}
                            className="text-xs text-rose-600 hover:underline font-medium ml-auto"
                        >
                            Réinitialiser filtres
                        </button>
                    )}
                </div>

                {/* Table */}
                {scopes.data && scopes.data.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Utilisateur', 'Type de Périmètre', 'Cible / Zone couverte', 'Attribué par', 'Expiration', 'Actions']}>
                            {scopes.data.map((scope) => (
                                <tr key={scope.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-2.5">
                                            <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-xs text-slate-700">
                                                {scope.user_name ? scope.user_name[0] : 'U'}
                                            </div>
                                            <div>
                                                <div className="font-semibold text-xs text-slate-900">{scope.user_name}</div>
                                                <div className="text-[10px] text-slate-400">{scope.user_email}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-medium capitalize">
                                            {renderScopeIcon(scope.scope_type)}
                                            {scope.scope_type_label}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className="font-semibold text-xs text-slate-800">
                                            {scope.scope_target_name}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-500">
                                        {scope.granted_by_name || 'Système'}
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-500">
                                        {scope.expires_at ? (
                                            <span className="inline-flex items-center gap-1 text-amber-600 font-medium">
                                                <Clock className="w-3.5 h-3.5" />
                                                {new Date(scope.expires_at).toLocaleDateString()}
                                            </span>
                                        ) : (
                                            <span className="text-slate-400">Permanent</span>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        {can.delete && (
                                            <button
                                                onClick={() => setRevokeScope(scope)}
                                                className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition"
                                                title="Révoquer ce périmètre"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </Table>
                    </div>
                ) : (
                    <EmptyState
                        icon={KeyRound}
                        title="Aucun périmètre d'accès défini"
                        description="Les périmètres déterminent précisément où un collaborateur peut agir."
                        action={
                            can.create ? (
                                <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                                    <Plus className="w-4 h-4 mr-1.5" />
                                    Accorder un périmètre
                                </Button>
                            ) : null
                        }
                    />
                )}
            </div>

            {/* Modal Attribution Périmètre */}
            <Modal show={createModalOpen} onClose={() => setCreateModalOpen(false)}>
                <form onSubmit={handleCreate} className="p-6 space-y-5">
                    <div className="flex items-center gap-3">
                        <span className="p-2.5 rounded-xl bg-purple-50 text-purple-600">
                            <KeyRound className="w-5 h-5" />
                        </span>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900">Accorder un Périmètre d'Accès</h3>
                            <p className="text-xs text-slate-500">Attribuez une zone géographique ou fonctionnelle à un collaborateur.</p>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Collaborateur concerné <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={form.data.user_id}
                                onChange={(e) => form.setData('user_id', e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                                required
                            >
                                <option value="">Sélectionner un utilisateur</option>
                                {users.map((u) => (
                                    <option key={u.id} value={u.id}>
                                        {u.name} ({u.email})
                                    </option>
                                ))}
                            </select>
                            {form.errors.user_id && <p className="text-xs text-rose-500 mt-1">{form.errors.user_id}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Type de périmètre <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={form.data.scope_type}
                                onChange={(e) => {
                                    form.setData({
                                        ...form.data,
                                        scope_type: e.target.value,
                                        scope_id: '',
                                    });
                                }}
                                className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                                required
                            >
                                {scopeTypes.map((t) => (
                                    <option key={t.value} value={t.value}>
                                        {t.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Dynamic Target Selection */}
                        {form.data.scope_type === 'direction' && (
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Direction ciblée <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={form.data.scope_id}
                                    onChange={(e) => form.setData('scope_id', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                                    required
                                >
                                    <option value="">Sélectionner une direction</option>
                                    {directions.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {form.data.scope_type === 'service' && (
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Service ciblé <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={form.data.scope_id}
                                    onChange={(e) => form.setData('scope_id', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                                    required
                                >
                                    <option value="">Sélectionner un service</option>
                                    {services.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name} ({s.direction_name})
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {form.data.scope_type === 'document_type' && (
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Type documentaire ciblé <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={form.data.scope_id}
                                    onChange={(e) => form.setData('scope_id', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500"
                                    required
                                >
                                    <option value="">Sélectionner un type documentaire</option>
                                    {documentTypes.map((dt) => (
                                        <option key={dt.id} value={dt.id}>
                                            {dt.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {form.data.scope_type === 'organization' && (
                            <div className="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800">
                                <span className="font-bold">Portée globale :</span> Ce périmètre accordera l'accès sur l'ensemble de l'organisation.
                            </div>
                        )}

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Date d'expiration (optionnel)
                            </label>
                            <Input
                                type="date"
                                value={form.data.expires_at}
                                onChange={(e) => form.setData('expires_at', e.target.value)}
                                error={form.errors.expires_at}
                            />
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <Button type="button" variant="secondary" onClick={() => setCreateModalOpen(false)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary" disabled={form.processing}>
                            {form.processing ? 'Attribution...' : 'Accorder le périmètre'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Confirmation Révocation */}
            <ConfirmDialog
                isOpen={!!revokeScope}
                onClose={() => setRevokeScope(null)}
                onConfirm={handleRevoke}
                title="Révoquer ce périmètre d'accès ?"
                message={`Êtes-vous sûr de vouloir retirer le périmètre "${revokeScope?.scope_target_name}" pour "${revokeScope?.user_name}" ? L'utilisateur perdra l'accès aux documents de ce périmètre.`}
                confirmText="Révoquer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
