import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Breadcrumb from '../../Components/Breadcrumb';
import Badge from '../../Components/Badge';
import {
    ShieldPlus,
    ArrowLeft,
    CheckSquare,
    Square,
    Lock,
    Key,
    Save
} from 'lucide-react';

const ACTION_LABELS = {
    view: 'Consulter / Voir',
    create: 'Créer / Ajouter',
    update: 'Modifier',
    delete: 'Supprimer',
    download: 'Télécharger',
    share: 'Partager',
    archive: 'Archiver',
    restore: 'Restaurer',
    moderate: 'Modérer',
    execute: 'Exécuter',
    approve: 'Approuver',
    reject: 'Rejeter',
    cancel: 'Annuler',
};

export default function RolesCreate({ permissionsGrouped = [] }) {
    const form = useForm({
        name: '',
        permissions: [],
    });

    const allPermissionNames = permissionsGrouped.flatMap((g) =>
        g.permissions.map((p) => p.name)
    );

    const isAllSelected =
        allPermissionNames.length > 0 &&
        allPermissionNames.every((p) => form.data.permissions.includes(p));

    const toggleAll = () => {
        if (isAllSelected) {
            form.setData('permissions', []);
        } else {
            form.setData('permissions', [...allPermissionNames]);
        }
    };

    const togglePermission = (permName) => {
        const current = form.data.permissions || [];
        if (current.includes(permName)) {
            form.setData(
                'permissions',
                current.filter((p) => p !== permName)
            );
        } else {
            form.setData('permissions', [...current, permName]);
        }
    };

    const toggleDomain = (domainPermissions) => {
        const current = form.data.permissions || [];
        const domainNames = domainPermissions.map((p) => p.name);
        const allInDomainSelected = domainNames.every((p) => current.includes(p));

        if (allInDomainSelected) {
            form.setData(
                'permissions',
                current.filter((p) => !domainNames.includes(p))
            );
        } else {
            const combined = Array.from(new Set([...current, ...domainNames]));
            form.setData('permissions', combined);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        form.post('/roles');
    };

    return (
        <AuthenticatedLayout>
            <Head title="Créer un rôle" />

            <div className="max-w-5xl mx-auto space-y-6">
                {/* Breadcrumb */}
                <div className="flex items-center justify-between">
                    <Breadcrumb
                        items={[
                            { label: 'Rôles & Permissions', href: '/roles' },
                            { label: 'Nouveau rôle' },
                        ]}
                    />
                    <Link href="/roles">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="w-4 h-4" />
                            Retour aux rôles
                        </Button>
                    </Link>
                </div>

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Nouveau rôle</h1>
                    <p className="text-xs text-slate-500 mt-1">
                        Définissez un profil d'autorisations en sélectionnant les privilèges accordés.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Role Identity */}
                    <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <ShieldPlus className="w-4 h-4 text-indigo-600" />
                            <span>Paramètres du rôle</span>
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-slate-700 mb-1">
                                Nom du rôle <span className="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                required
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Ex: comptable, archiviste-rh, auditeur-externe..."
                                className="w-full max-w-md px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                            />
                            {form.errors.name && (
                                <p className="text-[11px] text-rose-500 mt-1">{form.errors.name}</p>
                            )}
                            <p className="text-[11px] text-slate-400 mt-1">
                                Utilisez un identifiant explicite (en minuscules avec tirets de préférence).
                            </p>
                        </div>
                    </div>

                    {/* Permissions Grid */}
                    <div className="space-y-4">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div className="flex items-center gap-2">
                                <Key className="w-4 h-4 text-indigo-600" />
                                <h2 className="text-sm font-bold text-slate-900">
                                    Permissions accordées ({form.data.permissions.length} / {allPermissionNames.length})
                                </h2>
                            </div>

                            <Button
                                type="button"
                                variant="secondary"
                                size="sm"
                                onClick={toggleAll}
                            >
                                {isAllSelected ? (
                                    <>
                                        <Square className="w-3.5 h-3.5" />
                                        Tout désélectionner
                                    </>
                                ) : (
                                    <>
                                        <CheckSquare className="w-3.5 h-3.5" />
                                        Tout sélectionner
                                    </>
                                )}
                            </Button>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {permissionsGrouped.map((group) => {
                                const domainNames = group.permissions.map((p) => p.name);
                                const selectedCount = domainNames.filter((p) =>
                                    form.data.permissions.includes(p)
                                ).length;
                                const allSelected =
                                    domainNames.length > 0 && selectedCount === domainNames.length;

                                return (
                                    <div
                                        key={group.domain}
                                        className="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col"
                                    >
                                        {/* Card Header */}
                                        <div className="flex items-center justify-between px-4 py-3 bg-slate-50/80 border-b border-slate-200/70">
                                            <div className="flex items-center gap-2">
                                                <span className="font-semibold text-xs text-slate-900">
                                                    {group.label}
                                                </span>
                                                <Badge
                                                    variant={selectedCount > 0 ? 'primary' : 'default'}
                                                    size="sm"
                                                >
                                                    {selectedCount}/{group.permissions.length}
                                                </Badge>
                                            </div>

                                            <button
                                                type="button"
                                                onClick={() => toggleDomain(group.permissions)}
                                                className="text-[11px] font-medium text-indigo-600 hover:text-indigo-800 transition"
                                            >
                                                {allSelected ? 'Décocher tout' : 'Cocher tout'}
                                            </button>
                                        </div>

                                        {/* Permissions list */}
                                        <div className="p-3 space-y-1.5 divide-y divide-slate-50 flex-1">
                                            {group.permissions.map((p) => {
                                                const isChecked = form.data.permissions.includes(p.name);
                                                return (
                                                    <label
                                                        key={p.id}
                                                        className="flex items-center gap-2.5 pt-1.5 first:pt-0 cursor-pointer text-xs text-slate-700 hover:text-slate-900 select-none group"
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            checked={isChecked}
                                                            onChange={() => togglePermission(p.name)}
                                                            className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
                                                        />
                                                        <div className="min-w-0 flex-1">
                                                            <div className="font-medium">
                                                                {ACTION_LABELS[p.action] || p.action}
                                                            </div>
                                                            <span className="text-[10px] text-slate-400 font-mono">
                                                                {p.name}
                                                            </span>
                                                        </div>
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-4">
                        <Link href="/roles">
                            <Button variant="secondary" type="button">
                                Annuler
                            </Button>
                        </Link>
                        <Button variant="primary" type="submit" loading={form.processing}>
                            <Save className="w-4 h-4" />
                            Enregistrer le rôle
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
