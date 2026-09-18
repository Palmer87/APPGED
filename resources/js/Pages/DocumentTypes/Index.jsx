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
    FolderOpen,
    Sliders,
    Files,
    CheckCircle2,
    Filter
} from 'lucide-react';

export default function DocumentTypesIndex({ documentTypes = [], departments = [], can = {} }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editDocType, setEditDocType] = useState(null);
    const [deleteDocType, setDeleteDocType] = useState(null);
    const [selectedDepartment, setSelectedDepartment] = useState('all');

    const form = useForm({
        name: '',
        description: '',
        parent_id: departments[0]?.id || '',
        is_active: true,
    });

    const filteredDocTypes = useMemo(() => {
        if (selectedDepartment === 'all') return documentTypes;
        return documentTypes.filter((t) => String(t.parent_id) === String(selectedDepartment));
    }, [documentTypes, selectedDepartment]);

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

    return (
        <AuthenticatedLayout>
            <Head title="Types documentaires" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Types documentaires</h1>
                        <p className="text-xs text-slate-500 mt-1">
                            Définissez vos types de documents (Facture, Contrat, Devis...) rattachés aux directions et configurez leurs métadonnées.
                        </p>
                    </div>
                    {can.create && (
                        <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                            <Plus className="w-4 h-4 mr-1.5" />
                            Nouveau type documentaire
                        </Button>
                    )}
                </div>

                {/* Filtre par Direction */}
                {departments.length > 0 && (
                    <div className="flex items-center gap-2 overflow-x-auto pb-1">
                        <span className="text-xs font-semibold text-slate-500 flex items-center gap-1 shrink-0 mr-1">
                            <Filter className="w-3.5 h-3.5" />
                            Direction :
                        </span>
                        <button
                            onClick={() => setSelectedDepartment('all')}
                            className={`px-3 py-1.5 rounded-xl text-xs font-semibold transition shrink-0 ${
                                selectedDepartment === 'all'
                                    ? 'bg-blue-600 text-white shadow-xs'
                                    : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                            }`}
                        >
                            Toutes ({documentTypes.length})
                        </button>
                        {departments.map((dept) => {
                            const count = documentTypes.filter((t) => t.parent_id === dept.id).length;
                            return (
                                <button
                                    key={dept.id}
                                    onClick={() => setSelectedDepartment(String(dept.id))}
                                    className={`px-3 py-1.5 rounded-xl text-xs font-semibold transition shrink-0 ${
                                        String(selectedDepartment) === String(dept.id)
                                            ? 'bg-blue-600 text-white shadow-xs'
                                            : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                                    }`}
                                >
                                    {dept.name} ({count})
                                </button>
                            );
                        })}
                    </div>
                )}

                {filteredDocTypes.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Type documentaire', 'Direction', 'Description', 'Documents', 'Métadonnées', 'Statut', 'Actions']}>
                            {filteredDocTypes.map((t) => (
                                <tr key={t.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <span className="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 shadow-2xs">
                                                <FileStack className="w-5 h-5" />
                                            </span>
                                            <div>
                                                <Link
                                                    href={`/folders/${t.id}`}
                                                    className="font-semibold text-sm text-slate-900 hover:text-indigo-600 transition"
                                                >
                                                    {t.name}
                                                </Link>
                                                <div className="text-[11px] text-slate-400">Classement documentaire</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-xs font-medium text-slate-700">
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700">
                                            <Building2 className="w-3.5 h-3.5 text-slate-500" />
                                            {t.department_name}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                                        {t.description || '—'}
                                    </td>
                                    <td className="px-6 py-4 text-xs font-semibold text-slate-700">
                                        <span className="inline-flex items-center gap-1">
                                            <Files className="w-3.5 h-3.5 text-slate-400" />
                                            {t.documents_count}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-xs font-semibold text-slate-700">
                                        <Link
                                            href={`/document-types/${t.id}/metadata`}
                                            className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 hover:underline"
                                        >
                                            <Sliders className="w-3.5 h-3.5" />
                                            {t.metadata_count} champ(s)
                                        </Link>
                                    </td>
                                    <td className="px-6 py-4">
                                        {t.is_active ? (
                                            <Badge variant="success">Actif</Badge>
                                        ) : (
                                            <Badge variant="neutral">Inactif</Badge>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-1.5">
                                            <Link
                                                href={`/document-types/${t.id}/metadata`}
                                                className="p-1.5 text-indigo-600 hover:text-indigo-800 rounded-lg hover:bg-indigo-50 transition"
                                                title="Configurer les métadonnées"
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
                        title="Aucun type documentaire"
                        description="Créez vos types documentaires pour classer vos documents selon vos règles métier."
                        actionLabel={can.create ? 'Nouveau type documentaire' : null}
                        onAction={() => setCreateModalOpen(true)}
                    />
                )}
            </div>

            {/* Modal Création */}
            <Modal
                show={createModalOpen}
                onClose={() => {
                    setCreateModalOpen(false);
                    form.reset();
                }}
                title="Nouveau type documentaire"
            >
                <form onSubmit={handleCreate} className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Direction de rattachement <span className="text-rose-500">*</span>
                        </label>
                        <select
                            value={form.data.parent_id}
                            onChange={(e) => form.setData('parent_id', e.target.value)}
                            required
                            className="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="" disabled>Sélectionnez une direction...</option>
                            {departments.map((dept) => (
                                <option key={dept.id} value={dept.id}>
                                    {dept.name}
                                </option>
                            ))}
                        </select>
                        {form.errors.parent_id && (
                            <p className="mt-1 text-xs text-rose-500">{form.errors.parent_id}</p>
                        )}
                    </div>

                    <Input
                        label="Nom du type documentaire"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Ex : Facture client, Contrat RH, Bon de commande..."
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
                            placeholder="Description des documents appartenant à ce type..."
                            className="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-slate-400 transition"
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
                        <Button type="submit" variant="primary" loading={form.processing}>
                            Créer le type
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Modification */}
            <Modal
                show={!!editDocType}
                onClose={() => {
                    setEditDocType(null);
                    form.reset();
                }}
                title="Modifier le type documentaire"
            >
                <form onSubmit={handleUpdate} className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Direction de rattachement
                        </label>
                        <select
                            value={form.data.parent_id}
                            onChange={(e) => form.setData('parent_id', e.target.value)}
                            className="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            {departments.map((dept) => (
                                <option key={dept.id} value={dept.id}>
                                    {dept.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <Input
                        label="Nom du type documentaire"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
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
                            className="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                        />
                    </div>

                    <div className="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="doc_type_is_active"
                            checked={form.data.is_active}
                            onChange={(e) => form.setData('is_active', e.target.checked)}
                            className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label htmlFor="doc_type_is_active" className="text-xs font-medium text-slate-700">
                            Type documentaire actif
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setEditDocType(null)}
                        >
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary" loading={form.processing}>
                            Enregistrer
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Dialog Suppression */}
            <ConfirmDialog
                show={!!deleteDocType}
                onClose={() => setDeleteDocType(null)}
                onConfirm={handleDelete}
                title="Supprimer le type documentaire ?"
                message={`Êtes-vous sûr de vouloir supprimer le type documentaire "${deleteDocType?.name}" ? Cette action est bloquée si des documents y sont rattachés.`}
                confirmText="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
