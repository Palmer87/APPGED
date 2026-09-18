import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Select from '../../Components/Select';
import Badge from '../../Components/Badge';
import Pagination from '../../Components/Pagination';
import EmptyState from '../../Components/EmptyState';
import ConfirmDialog from '../../Components/ConfirmDialog';
import {
    UserPlus,
    Search,
    Filter,
    X,
    Eye,
    Edit2,
    Trash2,
    UserCheck,
    UserX,
    Shield,
    Users,
    Mail,
    Briefcase,
    Calendar
} from 'lucide-react';

export default function UsersIndex({ users, roles = [], groups = [], filters = {}, can = {}, auth }) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [role, setRole] = useState(filters.role || '');
    const [groupId, setGroupId] = useState(filters.group_id || '');
    const [userToDelete, setUserToDelete] = useState(null);
    const [userToToggle, setUserToToggle] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isToggling, setIsToggling] = useState(false);

    const handleFilter = (updatedFilters) => {
        const query = {
            search: search,
            status: status,
            role: role,
            group_id: groupId,
            ...updatedFilters,
        };

        // Clean empty keys
        Object.keys(query).forEach((key) => {
            if (!query[key]) delete query[key];
        });

        router.get('/users', query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        handleFilter({ search });
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('');
        setRole('');
        setGroupId('');
        router.get('/users', {}, { preserveState: true, replace: true });
    };

    const hasActiveFilters = Boolean(search || status || role || groupId);

    const handleToggleStatus = () => {
        if (!userToToggle) return;
        setIsToggling(true);
        router.post(`/users/${userToToggle.id}/toggle-status`, {}, {
            preserveScroll: true,
            onFinish: () => {
                setIsToggling(false);
                setUserToToggle(null);
            },
        });
    };

    const handleDelete = () => {
        if (!userToDelete) return;
        setIsDeleting(true);
        router.delete(`/users/${userToDelete.id}`, {
            preserveScroll: true,
            onFinish: () => {
                setIsDeleting(false);
                setUserToDelete(null);
            },
        });
    };

    const getInitials = (user) => {
        const first = user.first_name?.[0] || '';
        const last = user.last_name?.[0] || '';
        return (first + last).toUpperCase() || user.email?.[0]?.toUpperCase() || '?';
    };

    return (
        <AuthenticatedLayout>
            <Head title="Gestion des utilisateurs" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3">
                            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Utilisateurs</h1>
                            <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                {users?.total || 0}
                            </span>
                        </div>
                        <p className="text-xs text-slate-500 mt-1">
                            Gérez les comptes d'accès, attributions de rôles et appartenances aux groupes.
                        </p>
                    </div>

                    {can.create && (
                        <Link href="/users/create">
                            <Button variant="primary" className="shadow-sm">
                                <UserPlus className="w-4 h-4" />
                                Nouvel utilisateur
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Filter & Search Bar */}
                <div className="bg-white p-4 rounded-xl border border-slate-200/80 shadow-xs space-y-3">
                    <form onSubmit={handleSearchSubmit} className="flex flex-col md:flex-row gap-3">
                        {/* Search */}
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Rechercher par nom, prénom, email, fonction..."
                                className="w-full pl-9 pr-4 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white placeholder-slate-400 transition"
                            />
                        </div>

                        {/* Status Filter */}
                        <div className="w-full md:w-40">
                            <select
                                value={status}
                                onChange={(e) => {
                                    setStatus(e.target.value);
                                    handleFilter({ status: e.target.value });
                                }}
                                className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white text-slate-700"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="active">Actif</option>
                                <option value="inactive">Inactif</option>
                            </select>
                        </div>

                        {/* Role Filter */}
                        <div className="w-full md:w-44">
                            <select
                                value={role}
                                onChange={(e) => {
                                    setRole(e.target.value);
                                    handleFilter({ role: e.target.value });
                                }}
                                className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white text-slate-700"
                            >
                                <option value="">Tous les rôles</option>
                                {roles.map((r) => (
                                    <option key={r.id} value={r.name}>
                                        {r.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Group Filter */}
                        <div className="w-full md:w-44">
                            <select
                                value={groupId}
                                onChange={(e) => {
                                    setGroupId(e.target.value);
                                    handleFilter({ group_id: e.target.value });
                                }}
                                className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white text-slate-700"
                            >
                                <option value="">Tous les groupes</option>
                                {groups.map((g) => (
                                    <option key={g.id} value={g.id}>
                                        {g.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Search action button */}
                        <Button type="submit" variant="secondary" size="md">
                            <Filter className="w-3.5 h-3.5" />
                            Filtrer
                        </Button>

                        {/* Clear Filters */}
                        {hasActiveFilters && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="md"
                                onClick={resetFilters}
                                className="text-slate-500 hover:text-slate-800"
                            >
                                <X className="w-3.5 h-3.5" />
                                Effacer
                            </Button>
                        )}
                    </form>
                </div>

                {/* Users Table */}
                <div className="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
                    {users?.data?.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                        <th className="py-3.5 px-4">Utilisateur</th>
                                        <th className="py-3.5 px-4">Poste / Rôle</th>
                                        <th className="py-3.5 px-4">Statut</th>
                                        <th className="py-3.5 px-4">Groupes</th>
                                        <th className="py-3.5 px-4">Dernière connexion</th>
                                        <th className="py-3.5 px-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-xs text-slate-700">
                                    {users.data.map((u) => {
                                        const isCurrentUser = auth?.user?.id === u.id;
                                        const primaryRole = u.roles?.[0]?.name;

                                        return (
                                            <tr
                                                key={u.id}
                                                className="hover:bg-slate-50/80 transition group"
                                            >
                                                {/* User info */}
                                                <td className="py-3.5 px-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-500 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                                                            {getInitials(u)}
                                                        </div>
                                                        <div className="min-w-0">
                                                            <div className="flex items-center gap-1.5">
                                                                <Link
                                                                    href={`/users/${u.id}`}
                                                                    className="font-semibold text-slate-900 hover:text-indigo-600 truncate transition"
                                                                >
                                                                    {u.name}
                                                                </Link>
                                                                {isCurrentUser && (
                                                                    <span className="px-1.5 py-0.2 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                                                        Vous
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <div className="text-[11px] text-slate-400 flex items-center gap-1 truncate">
                                                                <Mail className="w-3 h-3 shrink-0" />
                                                                <span>{u.email}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Role & Job Title */}
                                                <td className="py-3.5 px-4">
                                                    <div className="space-y-1">
                                                        {primaryRole ? (
                                                            <Badge
                                                                variant={
                                                                    primaryRole === 'super-admin' || primaryRole === 'admin'
                                                                        ? 'purple'
                                                                        : primaryRole === 'manager'
                                                                        ? 'primary'
                                                                        : 'default'
                                                                }
                                                                size="sm"
                                                            >
                                                                <Shield className="w-3 h-3" />
                                                                {primaryRole}
                                                            </Badge>
                                                        ) : (
                                                            <span className="text-slate-400 italic">Aucun rôle</span>
                                                        )}
                                                        {u.job_title && (
                                                            <div className="text-[11px] text-slate-500 flex items-center gap-1">
                                                                <Briefcase className="w-3 h-3 text-slate-400 shrink-0" />
                                                                <span className="truncate">{u.job_title}</span>
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>

                                                {/* Status */}
                                                <td className="py-3.5 px-4">
                                                    <Badge
                                                        variant={u.status === 'active' ? 'success' : 'default'}
                                                        size="sm"
                                                    >
                                                        <span
                                                            className={`w-1.5 h-1.5 rounded-full ${
                                                                u.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'
                                                            }`}
                                                        />
                                                        {u.status === 'active' ? 'Actif' : 'Inactif'}
                                                    </Badge>
                                                </td>

                                                {/* Groups */}
                                                <td className="py-3.5 px-4">
                                                    {u.groups?.length > 0 ? (
                                                        <div className="flex flex-wrap gap-1 max-w-xs">
                                                            {u.groups.slice(0, 2).map((g) => (
                                                                <Badge key={g.id} variant="info" size="sm">
                                                                    {g.name}
                                                                </Badge>
                                                            ))}
                                                            {u.groups.length > 2 && (
                                                                <span className="text-[11px] text-slate-400 self-center">
                                                                    +{u.groups.length - 2}
                                                                </span>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400 italic text-[11px]">—</span>
                                                    )}
                                                </td>

                                                {/* Last login */}
                                                <td className="py-3.5 px-4 text-slate-500 text-[11px]">
                                                    {u.last_login_at ? (
                                                        <div className="flex items-center gap-1">
                                                            <Calendar className="w-3 h-3 text-slate-400" />
                                                            <span>
                                                                {new Date(u.last_login_at).toLocaleDateString('fr-FR', {
                                                                    day: '2-digit',
                                                                    month: 'short',
                                                                    year: 'numeric',
                                                                })}
                                                            </span>
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400 italic">Jamais connecté</span>
                                                    )}
                                                </td>

                                                {/* Actions */}
                                                <td className="py-3.5 px-4 text-right">
                                                    <div className="flex items-center justify-end gap-1.5">
                                                        {/* Show */}
                                                        <Link
                                                            href={`/users/${u.id}`}
                                                            title="Voir les détails"
                                                            className="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-slate-100 transition"
                                                        >
                                                            <Eye className="w-4 h-4" />
                                                        </Link>

                                                        {/* Edit */}
                                                        {can.update && (
                                                            <Link
                                                                href={`/users/${u.id}/edit`}
                                                                title="Modifier"
                                                                className="p-1.5 rounded-lg text-slate-400 hover:text-amber-600 hover:bg-slate-100 transition"
                                                            >
                                                                <Edit2 className="w-4 h-4" />
                                                            </Link>
                                                        )}

                                                        {/* Toggle status */}
                                                        {can.update && !isCurrentUser && (
                                                            <button
                                                                type="button"
                                                                onClick={() => setUserToToggle(u)}
                                                                title={u.status === 'active' ? 'Désactiver le compte' : 'Activer le compte'}
                                                                className={`p-1.5 rounded-lg transition ${
                                                                    u.status === 'active'
                                                                        ? 'text-slate-400 hover:text-rose-600 hover:bg-rose-50'
                                                                        : 'text-slate-400 hover:text-emerald-600 hover:bg-emerald-50'
                                                                }`}
                                                            >
                                                                {u.status === 'active' ? (
                                                                    <UserX className="w-4 h-4" />
                                                                ) : (
                                                                    <UserCheck className="w-4 h-4" />
                                                                )}
                                                            </button>
                                                        )}

                                                        {/* Delete */}
                                                        {can.delete && !isCurrentUser && (
                                                            <button
                                                                type="button"
                                                                onClick={() => setUserToDelete(u)}
                                                                title="Supprimer l'utilisateur"
                                                                className="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
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
                            icon={Users}
                            title="Aucun utilisateur trouvé"
                            description={
                                hasActiveFilters
                                    ? "Aucun utilisateur ne correspond à vos critères de recherche. Essayez d'ajuster les filtres."
                                    : "Commencez par ajouter des utilisateurs à votre organisation."
                            }
                            action={
                                hasActiveFilters ? (
                                    <Button variant="secondary" size="sm" onClick={resetFilters}>
                                        Effacer les filtres
                                    </Button>
                                ) : can.create ? (
                                    <Link href="/users/create">
                                        <Button variant="primary" size="sm">
                                            <UserPlus className="w-4 h-4" />
                                            Ajouter un utilisateur
                                        </Button>
                                    </Link>
                                ) : null
                            }
                        />
                    )}

                    {/* Pagination */}
                    {users?.links?.length > 3 && (
                        <div className="border-t border-slate-200/80 p-3 bg-slate-50/50">
                            <Pagination pagination={users} />
                        </div>
                    )}
                </div>
            </div>

            {/* Confirm Status Toggle */}
            <ConfirmDialog
                isOpen={Boolean(userToToggle)}
                onClose={() => setUserToToggle(null)}
                onConfirm={handleToggleStatus}
                title={userToToggle?.status === 'active' ? "Désactiver l'utilisateur" : "Activer l'utilisateur"}
                message={`Êtes-vous sûr de vouloir ${
                    userToToggle?.status === 'active' ? 'désactiver' : 'activer'
                } l'accès pour ${userToToggle?.name} ?`}
                confirmLabel={userToToggle?.status === 'active' ? 'Désactiver' : 'Activer'}
                variant={userToToggle?.status === 'active' ? 'danger' : 'primary'}
                loading={isToggling}
            />

            {/* Confirm Delete */}
            <ConfirmDialog
                isOpen={Boolean(userToDelete)}
                onClose={() => setUserToDelete(null)}
                onConfirm={handleDelete}
                title="Supprimer l'utilisateur"
                message={`Êtes-vous sûr de vouloir supprimer définitivement ${userToDelete?.name} (${userToDelete?.email}) ? Cette action est irréversible.`}
                confirmLabel="Supprimer"
                variant="danger"
                loading={isDeleting}
            />
        </AuthenticatedLayout>
    );
}
