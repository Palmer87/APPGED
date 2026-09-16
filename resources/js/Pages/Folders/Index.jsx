import React, { useState } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Select from '../../Components/Select';
import Textarea from '../../Components/Textarea';
import Modal from '../../Components/Modal';
import EmptyState from '../../Components/EmptyState';
import ConfirmDialog from '../../Components/ConfirmDialog';
import { Folder as FolderIcon, Plus, Edit2, Trash2, ChevronRight, Files } from 'lucide-react';

export default function FoldersIndex({ folders = [] }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editFolder, setEditFolder] = useState(null);
    const [deleteFolder, setDeleteFolder] = useState(null);

    const form = useForm({
        name: '',
        description: '',
        parent_id: '',
    });

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        form.post('/folders', {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
        });
    };

    const handleUpdateSubmit = (e) => {
        e.preventDefault();
        if (!editFolder) return;
        form.put(`/folders/${editFolder.id}`, {
            onSuccess: () => {
                setEditFolder(null);
                form.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteFolder) return;
        router.delete(`/folders/${deleteFolder.id}`, {
            onSuccess: () => setDeleteFolder(null),
        });
    };

    const openEdit = (folder) => {
        setEditFolder(folder);
        form.setData({
            name: folder.name,
            description: folder.description || '',
            parent_id: folder.parent_id || '',
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des dossiers" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Dossiers</h1>
                        <p className="text-xs text-slate-500 mt-1">Structurez l'arborescence de classement de vos documents.</p>
                    </div>
                    <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                        <Plus className="w-4 h-4" />
                        Nouveau dossier
                    </Button>
                </div>

                {folders.length > 0 ? (
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        {folders.map((folder) => (
                            <div
                                key={folder.id}
                                className="group relative rounded-xl border border-slate-200 bg-white p-4 shadow-2xs hover:shadow-md hover:border-indigo-200 transition flex flex-col justify-between"
                            >
                                <div>
                                    <div className="flex items-start justify-between gap-2 mb-3">
                                        <div className="p-2.5 rounded-xl bg-amber-50 text-amber-600 border border-amber-100 group-hover:scale-105 transition">
                                            <FolderIcon className="w-6 h-6 fill-amber-500" />
                                        </div>
                                        <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition">
                                            <button
                                                type="button"
                                                onClick={() => openEdit(folder)}
                                                className="p-1 text-slate-400 hover:text-slate-700"
                                                title="Modifier"
                                            >
                                                <Edit2 className="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setDeleteFolder(folder)}
                                                className="p-1 text-slate-400 hover:text-rose-600"
                                                title="Supprimer"
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </div>

                                    <Link
                                        href={`/documents?folder_id=${folder.id}`}
                                        className="font-bold text-sm text-slate-900 group-hover:text-indigo-600 block truncate"
                                    >
                                        {folder.name}
                                    </Link>

                                    {folder.parent && (
                                        <span className="text-[11px] text-slate-400 block mt-0.5">
                                            Sous-dossier de {folder.parent.name}
                                        </span>
                                    )}

                                    {folder.description && (
                                        <p className="text-xs text-slate-500 line-clamp-2 mt-1">{folder.description}</p>
                                    )}
                                </div>

                                <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                                    <span className="inline-flex items-center gap-1">
                                        <Files className="w-3.5 h-3.5 text-slate-400" />
                                        {folder.documents_count || 0} doc(s)
                                    </span>
                                    <Link
                                        href={`/documents?folder_id=${folder.id}`}
                                        className="font-semibold text-indigo-600 hover:text-indigo-800 flex items-center gap-0.5"
                                    >
                                        Ouvrir <ChevronRight className="w-3.5 h-3.5" />
                                    </Link>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        title="Aucun dossier créé"
                        description="Organisez vos fichiers en créant des dossiers thématiques ou fonctionnels."
                        actionLabel="Créer un dossier"
                        onAction={() => setCreateModalOpen(true)}
                    />
                )}
            </div>

            {/* Create Folder Modal */}
            <Modal
                isOpen={createModalOpen}
                onClose={() => setCreateModalOpen(false)}
                title="Créer un nouveau dossier"
            >
                <form onSubmit={handleCreateSubmit} className="space-y-4">
                    <Input
                        id="folder-name"
                        label="Nom du dossier"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Ex: Factures 2026, Contrats RH..."
                        error={form.errors.name}
                    />

                    <Select
                        id="folder-parent"
                        label="Dossier parent (optionnel)"
                        value={form.data.parent_id}
                        onChange={(e) => form.setData('parent_id', e.target.value)}
                        placeholder="Racine (aucun parent)"
                        options={folders.map(f => ({ value: f.id, label: f.name }))}
                        error={form.errors.parent_id}
                    />

                    <Textarea
                        id="folder-desc"
                        label="Description"
                        rows={2}
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                        placeholder="Usage et périmètre du dossier..."
                        error={form.errors.description}
                    />

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setCreateModalOpen(false)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={form.processing}>
                            Créer le dossier
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Edit Folder Modal */}
            <Modal
                isOpen={!!editFolder}
                onClose={() => setEditFolder(null)}
                title="Modifier le dossier"
            >
                <form onSubmit={handleUpdateSubmit} className="space-y-4">
                    <Input
                        id="edit-folder-name"
                        label="Nom du dossier"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        error={form.errors.name}
                    />

                    <Select
                        id="edit-folder-parent"
                        label="Dossier parent"
                        value={form.data.parent_id}
                        onChange={(e) => form.setData('parent_id', e.target.value)}
                        placeholder="Racine (aucun parent)"
                        options={folders.filter(f => f.id !== editFolder?.id).map(f => ({ value: f.id, label: f.name }))}
                        error={form.errors.parent_id}
                    />

                    <Textarea
                        id="edit-folder-desc"
                        label="Description"
                        rows={2}
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                        error={form.errors.description}
                    />

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setEditFolder(null)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={form.processing}>
                            Enregistrer les modifications
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Delete Confirmation */}
            <ConfirmDialog
                isOpen={!!deleteFolder}
                onClose={() => setDeleteFolder(null)}
                onConfirm={handleDelete}
                title="Supprimer le dossier"
                message={`Êtes-vous sûr de vouloir supprimer le dossier "${deleteFolder?.name}" ?`}
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
