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
import { Plus, Edit2, Trash2, Building2, Network, FileText, CheckCircle2, XCircle, Search } from 'lucide-react';

export default function DirectionsIndex({ directions = [], can = {} }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editDirection, setEditDirection] = useState(null);
    const [deleteDirection, setDeleteDirection] = useState(null);
    const [search, setSearch] = useState('');

    const form = useForm({
        name: '',
        code: '',
        description: '',
        is_active: true,
    });

    const filteredDirections = directions.filter((d) => {
        if (!search) return true;
        const q = search.toLowerCase();
        return d.name?.toLowerCase().includes(q) || d.code?.toLowerCase().includes(q) || d.description?.toLowerCase().includes(q);
    });

    const handleCreate = (e) => {
        e.preventDefault();
        form.post('/directions', {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editDirection) return;
        form.put(`/directions/${editDirection.id}`, {
            onSuccess: () => {
                setEditDirection(null);
                form.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteDirection) return;
        router.delete(`/directions/${deleteDirection.id}`, {
            onSuccess: () => setDeleteDirection(null),
        });
    };

    const openEdit = (d) => {
        setEditDirection(d);
        form.setData({
            name: d.name,
            code: d.code || '',
            description: d.description || '',
            is_active: d.is_active,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des Directions (V2)" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 rounded-xl bg-blue-50 text-blue-600">
                                <Building2 className="w-5 h-5" />
                            </span>
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Directions</h1>
                            <span className="px-2 py-0.5 text-xs font-semibold bg-blue-100 text-blue-700 rounded-full">
                                {directions.length}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-1">
                            Structure organisationnelle de haut niveau. Chaque direction regroupe des services métiers.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {can.create && (
                            <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                                <Plus className="w-4 h-4 mr-1.5" />
                                Nouvelle direction
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filter bar */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div className="relative w-full sm:w-80">
                        <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            type="text"
                            placeholder="Rechercher une direction..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                        />
                    </div>
                    <div className="text-xs text-slate-500">
                        Total services rattachés : <span className="font-semibold text-slate-700">{directions.reduce((acc, d) => acc + (d.services_count || 0), 0)}</span>
                    </div>
                </div>

                {/* List Table */}
                {filteredDirections.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Nom & Code', 'Description', 'Services rattachés', 'Documents', 'Statut', 'Actions']}>
                            {filteredDirections.map((dir) => (
                                <tr key={dir.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <span className="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 shadow-2xs">
                                                <Building2 className="w-5 h-5" />
                                            </span>
                                            <div>
                                                <div className="font-semibold text-sm text-slate-900">{dir.name}</div>
                                                {dir.code && (
                                                    <span className="inline-block mt-0.5 px-1.5 py-0.2 text-[10px] font-mono font-medium bg-slate-100 text-slate-600 rounded">
                                                        {dir.code}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-500 max-w-xs truncate">
                                        {dir.description || <span className="text-slate-300 italic">Aucune description</span>}
                                    </td>
                                    <td className="px-6 py-4">
                                        <Link
                                            href={`/services?direction_id=${dir.id}`}
                                            className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-semibold text-xs transition"
                                        >
                                            <Network className="w-3.5 h-3.5" />
                                            <span>{dir.services_count ?? 0} service{dir.services_count > 1 ? 's' : ''}</span>
                                        </Link>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-1.5 text-xs text-slate-600 font-medium">
                                            <FileText className="w-3.5 h-3.5 text-slate-400" />
                                            <span>{dir.documents_count ?? 0}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        {dir.is_active ? (
                                            <Badge variant="success" className="inline-flex items-center gap-1">
                                                <CheckCircle2 className="w-3 h-3" /> Actif
                                            </Badge>
                                        ) : (
                                            <Badge variant="danger" className="inline-flex items-center gap-1">
                                                <XCircle className="w-3 h-3" /> Inactif
                                            </Badge>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 text-right">
                                        <div className="flex items-center justify-end gap-1.5">
                                            <Link
                                                href={`/services?direction_id=${dir.id}`}
                                                className="px-2 py-1 text-xs font-medium text-slate-600 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition"
                                                title="Voir les services"
                                            >
                                                Voir
                                            </Link>
                                            {can.update && (
                                                <button
                                                    onClick={() => openEdit(dir)}
                                                    className="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition"
                                                    title="Modifier"
                                                >
                                                    <Edit2 className="w-4 h-4" />
                                                </button>
                                            )}
                                            {can.delete && (
                                                <button
                                                    onClick={() => setDeleteDirection(dir)}
                                                    className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition"
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
                        title="Aucune direction trouvée"
                        description={search ? "Aucun résultat pour cette recherche." : "Créez votre première direction pour organiser vos services métiers."}
                        action={
                            can.create && !search ? (
                                <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                                    <Plus className="w-4 h-4 mr-1.5" />
                                    Créer une direction
                                </Button>
                            ) : null
                        }
                    />
                )}
            </div>

            {/* Modal Création */}
            <Modal show={createModalOpen} onClose={() => setCreateModalOpen(false)}>
                <form onSubmit={handleCreate} className="p-6 space-y-5">
                    <div className="flex items-center gap-3">
                        <span className="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                            <Building2 className="w-5 h-5" />
                        </span>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900">Nouvelle Direction</h3>
                            <p className="text-xs text-slate-500">Ajoutez une direction principale à votre organisation.</p>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Nom de la direction <span className="text-rose-500">*</span>
                            </label>
                            <Input
                                placeholder="ex. Direction Administrative & Financière"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                error={form.errors.name}
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">Code / Sigle (optionnel)</label>
                            <Input
                                placeholder="ex. DAF"
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value)}
                                error={form.errors.code}
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">Description (optionnel)</label>
                            <textarea
                                rows={3}
                                placeholder="Description des attributions de la direction..."
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                            />
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="create_is_active"
                                checked={form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                                className="rounded text-blue-600 focus:ring-blue-500 w-4 h-4"
                            />
                            <label htmlFor="create_is_active" className="text-xs font-medium text-slate-700">
                                Direction active
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <Button type="button" variant="secondary" onClick={() => setCreateModalOpen(false)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary" disabled={form.processing}>
                            {form.processing ? 'Création...' : 'Créer la direction'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Modification */}
            <Modal show={!!editDirection} onClose={() => setEditDirection(null)}>
                <form onSubmit={handleUpdate} className="p-6 space-y-5">
                    <div className="flex items-center gap-3">
                        <span className="p-2.5 rounded-xl bg-amber-50 text-amber-600">
                            <Edit2 className="w-5 h-5" />
                        </span>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900">Modifier la Direction</h3>
                            <p className="text-xs text-slate-500">Mettez à jour les informations de cette direction.</p>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Nom de la direction <span className="text-rose-500">*</span>
                            </label>
                            <Input
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                error={form.errors.name}
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">Code / Sigle</label>
                            <Input
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value)}
                                error={form.errors.code}
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">Description</label>
                            <textarea
                                rows={3}
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                            />
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="edit_is_active"
                                checked={form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                                className="rounded text-blue-600 focus:ring-blue-500 w-4 h-4"
                            />
                            <label htmlFor="edit_is_active" className="text-xs font-medium text-slate-700">
                                Direction active
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <Button type="button" variant="secondary" onClick={() => setEditDirection(null)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary" disabled={form.processing}>
                            {form.processing ? 'Enregistrement...' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Confirmation Suppression */}
            <ConfirmDialog
                isOpen={!!deleteDirection}
                onClose={() => setDeleteDirection(null)}
                onConfirm={handleDelete}
                title="Supprimer la direction ?"
                message={`Êtes-vous sûr de vouloir supprimer la direction "${deleteDirection?.name}" ? Cette action n'est possible que si aucun service ou document n'y est rattaché.`}
                confirmText="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
