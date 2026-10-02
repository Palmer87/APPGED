import React, { useState, useMemo } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Modal from '../../Components/Modal';
import Table from '../../Components/Table';
import Badge from '../../Components/Badge';
import ConfirmDialog from '../../Components/ConfirmDialog';
import EmptyState from '../../Components/EmptyState';
import {
    Plus,
    Edit2,
    Trash2,
    FileStack,
    Building2,
    Network,
    FolderOpen,
    Sliders,
    Files,
    CheckCircle2,
    Filter,
    Search,
    X
} from 'lucide-react';

export default function DocumentTypesIndex({
    documentTypes = [],
    directions = [],
    services = [],
    departments = [],
    filters = {},
    can = {},
}) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editDocType, setEditDocType] = useState(null);
    const [deleteDocType, setDeleteDocType] = useState(null);
    const [selectedDirection, setSelectedDirection] = useState(filters.direction_id || 'all');
    const [selectedService, setSelectedService] = useState(filters.service_id || 'all');
    const [search, setSearch] = useState(filters.search || '');

    const effectiveDirections = directions.length > 0 ? directions : departments;

    const form = useForm({
        name: '',
        description: '',
        direction_id: '',
        service_id: '',
        parent_id: '',
        is_active: true,
    });

    const availableCreateServices = useMemo(() => {
        if (!form.data.direction_id) return services;
        return services.filter((s) => String(s.direction_id) === String(form.data.direction_id));
    }, [services, form.data.direction_id]);

    const availableFilterServices = useMemo(() => {
        if (selectedDirection === 'all') return services;
        return services.filter((s) => String(s.direction_id) === String(selectedDirection));
    }, [services, selectedDirection]);

    const filteredDocTypes = useMemo(() => {
        return documentTypes.filter((t) => {
            if (selectedDirection !== 'all' && String(t.direction_id) !== String(selectedDirection) && String(t.parent_id) !== String(selectedDirection)) {
                return false;
            }
            if (selectedService !== 'all' && String(t.service_id) !== String(selectedService)) {
                return false;
            }
            if (search) {
                const q = search.toLowerCase();
                const matchName = t.name?.toLowerCase().includes(q);
                const matchDesc = t.description?.toLowerCase().includes(q);
                if (!matchName && !matchDesc) return false;
            }
            return true;
        });
    }, [documentTypes, selectedDirection, selectedService, search]);

    const openCreate = () => {
        const defaultDir = selectedDirection !== 'all' ? selectedDirection : (effectiveDirections[0]?.id || '');
        form.setData({
            name: '',
            description: '',
            direction_id: defaultDir,
            service_id: '',
            parent_id: '',
            is_active: true,
        });
        setCreateModalOpen(true);
    };

    const handleCreate = (e) => {
        e.preventDefault();
        form.post('/document-types', {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editDocType) return;
        form.put(`/document-types/${editDocType.id}`, {
            onSuccess: () => {
                setEditDocType(null);
                form.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteDocType) return;
        router.delete(`/document-types/${deleteDocType.id}`, {
            onSuccess: () => setDeleteDocType(null),
        });
    };

    const openEdit = (t) => {
        setEditDocType(t);
        form.setData({
            name: t.name,
            description: t.description || '',
            parent_id: t.parent_id,
            is_active: t.is_active,
        });
    };

    const resetFilters = () => {
        setSelectedDirection('all');
        setSelectedService('all');
        setSearch('');
    };

    return (
        <AuthenticatedLayout>
            <Head title="Types documentaires" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2.5">
                            <span className="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                                <FileStack className="w-5 h-5" />
                            </span>
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Types documentaires</h1>
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                {documentTypes.length}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-1">
                            Gérez le catalogue des types de documents autorisés (Factures, Contrats, Bulletins...), leurs rattachements aux Directions/Services et leurs métadonnées.
                        </p>
                    </div>
                    {can.create && (
                        <Button variant="primary" onClick={openCreate}>
                            <Plus className="w-4 h-4 mr-1.5" />
                            Nouveau type documentaire
                        </Button>
                    )}
                </div>

                {/* Filters Bar */}
                <div className="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs space-y-3">
                    <div className="flex flex-col md:flex-row items-center gap-3">
                        {/* Search Input */}
                        <div className="relative flex-1 w-full">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher par nom ou description..."
                                className="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                            />
                        </div>

                        {/* Filter by Direction */}
                        {effectiveDirections.length > 0 && (
                            <div className="w-full md:w-56">
                                <select
                                    value={selectedDirection}
                                    onChange={(e) => {
                                        setSelectedDirection(e.target.value);
                                        setSelectedService('all');
                                    }}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white text-slate-700"
                                >
                                    <option value="all">Toutes directions</option>
                                    {effectiveDirections.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {/* Filter by Service */}
                        {availableFilterServices.length > 0 && (
                            <div className="w-full md:w-56">
                                <select
                                    value={selectedService}
                                    onChange={(e) => setSelectedService(e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white text-slate-700"
                                >
                                    <option value="all">Tous services</option>
                                    {availableFilterServices.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}

                        {(selectedDirection !== 'all' || selectedService !== 'all' || search) && (
                            <Button variant="ghost" size="sm" onClick={resetFilters}>
                                <X className="w-3.5 h-3.5 mr-1" />
                                Effacer
                            </Button>
                        )}
                    </div>
                </div>

                {/* Table */}
                {filteredDocTypes.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-2xs">
                        <Table headers={['Type documentaire', 'Direction', 'Service', 'Documents', 'Métadonnées', 'Statut', 'Actions']}>
                            {filteredDocTypes.map((t) => (
                                <tr key={t.id} className="hover:bg-slate-50/70 transition">
                                    {/* Name & Desc */}
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <span className="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 shadow-2xs">
                                                <FileStack className="w-5 h-5" />
                                            </span>
                                            <div>
                                                <span className="font-semibold text-sm text-slate-900 block">
                                                    {t.name}
                                                </span>
                                                {t.description && (
                                                    <span className="text-[11px] text-slate-400 block truncate max-w-xs">
                                                        {t.description}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </td>

                                    {/* Direction */}
                                    <td className="px-6 py-4 text-xs font-medium text-slate-700">
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                            <Building2 className="w-3.5 h-3.5 text-slate-500" />
                                            {t.direction_name || t.department_name || 'Général'}
                                        </span>
                                    </td>

                                    {/* Service */}
                                    <td className="px-6 py-4 text-xs text-slate-700">
                                        {t.service_name ? (
                                            <span className="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-medium">
                                                <Network className="w-3 h-3 text-indigo-500" />
                                                {t.service_name}
                                            </span>
                                        ) : (
                                            <span className="text-slate-400 italic">Tous services</span>
                                        )}
                                    </td>

                                    {/* Documents count */}
                                    <td className="px-6 py-4 text-xs font-semibold text-slate-700">
                                        <span className="inline-flex items-center gap-1">
                                            <Files className="w-3.5 h-3.5 text-slate-400" />
                                            {t.documents_count}
                                        </span>
                                    </td>

                                    {/* Metadata definitions */}
                                    <td className="px-6 py-4 text-xs font-semibold text-slate-700">
                                        <Link
                                            href={`/document-types/${t.id}/metadata`}
                                            className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 hover:underline"
                                        >
                                            <Sliders className="w-3.5 h-3.5" />
                                            {t.metadata_count} champ(s)
                                        </Link>
                                    </td>

                                    {/* Status */}
                                    <td className="px-6 py-4">
                                        {t.is_active ? (
                                            <Badge variant="success">Actif</Badge>
                                        ) : (
                                            <Badge variant="neutral">Inactif</Badge>
                                        )}
                                    </td>

                                    {/* Actions */}
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-1.5">
                                            <Link
                                                href={`/document-types/${t.id}/metadata`}
                                                className="p-1.5 text-indigo-600 hover:text-indigo-800 rounded-lg hover:bg-indigo-50 transition"
                                                title="Configurer les champs de métadonnées"
                                            >
                                                <Sliders className="w-4 h-4" />
                                            </Link>
                                            <Link
                                                href={`/folders/${t.id}`}
                                                className="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-100 transition"
                                                title="Ouvrir dans l'explorateur"
                                            >
                                                <FolderOpen className="w-4 h-4" />
                                            </Link>
                                            {can.edit && (
                                                <button
                                                    onClick={() => openEdit(t)}
                                                    className="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition"
                                                    title="Modifier"
                                                >
                                                    <Edit2 className="w-4 h-4" />
                                                </button>
                                            )}
                                            {can.delete && (
                                                <button
                                                    onClick={() => setDeleteDocType(t)}
                                                    className="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                                    title="Supprimer"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </Table>
                    </div>
                ) : (
                    <EmptyState
                        icon={FileStack}
                        title="Aucun type documentaire trouvé"
                        description={
                            search || selectedDirection !== 'all' || selectedService !== 'all'
                                ? "Aucun type documentaire ne correspond à vos filtres de recherche."
                                : "Créez votre premier type documentaire pour organiser vos documents métier."
                        }
                        action={
                            can.create ? (
                                <Button variant="primary" onClick={openCreate}>
                                    <Plus className="w-4 h-4 mr-1.5" />
                                    Créer un type documentaire
                                </Button>
                            ) : null
                        }
                    />
                )}
            </div>

            {/* Modal Création */}
            <Modal
                isOpen={createModalOpen}
                show={createModalOpen}
                onClose={() => {
                    setCreateModalOpen(false);
                    form.reset();
                }}
                title="Nouveau type documentaire"
            >
                <form onSubmit={handleCreate} className="space-y-4">
                    {form.errors.limit && (
                        <div className="p-3 bg-rose-50 border border-rose-200 text-rose-700 text-xs rounded-xl">
                            {form.errors.limit}
                        </div>
                    )}

                    {effectiveDirections.length === 0 ? (
                        <div className="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-sm space-y-2">
                            <p className="font-semibold">Aucune direction configurée</p>
                            <p className="text-xs text-amber-700">
                                Un type documentaire doit être rattaché à une direction de l'organisation. Veuillez d'abord créer au moins une direction.
                            </p>
                            <Link
                                href="/admin/directions"
                                className="inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-800 underline"
                            >
                                Aller à la gestion des directions →
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Direction de rattachement <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={form.data.direction_id}
                                    onChange={(e) => {
                                        form.setData({
                                            ...form.data,
                                            direction_id: e.target.value,
                                            service_id: '',
                                        });
                                    }}
                                    required
                                    className="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                >
                                    <option value="" disabled>Sélectionnez une direction...</option>
                                    {effectiveDirections.map((dir) => (
                                        <option key={dir.id} value={dir.id}>
                                            {dir.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.direction_id && (
                                    <p className="mt-1 text-xs text-rose-500">{form.errors.direction_id}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Service rattaché (optionnel)
                                </label>
                                <select
                                    value={form.data.service_id}
                                    onChange={(e) => form.setData('service_id', e.target.value)}
                                    className="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                                >
                                    <option value="">Tous les services de la direction</option>
                                    {availableCreateServices.map((srv) => (
                                        <option key={srv.id} value={srv.id}>
                                            {srv.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    )}

                    <Input
                        label="Nom du type documentaire"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Ex : Facture client, Contrat RH, Bulletin de paie..."
                        error={form.errors.name}
                        required
                    />

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Description
                        </label>
                        <textarea
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            rows={3}
                            placeholder="Description fonctionnelle du type documentaire..."
                            className="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder:text-slate-400 transition"
                        />
                        {form.errors.description && (
                            <p className="mt-1 text-xs text-rose-500">{form.errors.description}</p>
                        )}
                    </div>

                    <div className="flex justify-end gap-2 pt-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setCreateModalOpen(false)}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            variant="primary"
                            loading={form.processing}
                            disabled={effectiveDirections.length === 0}
                        >
                            Créer le type
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Modification */}
            <Modal
                isOpen={!!editDocType}
                show={!!editDocType}
                onClose={() => {
                    setEditDocType(null);
                    form.reset();
                }}
                title="Modifier le type documentaire"
            >
                <form onSubmit={handleUpdate} className="space-y-4">
                    <Input
                        label="Nom du type documentaire"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        required
                        error={form.errors.name}
                    />

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Description
                        </label>
                        <textarea
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            rows={3}
                            className="w-full text-xs rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 placeholder:text-slate-400 transition"
                        />
                        {form.errors.description && (
                            <p className="mt-1 text-xs text-rose-500">{form.errors.description}</p>
                        )}
                    </div>

                    <div className="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="edit_is_active"
                            checked={form.data.is_active}
                            onChange={(e) => form.setData('is_active', e.target.checked)}
                            className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
                        />
                        <label htmlFor="edit_is_active" className="text-xs font-medium text-slate-700">
                            Type documentaire actif
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                setEditDocType(null);
                                form.reset();
                            }}
                        >
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary" loading={form.processing}>
                            Enregistrer les modifications
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Suppression */}
            <ConfirmDialog
                isOpen={!!deleteDocType}
                onClose={() => setDeleteDocType(null)}
                onConfirm={handleDelete}
                title="Supprimer le type documentaire"
                message={`Êtes-vous sûr de vouloir supprimer le type documentaire '${deleteDocType?.name}' ?`}
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
