import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Badge from '../../Components/Badge';
import Breadcrumb from '../../Components/Breadcrumb';
import ConfirmDialog from '../../Components/ConfirmDialog';
import {
    Shield,
    ArrowLeft,
    Edit2,
    Trash2,
    Users,
    Check,
    X,
    Lock,
    Unlock,
    Search,
    KeyRound
} from 'lucide-react';

export default function RolesShow({ role, users = [], permissionsGrouped = [], isSystemRole = false, can = {} }) {
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');

    const handleDelete = () => {
        setIsDeleting(true);
        router.delete(`/roles/${role.id}`, {
            onFinish: () => {
                setIsDeleting(false);
                setConfirmDelete(false);
            },
        });
    };

    const getInitials = (u) => {
        const first = u.first_name?.[0] || '';
        const last = u.last_name?.[0] || '';
        return (first + last).toUpperCase() || u.email?.[0]?.toUpperCase() || '?';
    };

    const filteredGroups = permissionsGrouped.map((group) => {
        if (!searchTerm) return group;
        const matching = group.permissions.filter(
            (p) =>
                p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                (p.action && p.action.toLowerCase().includes(searchTerm.toLowerCase()))
        );
        return {
            ...group,
            permissions: matching,
        };
    }).filter((g) => g.permissions.length > 0);

    return (
        <AuthenticatedLayout>
            <Head title={`Rôle : ${role.name}`} />

            <div className="max-w-6xl mx-auto space-y-6">
                {/* Breadcrumb & Navigation */}
                <div className="flex items-center justify-between">
                    <Breadcrumb
                        items={[
                            { label: 'Rôles & Permissions', href: '/roles' },
                            { label: role.name },
                        ]}
                    />
                    <Link href="/roles">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="w-4 h-4 mr-1.5" />
                            Retour aux rôles
                        </Button>
                    </Link>
                </div>

                {/* Header Banner */}
                <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 md:p-8">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        <div className="flex items-center gap-5">
                            <div className={`w-16 h-16 rounded-2xl flex items-center justify-center font-bold text-xl shadow-md shrink-0 ${
                                isSystemRole ? 'bg-purple-100 text-purple-700' : 'bg-indigo-50 text-indigo-600'
                            }`}>
                                <Shield className="w-8 h-8" />
                            </div>
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
                                        {role.name}
                                    </h1>
                                    {isSystemRole ? (
                                        <Badge variant="purple" size="sm">
                                            Rôle Système
                                        </Badge>
                                    ) : (
                                        <Badge variant="primary" size="sm">
                                            Rôle Organisationnel
                                        </Badge>
                                    )}
                                </div>
                                <p className="text-xs text-slate-500">
                                    {role.users_count} utilisateur(s) assigné(s) • {role.permissions_count} permission(s) active(s)
                                </p>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center gap-2">
                            {can.update && (
                                <Link href={`/roles/${role.id}/edit`}>
                                    <Button variant="primary" size="md">
                                        <Edit2 className="w-4 h-4 mr-1.5" />
                                        Modifier les permissions
                                    </Button>
                                </Link>
                            )}

                            {can.delete && !isSystemRole && role.users_count === 0 && (
                                <Button
                                    variant="danger"
                                    size="md"
                                    onClick={() => setConfirmDelete(true)}
                                >
                                    <Trash2 className="w-4 h-4 mr-1.5" />
                                    Supprimer
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* 2-Column Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left Column: Assigned Users & Scope info */}
                    <div className="lg:col-span-1 space-y-6">
                        {/* Summary Card */}
                        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Informations du rôle
                            </h3>
                            <div className="space-y-2.5 text-xs text-slate-700">
                                <div className="flex justify-between py-1 border-b border-slate-100">
                                    <span className="text-slate-400">Identifiant</span>
                                    <span className="font-semibold text-slate-900">#{role.id}</span>
                                </div>
                                <div className="flex justify-between py-1 border-b border-slate-100">
                                    <span className="text-slate-400">Type</span>
                                    <span className="font-medium">{isSystemRole ? 'Système (Standard)' : 'Personnalisé'}</span>
                                </div>
                                <div className="flex justify-between py-1 border-b border-slate-100">
                                    <span className="text-slate-400">Guard</span>
                                    <span className="font-mono text-slate-600">web</span>
                                </div>
                                <div className="flex justify-between py-1">
                                    <span className="text-slate-400">Utilisateurs actifs</span>
                                    <span className="font-semibold text-indigo-600">{role.users_count}</span>
                                </div>
                            </div>
                        </div>

                        {/* Assigned Users list */}
                        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                    <Users className="w-3.5 h-3.5 text-indigo-600" />
                                    Utilisateurs avec ce rôle
                                </h3>
                                <span className="text-xs font-semibold text-slate-500">
                                    {users.length}
                                </span>
                            </div>

                            {users.length > 0 ? (
                                <div className="space-y-2 max-h-96 overflow-y-auto pr-1">
                                    {users.map((u) => (
                                        <Link
                                            key={u.id}
                                            href={`/users/${u.id}`}
                                            className="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 transition border border-transparent hover:border-slate-100"
                                        >
                                            <div className="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs shrink-0">
                                                {getInitials(u)}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <span className="text-xs font-semibold text-slate-900 block truncate">
                                                    {u.first_name} {u.last_name}
                                                </span>
                                                <span className="text-[11px] text-slate-400 block truncate">
                                                    {u.email}
                                                </span>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-6 text-xs text-slate-400 italic">
                                    Aucun utilisateur n'est actuellement assigné à ce rôle.
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column: Grouped Permissions */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
                                <div>
                                    <h2 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                        <KeyRound className="w-4 h-4 text-indigo-600" />
                                        Permissions associées au rôle
                                    </h2>
                                    <p className="text-xs text-slate-500 mt-0.5">
                                        Droits fonctionnels regroupés par domaine de gestion.
                                    </p>
                                </div>

                                {/* Filter input */}
                                <div className="relative w-full sm:w-60">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                    <input
                                        type="text"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        placeholder="Filtrer les permissions..."
                                        className="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                                    />
                                </div>
                            </div>

                            {/* Grouped list */}
                            <div className="space-y-4">
                                {filteredGroups.map((group) => (
                                    <div
                                        key={group.domain}
                                        className="p-4 rounded-xl bg-slate-50/70 border border-slate-200/80 space-y-3"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="text-xs font-bold uppercase tracking-wider text-slate-900">
                                                {group.label}
                                            </span>
                                            <span className="text-[11px] font-semibold text-slate-500">
                                                {group.active_count || 0} / {group.permissions.length} active(s)
                                            </span>
                                        </div>

                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                            {group.permissions.map((perm) => (
                                                <div
                                                    key={perm.id}
                                                    className={`flex items-center justify-between p-2 rounded-lg border text-xs transition ${
                                                        perm.is_active
                                                            ? 'bg-emerald-50/60 border-emerald-200 text-emerald-950 font-medium'
                                                            : 'bg-white border-slate-200 text-slate-400'
                                                    }`}
                                                >
                                                    <span className="truncate">{perm.name}</span>
                                                    {perm.is_active ? (
                                                        <span className="flex items-center gap-1 text-[10px] text-emerald-700 bg-emerald-100/80 px-2 py-0.5 rounded-full font-semibold shrink-0">
                                                            <Check className="w-3 h-3" />
                                                            Accordé
                                                        </span>
                                                    ) : (
                                                        <span className="text-[10px] text-slate-400 shrink-0">
                                                            Non accordé
                                                        </span>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Confirm Delete Dialog */}
            <ConfirmDialog
                isOpen={confirmDelete}
                onClose={() => setConfirmDelete(false)}
                onConfirm={handleDelete}
                title="Supprimer le rôle"
                message={`Êtes-vous sûr de vouloir supprimer définitivement le rôle '${role.name}' ? Cette action est irréversible.`}
                confirmLabel="Supprimer"
                variant="danger"
                loading={isDeleting}
            />
        </AuthenticatedLayout>
    );
}
