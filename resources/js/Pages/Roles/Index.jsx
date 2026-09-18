import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Badge from '../../Components/Badge';
import Pagination from '../../Components/Pagination';
import EmptyState from '../../Components/EmptyState';
import ConfirmDialog from '../../Components/ConfirmDialog';
import {
    Shield,
    ShieldPlus,
    Search,
    Edit2,
    Trash2,
    Users,
    Key,
    Lock,
    X,
    AlertCircle
} from 'lucide-react';

export default function RolesIndex({ roles, filters = {}, can = {}, auth }) {
    const [search, setSearch] = useState(filters.search || '');
    const [roleToDelete, setRoleToDelete] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        router.get('/roles', search ? { search } : {}, {
            preserveState: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        setSearch('');
        router.get('/roles', {}, { preserveState: true, replace: true });
    };

    const handleDelete = () => {
        if (!roleToDelete) return;
        setIsDeleting(true);
        router.delete(`/roles/${roleToDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setIsDeleting(false);
                setRoleToDelete(null);
            },
        });
    };

    const isSystemRole = (roleName) => ['super-admin', 'admin'].includes(roleName);

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des rôles & permissions" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Rôles & Permissions</h1>
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                {roles?.total || 0}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-1">
                            Définissez les profils d'accès et ajustez finement les droits accordés à chaque groupe de collaborateurs.
                        </p>
                    </div>

                    {can.create && (
                        <Link href="/roles/create">
                            <Button variant="primary" className="shadow-sm">
                                <ShieldPlus className="w-4 h-4" />
                                Nouveau rôle
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Search Bar */}
                <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs">
                    <form onSubmit={handleSearchSubmit} className="flex gap-3">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher un rôle par son nom..."
                                className="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white placeholder-slate-400"
                            />
                        </div>
                        <Button type="submit" variant="secondary" size="md">
                            Rechercher
                        </Button>
                        {search && (
                            <Button type="button" variant="ghost" size="md" onClick={resetFilters}>
                                <X className="w-3.5 h-3.5" />
                                Effacer
                            </Button>
                        )}
                    </form>
                </div>

                {/* Roles Table */}
                <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
                    {roles?.data?.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                        <th className="py-3.5 px-4">Nom du rôle</th>
                                        <th className="py-3.5 px-4">Type</th>
                                        <th className="py-3.5 px-4">Utilisateurs assignés</th>
                                        <th className="py-3.5 px-4">Permissions actives</th>
                                        <th className="py-3.5 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-xs text-slate-700">
                                    {roles.data.map((r) => {
                                        const system = isSystemRole(r.name);
                                        const hasUsers = (r.users_count || 0) > 0;

                                        return (
                                            <tr key={r.id} className="hover:bg-slate-50/80 transition">
                                                {/* Role name */}
                                                <td className="py-3.5 px-4 font-semibold text-slate-900">
                                                    <div className="flex items-center gap-2.5">
                                                        <div className={`p-2 rounded-lg shrink-0 ${system ? 'bg-purple-100 text-purple-700' : 'bg-indigo-50 text-indigo-600'}`}>
                                                            <Shield className="w-4 h-4" />
                                                        </div>
                                                        <span>{r.name}</span>
                                                    </div>
                                                </td>

                                                {/* Type */}
                                                <td className="py-3.5 px-4">
                                                    {system ? (
                                                        <Badge variant="purple" size="sm">
                                                            <Lock className="w-3 h-3" />
                                                            Système
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="default" size="sm">
                                                            Personnalisé
                                                        </Badge>
                                                    )}
                                                </td>

                                                {/* Users count */}
                                                <td className="py-3.5 px-4">
                                                    <div className="flex items-center gap-1.5 text-slate-600">
                                                        <Users className="w-3.5 h-3.5 text-slate-400" />
                                                        <span className="font-semibold">{r.users_count ?? 0}</span>
                                                        <span className="text-slate-400">utilisateur(s)</span>
                                                    </div>
                                                </td>

                                                {/* Permissions count */}
                                                <td className="py-3.5 px-4">
                                                    <div className="flex items-center gap-1.5 text-slate-600">
                                                        <Key className="w-3.5 h-3.5 text-slate-400" />
                                                        <span className="font-semibold">{r.permissions_count ?? 0}</span>
                                                        <span className="text-slate-400">permission(s)</span>
                                                    </div>
                                                </td>

                                                {/* Actions */}
                                                <td className="py-3.5 px-4 text-right">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        <Link
                                                            href={`/roles/${r.id}/edit`}
                                                            title="Configurer les permissions"
                                                            className="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-slate-100 transition"
                                                        >
                                                            <Edit2 className="w-4 h-4" />
                                                        </Link>

                                                        {!system && (
                                                            <button
                                                                type="button"
                                                                disabled={hasUsers}
                                                                onClick={() => setRoleToDelete(r)}
                                                                title={
                                                                    hasUsers
                                                                        ? "Impossible de supprimer : ce rôle est attribué à des utilisateurs"
                                                                        : "Supprimer ce rôle"
                                                                }
                                                                className={`p-1.5 rounded-lg transition ${
                                                                    hasUsers
                                                                        ? 'text-slate-300 cursor-not-allowed'
                                                                        : 'text-slate-400 hover:text-rose-600 hover:bg-rose-50'
                                                                }`}
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <EmptyState
                            icon={Shield}
                            title="Aucun rôle trouvé"
                            description={
                                search
                                    ? "Aucun rôle ne correspond à votre recherche."
                                    : "Commencez par créer des rôles personnalisés pour votre organisation."
                            }
                            action={
                                search ? (
                                    <Button variant="secondary" size="sm" onClick={resetFilters}>
                                        Effacer la recherche
                                    </Button>
                                ) : can.create ? (
                                    <Link href="/roles/create">
                                        <Button variant="primary" size="sm">
                                            <ShieldPlus className="w-4 h-4" />
                                            Créer un rôle
                                        </Button>
                                    </Link>
                                ) : null
                            }
                        />
                    )}

                    {roles?.links?.length > 3 && (
                        <div className="border-t border-slate-200/80 p-3 bg-slate-50/50">
                            <Pagination pagination={roles} />
                        </div>
                    )}
                </div>
            </div>

            {/* Confirm Delete Dialog */}
            <ConfirmDialog
                isOpen={Boolean(roleToDelete)}
                onClose={() => setRoleToDelete(null)}
                onConfirm={handleDelete}
                title="Supprimer le rôle"
                message={`Êtes-vous sûr de vouloir supprimer définitivement le rôle '${roleToDelete?.name}' ? Cette action est irréversible.`}
                confirmLabel="Supprimer"
                variant="danger"
                loading={isDeleting}
            />
        </AuthenticatedLayout>
    );
}
