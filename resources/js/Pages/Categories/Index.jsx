import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Modal from '../../Components/Modal';
import Table from '../../Components/Table';
import ConfirmDialog from '../../Components/ConfirmDialog';
import EmptyState from '../../Components/EmptyState';
import { Plus, Edit2, Trash2, FolderTree } from 'lucide-react';

export default function CategoriesIndex({ categories = [] }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editCategory, setEditCategory] = useState(null);
    const [deleteCategory, setDeleteCategory] = useState(null);

    const form = useForm({
        name: '',
        description: '',
    });

    const handleCreate = (e) => {
        e.preventDefault();
        form.post('/categories', {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editCategory) return;
        form.put(`/categories/${editCategory.id}`, {
            onSuccess: () => {
                setEditCategory(null);
                form.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteCategory) return;
        router.delete(`/categories/${deleteCategory.id}`, {
            onSuccess: () => setDeleteCategory(null),
        });
    };

    const openEdit = (c) => {
        setEditCategory(c);
        form.setData({
            name: c.name,
            description: c.description || '',
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des catégories" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Catégories</h1>
                        <p className="text-xs text-slate-500 mt-1">Gérez le classement documentaire de votre organisation.</p>
                    </div>
                    <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                        <Plus className="w-4 h-4" />
                        Nouvelle catégorie
                    </Button>
                </div>

                {categories.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Nom', 'Description', 'Documents associés', 'Actions']}>
                            {categories.map((cat) => (
                                <tr key={cat.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-2.5">
                                            <span className="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                                <FolderTree className="w-4 h-4" />
                                            </span>
                                            <span className="font-semibold text-sm text-slate-900">{cat.name}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                                        {cat.description || '—'}
                                    </td>
                                    <td className="px-6 py-4 text-xs font-semibold text-slate-600">
                                        {cat.documents_count || 0} document(s)
                                    </td>
                                    <td className="px-6 py-4 text-right text-xs">
                                        <div className="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                onClick={() => openEdit(cat)}
                                                className="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition"
                                                title="Modifier"
                                            >
                                                <Edit2 className="w-4 h-4" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setDeleteCategory(cat)}
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
                        title="Aucune catégorie configurée"
                        description="Créez des catégories (RH, Juridique, Finance...) pour structurer vos classements."
                        actionLabel="Créer une catégorie"
                        onAction={() => setCreateModalOpen(true)}
                    />
                )}
            </div>

            {/* Create Modal */}
            <Modal isOpen={createModalOpen} onClose={() => setCreateModalOpen(false)} title="Nouvelle catégorie">
                <form onSubmit={handleCreate} className="space-y-4">
                    <Input
                        id="cat-name"
                        label="Nom de la catégorie"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Ex: Contrats, Factures..."
                        error={form.errors.name}
                    />
                    <Input
                        id="cat-description"
                        label="Description (optionnelle)"
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                        placeholder="Ex: Documents juridiques et contractuels..."
                        error={form.errors.description}
                    />
                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setCreateModalOpen(false)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={form.processing}>Créer</Button>
                    </div>
                </form>
            </Modal>

            {/* Edit Modal */}
            <Modal isOpen={!!editCategory} onClose={() => setEditCategory(null)} title="Modifier la catégorie">
                <form onSubmit={handleUpdate} className="space-y-4">
                    <Input
                        id="edit-cat-name"
                        label="Nom de la catégorie"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        error={form.errors.name}
                    />
                    <Input
                        id="edit-cat-description"
                        label="Description (optionnelle)"
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                        error={form.errors.description}
                    />
                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setEditCategory(null)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={form.processing}>Enregistrer</Button>
                    </div>
                </form>
            </Modal>

            {/* Delete Dialog */}
            <ConfirmDialog
                isOpen={!!deleteCategory}
                onClose={() => setDeleteCategory(null)}
                onConfirm={handleDelete}
                title="Supprimer la catégorie"
                message={`Êtes-vous sûr de vouloir supprimer la catégorie "${deleteCategory?.name}" ?`}
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
