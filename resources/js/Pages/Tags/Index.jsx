import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Modal from '../../Components/Modal';
import Table from '../../Components/Table';
import ConfirmDialog from '../../Components/ConfirmDialog';
import EmptyState from '../../Components/EmptyState';
import { Plus, Edit2, Trash2, Hash } from 'lucide-react';

export default function TagsIndex({ tags = [] }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editTag, setEditTag] = useState(null);
    const [deleteTag, setDeleteTag] = useState(null);

    const form = useForm({
        name: '',
    });

    const handleCreate = (e) => {
        e.preventDefault();
        form.post('/tags', {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editTag) return;
        form.put(`/tags/${editTag.id}`, {
            onSuccess: () => {
                setEditTag(null);
                form.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteTag) return;
        router.delete(`/tags/${deleteTag.id}`, {
            onSuccess: () => setDeleteTag(null),
        });
    };

    const openEdit = (t) => {
        setEditTag(t);
        form.setData({ name: t.name });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des tags" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Tags transversaux</h1>
                        <p className="text-xs text-slate-500 mt-1">Étiquetez librement vos fichiers par mots-clés dynamiques.</p>
                    </div>
                    <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                        <Plus className="w-4 h-4" />
                        Nouveau tag
                    </Button>
                </div>

                {tags.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Tag', 'Utilisation', 'Actions']}>
                            {tags.map((tag) => (
                                <tr key={tag.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                            <Hash className="w-3 h-3 text-indigo-400" />
                                            {tag.name}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-xs font-semibold text-slate-600">
                                        {tag.documents_count || 0} document(s)
                                    </td>
                                    <td className="px-6 py-4 text-right text-xs">
                                        <div className="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                onClick={() => openEdit(tag)}
                                                className="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition"
                                                title="Modifier"
                                            >
                                                <Edit2 className="w-4 h-4" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setDeleteTag(tag)}
                                                className="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                                title="Supprimer"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </Table>
                    </div>
                ) : (
                    <EmptyState
                        title="Aucun tag défini"
                        description="Créez des étiquettes légères (#urgent, #audit, #2026...) pour faciliter la recherche transversale."
                        actionLabel="Créer un tag"
                        onAction={() => setCreateModalOpen(true)}
                    />
                )}
            </div>

            {/* Create Modal */}
            <Modal isOpen={createModalOpen} onClose={() => setCreateModalOpen(false)} title="Nouveau tag">
                <form onSubmit={handleCreate} className="space-y-4">
                    <Input
                        id="tag-name"
                        label="Libellé du tag"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Ex: urgent, confidentiel..."
                        error={form.errors.name}
                    />
                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setCreateModalOpen(false)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={form.processing}>Créer</Button>
                    </div>
                </form>
            </Modal>

            {/* Edit Modal */}
            <Modal isOpen={!!editTag} onClose={() => setEditTag(null)} title="Modifier le tag">
                <form onSubmit={handleUpdate} className="space-y-4">
                    <Input
                        id="edit-tag-name"
                        label="Libellé du tag"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        error={form.errors.name}
                    />
                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setEditTag(null)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={form.processing}>Enregistrer</Button>
                    </div>
                </form>
            </Modal>

            {/* Delete Dialog */}
            <ConfirmDialog
                isOpen={!!deleteTag}
                onClose={() => setDeleteTag(null)}
                onConfirm={handleDelete}
                title="Supprimer le tag"
                message={`Êtes-vous sûr de vouloir supprimer le tag "${deleteTag?.name}" ?`}
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
