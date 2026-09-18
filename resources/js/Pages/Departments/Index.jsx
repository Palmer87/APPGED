import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Modal from '../../Components/Modal';
import Table from '../../Components/Table';
import Badge from '../../Components/Badge';
import ConfirmDialog from '../../Components/ConfirmDialog';
import EmptyState from '../../Components/EmptyState';
import { Plus, Edit2, Trash2, Building2, FolderOpen, Layers, CheckCircle2, XCircle } from 'lucide-react';

export default function DepartmentsIndex({ departments = [], can = {} }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editDepartment, setEditDepartment] = useState(null);
    const [deleteDepartment, setDeleteDepartment] = useState(null);

    const form = useForm({
        name: '',
        description: '',
        is_active: true,
    });

    const handleCreate = (e) => {
        e.preventDefault();
        form.post('/departments', {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editDepartment) return;
        form.put(`/departments/${editDepartment.id}`, {
            onSuccess: () => {
                setEditDepartment(null);
                form.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteDepartment) return;
        router.delete(`/departments/${deleteDepartment.id}`, {
            onSuccess: () => setDeleteDepartment(null),
        });
    };

    const openEdit = (d) => {
        setEditDepartment(d);
        form.setData({
            name: d.name,
            description: d.description || '',
            is_active: d.is_active,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des Directions" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Directions</h1>
                        <p className="text-xs text-slate-500 mt-1">
                            Gérez les départements racine de votre organisation (Comptabilité, RH, Commercial, etc.).
                        </p>
                    </div>
                    {can.create && (
                        <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                            <Plus className="w-4 h-4 mr-1.5" />
                            Nouvelle direction
                        </Button>
                    )}
                </div>

                {departments.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Nom', 'Description', 'Types doc', 'Documents', 'Statut', 'Actions']}>
                            {departments.map((dept) => (
                                <tr key={dept.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <span className="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 shadow-2xs">
                                                <Building2 className="w-5 h-5" />
                                            </span>
                                            <div>
                                                <Link
                                                    href={`/folders/${dept.id}`}
                                                    className="font-semibold text-sm text-slate-900 hover:text-blue-600 transition"
                                                >
                                                    {dept.name}
                                                </Link>
                                                <div className="text-[11px] text-slate-400">Dossier racine</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                                        {dept.description || '—'}
                                    </td>
                                    <td className="px-6 py-4 text-xs font-medium text-slate-600">
                                        <span className="inline-flex items-center gap-1">
                                            <Layers className="w-3.5 h-3.5 text-slate-400" />
                                            {dept.document_types_count} type(s)
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 text-xs font-medium text-slate-600">
                                        {dept.documents_count} document(s)
                                    </td>
                                    <td className="px-6 py-4">
                                        {dept.is_active ? (
                                            <Badge variant="success">Actif</Badge>
                                        ) : (
                                            <Badge variant="neutral">Inactif</Badge>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-1.5">
                                            <Link
                                                href={`/folders/${dept.id}`}
                                                className="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-100 transition"
                                                title="Ouvrir dans l'explorateur"
                                            >
                                                <FolderOpen className="w-4 h-4" />
                                            </Link>
                                            {can.edit && (
                                                <button
                                                    onClick={() => openEdit(dept)}
                                                    className="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition"
                                                    title="Modifier"
                                                >
                                                    <Edit2 className="w-4 h-4" />
                                                </button>
                                            )}
                                            {can.delete && (
                                                <button
                                                    onClick={() => setDeleteDepartment(dept)}
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
                        icon={Building2}
                        title="Aucune direction"
                        description="Créez votre première direction pour organiser vos types documentaires et documents."
                        actionLabel={can.create ? 'Créer une direction' : null}
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
                title="Nouvelle direction"
            >
                <form onSubmit={handleCreate} className="space-y-4">
                    <Input
                        label="Nom de la direction"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        placeholder="Ex : Comptabilité, Ressources Humaines..."
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
                            placeholder="Description du rôle et des missions de cette direction..."
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
                            Créer la direction
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Modification */}
            <Modal
                show={!!editDepartment}
                onClose={() => {
                    setEditDepartment(null);
                    form.reset();
                }}
                title="Modifier la direction"
            >
                <form onSubmit={handleUpdate} className="space-y-4">
                    <Input
                        label="Nom de la direction"
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
                        {form.errors.description && (
                            <p className="mt-1 text-xs text-rose-500">{form.errors.description}</p>
                        )}
                    </div>

                    <div className="flex items-center gap-2 pt-1">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={form.data.is_active}
                            onChange={(e) => form.setData('is_active', e.target.checked)}
                            className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label htmlFor="is_active" className="text-xs font-medium text-slate-700">
                            Direction active
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setEditDepartment(null)}
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
                show={!!deleteDepartment}
                onClose={() => setDeleteDepartment(null)}
                onConfirm={handleDelete}
                title="Supprimer la direction ?"
                message={`Êtes-vous sûr de vouloir supprimer la direction "${deleteDepartment?.name}" ? Cette action n'est possible que si la direction ne contient pas de documents.`}
                confirmText="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
