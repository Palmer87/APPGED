import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Select from '../../Components/Select';
import Textarea from '../../Components/Textarea';
import Modal from '../../Components/Modal';
import Table from '../../Components/Table';
import Badge from '../../Components/Badge';
import ConfirmDialog from '../../Components/ConfirmDialog';
import EmptyState from '../../Components/EmptyState';
import { Plus, Edit2, Trash2, FileSpreadsheet } from 'lucide-react';

export default function MetadataIndex({ definitions = [], allowedTypes = [] }) {
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editDef, setEditDef] = useState(null);
    const [deleteDef, setDeleteDef] = useState(null);

    const form = useForm({
        name: '',
        key: '',
        type: 'string',
        description: '',
        is_required: false,
        is_active: true,
    });

    const handleCreate = (e) => {
        e.preventDefault();
        form.post('/metadata', {
            onSuccess: () => {
                setCreateModalOpen(false);
                form.reset();
            },
        });
    };

    const handleUpdate = (e) => {
        e.preventDefault();
        if (!editDef) return;
        form.put(`/metadata/${editDef.id}`, {
            onSuccess: () => {
                setEditDef(null);
                form.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteDef) return;
        router.delete(`/metadata/${deleteDef.id}`, {
            onSuccess: () => setDeleteDef(null),
        });
    };

    const openEdit = (d) => {
        setEditDef(d);
        form.setData({
            name: d.name,
            key: d.key,
            type: d.type,
            description: d.description || '',
            is_required: !!d.is_required,
            is_active: !!d.is_active,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Métadonnées personnalisées" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Métadonnées personnalisées</h1>
                        <p className="text-xs text-slate-500 mt-1">Définissez des champs sur-mesure pour indexer précisément vos documents métier.</p>
                    </div>
                    <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                        <Plus className="w-4 h-4" />
                        Nouveau champ
                    </Button>
                </div>

                {definitions.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Libellé', 'Clé système', 'Type', 'Requis', 'Statut', 'Valeurs associées', 'Actions']}>
                            {definitions.map((def) => (
                                <tr key={def.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="font-semibold text-sm text-slate-900">{def.name}</div>
                                        {def.description && <p className="text-xs text-slate-400">{def.description}</p>}
                                    </td>
                                    <td className="px-6 py-4 font-mono text-xs text-slate-500">
                                        {def.key}
                                    </td>
                                    <td className="px-6 py-4 text-xs font-semibold text-indigo-600 uppercase">
                                        {def.type}
                                    </td>
                                    <td className="px-6 py-4 text-xs">
                                        {def.is_required ? <Badge variant="danger" size="sm">Oui</Badge> : <Badge variant="default" size="sm">Non</Badge>}
                                    </td>
                                    <td className="px-6 py-4 text-xs">
                                        {def.is_active ? <Badge variant="success" size="sm">Actif</Badge> : <Badge variant="default" size="sm">Inactif</Badge>}
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-600 font-semibold">
                                        {def.values_count || 0}
                                    </td>
                                    <td className="px-6 py-4 text-right text-xs">
                                        <div className="flex items-center justify-end gap-1.5">
                                            <button
                                                type="button"
                                                onClick={() => openEdit(def)}
                                                className="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition"
                                                title="Modifier"
                                            >
                                                <Edit2 className="w-4 h-4" />
                                            </button>
                                            <button
                                                type="button"
                                                onClick={() => setDeleteDef(def)}
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
                        title="Aucun champ personnalisé défini"
                        description="Créez des métadonnées (Référence contrat, Montant, Client, Date d'échéance...) pour enrichir vos documents."
                        actionLabel="Créer un champ"
                        onAction={() => setCreateModalOpen(true)}
                    />
                )}
            </div>

            {/* Create Modal */}
            <Modal isOpen={createModalOpen} onClose={() => setCreateModalOpen(false)} title="Nouveau champ de métadonnée">
                <form onSubmit={handleCreate} className="space-y-4">
                    <Input
                        id="meta-name"
                        label="Libellé du champ"
                        required
                        value={form.data.name}
                        onChange={(e) => {
                            const val = e.target.value;
                            form.setData({
                                ...form.data,
                                name: val,
                                key: form.data.key || val.toLowerCase().replace(/[^a-z0-9]/g, '_').replace(/^_+|_+$/g, ''),
                            });
                        }}
                        placeholder="Ex: Référence Contrat, Montant HT..."
                        error={form.errors.name}
                    />

                    <Input
                        id="meta-key"
                        label="Clé unique (minuscules, chiffres et tirets bas)"
                        required
                        value={form.data.key}
                        onChange={(e) => form.setData('key', e.target.value)}
                        placeholder="Ex: reference_contrat, montant_ht"
                        error={form.errors.key}
                    />

                    <Select
                        id="meta-type"
                        label="Type de données"
                        required
                        value={form.data.type}
                        onChange={(e) => form.setData('type', e.target.value)}
                        options={allowedTypes.map(t => ({ value: t, label: t.toUpperCase() }))}
                        error={form.errors.type}
                    />

                    <Textarea
                        id="meta-desc"
                        label="Description (aide à la saisie)"
                        rows={2}
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                    />

                    <div className="flex items-center gap-6 pt-2">
                        <label className="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={form.data.is_required}
                                onChange={(e) => form.setData('is_required', e.target.checked)}
                                className="rounded text-indigo-600 focus:ring-indigo-500"
                            />
                            Champ obligatoire
                        </label>
                        <label className="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                                className="rounded text-indigo-600 focus:ring-indigo-500"
                            />
                            Champ actif
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setCreateModalOpen(false)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={form.processing}>Créer le champ</Button>
                    </div>
                </form>
            </Modal>

            {/* Edit Modal */}
            <Modal isOpen={!!editDef} onClose={() => setEditDef(null)} title="Modifier le champ de métadonnée">
                <form onSubmit={handleUpdate} className="space-y-4">
                    <Input
                        id="edit-meta-name"
                        label="Libellé du champ"
                        required
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                        error={form.errors.name}
                    />

                    <Input
                        id="edit-meta-key"
                        label="Clé unique"
                        required
                        value={form.data.key}
                        onChange={(e) => form.setData('key', e.target.value)}
                        error={form.errors.key}
                    />

                    <Select
                        id="edit-meta-type"
                        label="Type de données"
                        required
                        value={form.data.type}
                        onChange={(e) => form.setData('type', e.target.value)}
                        options={allowedTypes.map(t => ({ value: t, label: t.toUpperCase() }))}
                        error={form.errors.type}
                    />

                    <Textarea
                        id="edit-meta-desc"
                        label="Description"
                        rows={2}
                        value={form.data.description}
                        onChange={(e) => form.setData('description', e.target.value)}
                    />

                    <div className="flex items-center gap-6 pt-2">
                        <label className="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={form.data.is_required}
                                onChange={(e) => form.setData('is_required', e.target.checked)}
                                className="rounded text-indigo-600"
                            />
                            Champ obligatoire
                        </label>
                        <label className="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                checked={form.data.is_active}
                                onChange={(e) => form.setData('is_active', e.target.checked)}
                                className="rounded text-indigo-600"
                            />
                            Champ actif
                        </label>
                    </div>

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setEditDef(null)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={form.processing}>Enregistrer</Button>
                    </div>
                </form>
            </Modal>

            {/* Delete Dialog */}
            <ConfirmDialog
                isOpen={!!deleteDef}
                onClose={() => setDeleteDef(null)}
                onConfirm={handleDelete}
                title="Supprimer la définition"
                message={`Êtes-vous sûr de vouloir supprimer la définition "${deleteDef?.name}" ? Ses valeurs associées seront également purgées.`}
                confirmLabel="Supprimer"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
