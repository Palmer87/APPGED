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
import { Plus, Edit2, Trash2, Network, Building2, Users, FileText, CheckCircle2, XCircle, Search, UserPlus, UserMinus, ShieldCheck } from 'lucide-react';

export default function ServicesIndex({
    services = [],
    directions = [],
    availableUsers = [],
    selectedDirectionId = null,
    can = {},
}) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editService, setEditService] = useState(null);
    const [deleteService, setDeleteService] = useState(null);
    const [manageUsersService, setManageUsersService] = useState(null);
    const [search, setSearch] = useState('');
    const [directionFilter, setDirectionFilter] = useState(selectedDirectionId ? String(selectedDirectionId) : '');

    // Form for create/update service
    const form = useForm({
        direction_id: selectedDirectionId || '',
        name: '',
        code: '',
        description: '',
        is_active: true,
    });

    // Form for assigning user to service
    const userForm = useForm({
        user_id: '',
        is_primary: false,
    });

    const filteredServices = services.filter((s) => {
        const matchesDirection = !directionFilter || String(s.direction_id) === String(directionFilter);
        if (!matchesDirection) return false;
        if (!search) return true;
        const q = search.toLowerCase();
        return s.name?.toLowerCase().includes(q) || s.code?.toLowerCase().includes(q) || s.direction_name?.toLowerCase().includes(q);
    });

    const handleCreate = (e) => {
        e.preventDefault();
        form.post('/services', {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editService) return;
        form.put(`/services/${editService.id}`, {
            onSuccess: () => {
                setEditService(null);
                form.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteService) return;
        router.delete(`/services/${deleteService.id}`, {
            onSuccess: () => setDeleteService(null),
        });
    };

    const handleAssignUser = (e) => {
        e.preventDefault();
        if (!manageUsersService) return;
        userForm.post(`/services/${manageUsersService.id}/users`, {
            onSuccess: () => {
                userForm.reset();
            },
        });
    };

    const handleRemoveUser = (serviceId, userId) => {
        router.delete(`/services/${serviceId}/users/${userId}`);
    };

    const openEdit = (s) => {
        setEditService(s);
        form.setData({
            direction_id: s.direction_id,
            name: s.name,
            code: s.code || '',
            description: s.description || '',
            is_active: s.is_active,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des Services (V2)" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                                <Network className="w-5 h-5" />
                            </span>
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Services</h1>
                            <span className="px-2 py-0.5 text-xs font-semibold bg-indigo-100 text-indigo-700 rounded-full">
                                {services.length}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-1">
                            Rattachement organisationnel des collaborateurs. <span className="font-semibold text-slate-700">SERVICE ≠ PERMISSION</span>.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        {can.create && (
                            <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                                <Plus className="w-4 h-4 mr-1.5" />
                                Nouveau service
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filter bar */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
                    <div className="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                        <div className="relative w-full sm:w-72">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="Rechercher un service..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <select
                            value={directionFilter}
                            onChange={(e) => setDirectionFilter(e.target.value)}
                            className="w-full sm:w-60 px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        >
                            <option value="">Toutes les directions</option>
                            {directions.map((d) => (
                                <option key={d.id} value={d.id}>
                                    {d.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="text-xs text-slate-500">
                        Total utilisateurs rattachés : <span className="font-semibold text-slate-700">{services.reduce((acc, s) => acc + (s.users_count || 0), 0)}</span>
                    </div>
                </div>

                {/* Services Table */}
                {filteredServices.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Nom & Code', 'Direction de rattachement', 'Collaborateurs', 'Documents', 'Statut', 'Actions']}>
                            {filteredServices.map((service) => (
                                <tr key={service.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <span className="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 shadow-2xs">
                                                <Network className="w-5 h-5" />
                                            </span>
                                            <div>
                                                <div className="font-semibold text-sm text-slate-900">{service.name}</div>
                                                {service.code && (
                                                    <span className="inline-block mt-0.5 px-1.5 py-0.2 text-[10px] font-mono font-medium bg-slate-100 text-slate-600 rounded">
                                                        {service.code}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-medium">
                                            <Building2 className="w-3.5 h-3.5" />
                                            {service.direction_name || 'Direction'}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4">
                                        <button
                                            onClick={() => setManageUsersService(service)}
                                            className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 font-semibold text-xs transition"
                                        >
                                            <Users className="w-3.5 h-3.5" />
                                            <span>{service.users_count ?? 0} collaborateur{service.users_count > 1 ? 's' : ''}</span>
                                        </button>
                                    </td>
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-1.5 text-xs text-slate-600 font-medium">
                                            <FileText className="w-3.5 h-3.5 text-slate-400" />
                                            <span>{service.documents_count ?? 0}</span>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4">
                                        {service.is_active ? (
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
                                            <button
                                                onClick={() => setManageUsersService(service)}
                                                className="px-2 py-1 text-xs font-semibold text-emerald-600 hover:bg-emerald-50 rounded-lg transition"
                                                title="Gérer les utilisateurs"
                                            >
                                                Membres
                                            </button>
                                            {can.update && (
                                                <button
                                                    onClick={() => openEdit(service)}
                                                    className="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg transition"
                                                    title="Modifier"
                                                >
                                                    <Edit2 className="w-4 h-4" />
                                                </button>
                                            )}
                                            {can.delete && (
                                                <button
                                                    onClick={() => setDeleteService(service)}
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
                        icon={Network}
                        title="Aucun service trouvé"
                        description={search || directionFilter ? "Aucun service ne correspond à ces critères." : "Créez un premier service rattaché à une direction."}
                        action={
                            can.create && !search && !directionFilter ? (
                                <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                                    <Plus className="w-4 h-4 mr-1.5" />
                                    Créer un service
                                </Button>
                            ) : null
                        }
                    />
                )}
            </div>

            {/* Modal Création Service */}
            <Modal show={createModalOpen} onClose={() => setCreateModalOpen(false)}>
                <form onSubmit={handleCreate} className="p-6 space-y-5">
                    <div className="flex items-center gap-3">
                        <span className="p-2.5 rounded-xl bg-indigo-50 text-indigo-600">
                            <Network className="w-5 h-5" />
                        </span>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900">Nouveau Service</h3>
                            <p className="text-xs text-slate-500">Ajoutez un service métier rattaché à une Direction.</p>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Direction parente <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={form.data.direction_id}
                                onChange={(e) => form.setData('direction_id', e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                required
                            >
                                <option value="">Sélectionner une direction</option>
                                {directions.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                            {form.errors.direction_id && <p className="text-xs text-rose-500 mt-1">{form.errors.direction_id}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Nom du service <span className="text-rose-500">*</span>
                            </label>
                            <Input
                                placeholder="ex. Comptabilité, Recrutement, Trésorerie..."
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                error={form.errors.name}
                                required
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">Code / Sigle (optionnel)</label>
                            <Input
                                placeholder="ex. COMPTA, REC"
                                value={form.data.code}
                                onChange={(e) => form.setData('code', e.target.value)}
                                error={form.errors.code}
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">Description (optionnel)</label>
                            <textarea
                                rows={3}
                                placeholder="Description de l'activité du service..."
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="create_srv_active"
                                checked={form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                                className="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                            />
                            <label htmlFor="create_srv_active" className="text-xs font-medium text-slate-700">
                                Service actif
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <Button type="button" variant="secondary" onClick={() => setCreateModalOpen(false)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary" disabled={form.processing}>
                            {form.processing ? 'Création...' : 'Créer le service'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Modification Service */}
            <Modal show={!!editService} onClose={() => setEditService(null)}>
                <form onSubmit={handleUpdate} className="p-6 space-y-5">
                    <div className="flex items-center gap-3">
                        <span className="p-2.5 rounded-xl bg-amber-50 text-amber-600">
                            <Edit2 className="w-5 h-5" />
                        </span>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900">Modifier le Service</h3>
                            <p className="text-xs text-slate-500">Mettez à jour les informations du service.</p>
                        </div>
                    </div>

                    <div className="space-y-4">
                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Direction parente <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={form.data.direction_id}
                                onChange={(e) => form.setData('direction_id', e.target.value)}
                                className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                required
                            >
                                {directions.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Nom du service <span className="text-rose-500">*</span>
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
                                className="w-full px-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                        </div>

                        <div className="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="edit_srv_active"
                                checked={form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                                className="rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                            />
                            <label htmlFor="edit_srv_active" className="text-xs font-medium text-slate-700">
                                Service actif
                            </label>
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <Button type="button" variant="secondary" onClick={() => setEditService(null)}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary" disabled={form.processing}>
                            {form.processing ? 'Enregistrement...' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Modal Gestion des Collaborateurs du Service */}
            <Modal show={!!manageUsersService} onClose={() => setManageUsersService(null)} maxWidth="lg">
                <div className="p-6 space-y-5">
                    <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div className="flex items-center gap-3">
                            <span className="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                                <Users className="w-5 h-5" />
                            </span>
                            <div>
                                <h3 className="text-base font-bold text-slate-900">
                                    Collaborateurs de {manageUsersService?.name}
                                </h3>
                                <p className="text-xs text-slate-500">
                                    Direction : {manageUsersService?.direction_name}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Add user form */}
                    <form onSubmit={handleAssignUser} className="p-3.5 bg-slate-50 rounded-xl border border-slate-200/70 space-y-3">
                        <div className="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                            <UserPlus className="w-3.5 h-3.5 text-emerald-600" />
                            Affecter un collaborateur
                        </div>
                        <div className="flex flex-col sm:flex-row gap-2">
                            <select
                                value={userForm.data.user_id}
                                onChange={(e) => userForm.setData('user_id', e.target.value)}
                                className="flex-1 px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                                required
                            >
                                <option value="">Sélectionner un utilisateur</option>
                                {availableUsers
                                    .filter((u) => !manageUsersService?.users?.some((mu) => mu.id === u.id))
                                    .map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name} ({u.email})
                                        </option>
                                    ))}
                            </select>

                            <Button type="submit" variant="primary" disabled={userForm.processing}>
                                {userForm.processing ? 'Ajout...' : 'Affecter'}
                            </Button>
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="assign_is_primary"
                                checked={userForm.data.is_primary}
                                onChange={(e) => userForm.setData('is_primary', e.target.checked)}
                                className="rounded text-emerald-600 focus:ring-emerald-500 w-3.5 h-3.5"
                            />
                            <label htmlFor="assign_is_primary" className="text-[11px] text-slate-600">
                                Définir comme son <span className="font-semibold text-slate-800">service principal</span>
                            </label>
                        </div>
                    </form>

                    {/* Current users list */}
                    <div className="space-y-2">
                        <div className="text-xs font-bold text-slate-700">
                            Membres actuels ({manageUsersService?.users?.length ?? 0})
                        </div>
                        {manageUsersService?.users && manageUsersService.users.length > 0 ? (
                            <div className="max-h-60 overflow-y-auto divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white">
                                {manageUsersService.users.map((u) => (
                                    <div key={u.id} className="flex items-center justify-between p-3 hover:bg-slate-50 transition">
                                        <div>
                                            <div className="font-semibold text-xs text-slate-900 flex items-center gap-1.5">
                                                <span>{u.name}</span>
                                                {u.is_primary && (
                                                    <span className="px-1.5 py-0.2 text-[9px] font-bold bg-amber-100 text-amber-800 rounded">
                                                        Principal
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-[10px] text-slate-400">{u.email}</div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => handleRemoveUser(manageUsersService.id, u.id)}
                                            className="p-1 text-slate-400 hover:text-rose-600 rounded transition"
                                            title="Retirer du service"
                                        >
                                            <UserMinus className="w-4 h-4" />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-xs text-slate-400 italic py-3 text-center">
                                Aucun collaborateur n'est encore affecté à ce service.
                            </p>
                        )}
                    </div>

                    <div className="flex justify-end pt-3 border-t border-slate-100">
                        <Button type="button" variant="secondary" onClick={() => setManageUsersService(null)}>
                            Fermer
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* Confirmation Suppression */}
            <ConfirmDialog
                isOpen={!!deleteService}
                onClose={() => setDeleteService(null)}
                onConfirm={handleDelete}
                title="Supprimer le service ?"
                message={`Êtes-vous sûr de vouloir supprimer le service "${deleteService?.name}" ? Cette action est irréversible si aucun document n'y est rattaché.`}
                confirmText="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
