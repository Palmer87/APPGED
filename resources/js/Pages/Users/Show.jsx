import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Badge from '../../Components/Badge';
import Breadcrumb from '../../Components/Breadcrumb';
import ConfirmDialog from '../../Components/ConfirmDialog';
import {
    ArrowLeft,
    Edit2,
    Trash2,
    UserCheck,
    UserX,
    Shield,
    Users,
    Mail,
    Phone,
    Briefcase,
    Calendar,
    History,
    Building2,
    Clock,
    Activity
} from 'lucide-react';

export default function UsersShow({ user, auditLogs = [], can = {}, auth }) {
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [confirmToggle, setConfirmToggle] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isToggling, setIsToggling] = useState(false);

    const isCurrentUser = auth?.user?.id === user.id;
    const primaryRole = user.roles?.[0]?.name;

    const getInitials = (u) => {
        const first = u.first_name?.[0] || '';
        const last = u.last_name?.[0] || '';
        return (first + last).toUpperCase() || u.email?.[0]?.toUpperCase() || '?';
    };

    const handleToggleStatus = () => {
        setIsToggling(true);
        router.post(`/users/${user.id}/toggle-status`, {}, {
            preserveScroll: true,
            onFinish: () => {
                setIsToggling(false);
                setConfirmToggle(false);
            },
        });
    };

    const handleDelete = () => {
        setIsDeleting(true);
        router.delete(`/users/${user.id}`, {
            onFinish: () => {
                setIsDeleting(false);
                setConfirmDelete(false);
            },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Utilisateur : ${user.name}`} />

            <div className="max-w-5xl mx-auto space-y-6">
                {/* Breadcrumb & Top Bar */}
                <div className="flex items-center justify-between">
                    <Breadcrumb
                        items={[
                            { label: 'Utilisateurs', href: '/users' },
                            { label: user.name },
                        ]}
                    />
                    <Link href="/users">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="w-4 h-4" />
                            Retour aux utilisateurs
                        </Button>
                    </Link>
                </div>

                {/* Profile Banner Card */}
                <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 md:p-8">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                        <div className="flex items-start sm:items-center gap-5">
                            <div className="w-16 h-16 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-violet-500 text-white flex items-center justify-center font-bold text-xl shadow-md shrink-0">
                                {getInitials(user)}
                            </div>
                            <div className="space-y-1">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
                                        {user.name}
                                    </h1>
                                    <Badge
                                        variant={user.status === 'active' ? 'success' : 'default'}
                                        size="sm"
                                    >
                                        <span
                                            className={`w-1.5 h-1.5 rounded-full ${
                                                user.status === 'active' ? 'bg-emerald-500' : 'bg-slate-400'
                                            }`}
                                        />
                                        {user.status === 'active' ? 'Compte actif' : 'Compte inactif'}
                                    </Badge>
                                    {isCurrentUser && (
                                        <Badge variant="primary" size="sm">
                                            Votre compte
                                        </Badge>
                                    )}
                                </div>
                                <div className="flex flex-wrap items-center gap-4 text-xs text-slate-500 pt-1">
                                    <div className="flex items-center gap-1.5">
                                        <Mail className="w-3.5 h-3.5 text-slate-400" />
                                        <span>{user.email}</span>
                                    </div>
                                    {user.phone && (
                                        <div className="flex items-center gap-1.5">
                                            <Phone className="w-3.5 h-3.5 text-slate-400" />
                                            <span>{user.phone}</span>
                                        </div>
                                    )}
                                    {user.job_title && (
                                        <div className="flex items-center gap-1.5">
                                            <Briefcase className="w-3.5 h-3.5 text-slate-400" />
                                            <span>{user.job_title}</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-wrap items-center gap-2">
                            {can.update && (
                                <Link href={`/users/${user.id}/edit`}>
                                    <Button variant="secondary" size="md">
                                        <Edit2 className="w-4 h-4" />
                                        Modifier
                                    </Button>
                                </Link>
                            )}

                            {can.update && !isCurrentUser && (
                                <Button
                                    variant={user.status === 'active' ? 'secondary' : 'primary'}
                                    size="md"
                                    onClick={() => setConfirmToggle(true)}
                                >
                                    {user.status === 'active' ? (
                                        <>
                                            <UserX className="w-4 h-4 text-rose-500" />
                                            Désactiver
                                        </>
                                    ) : (
                                        <>
                                            <UserCheck className="w-4 h-4" />
                                            Activer
                                        </>
                                    )}
                                </Button>
                            )}

                            {can.delete && !isCurrentUser && (
                                <Button
                                    variant="danger"
                                    size="md"
                                    onClick={() => setConfirmDelete(true)}
                                >
                                    <Trash2 className="w-4 h-4" />
                                    Supprimer
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Two-column overview */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left: Info, Roles & Groups */}
                    <div className="lg:col-span-1 space-y-6">
                        {/* Account Details */}
                        <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Détails du compte
                            </h3>
                            <div className="space-y-3 text-xs">
                                <div>
                                    <span className="text-slate-400 block text-[11px]">Date de création</span>
                                    <span className="text-slate-700 font-medium">
                                        {user.created_at
                                            ? new Date(user.created_at).toLocaleDateString('fr-FR', {
                                                  day: '2-digit',
                                                  month: 'long',
                                                  year: 'numeric',
                                                  hour: '2-digit',
                                                  minute: '2-digit',
                                              })
                                            : '—'}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-slate-400 block text-[11px]">Dernière connexion</span>
                                    <span className="text-slate-700 font-medium">
                                        {user.last_login_at
                                            ? new Date(user.last_login_at).toLocaleDateString('fr-FR', {
                                                  day: '2-digit',
                                                  month: 'long',
                                                  year: 'numeric',
                                                  hour: '2-digit',
                                                  minute: '2-digit',
                                              })
                                            : 'Jamais connecté'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Roles */}
                        <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs space-y-3">
                            <div className="flex items-center justify-between">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                    <Shield className="w-3.5 h-3.5 text-indigo-600" />
                                    Rôles attribués
                                </h3>
                                <span className="text-xs font-semibold text-slate-500">
                                    {user.roles?.length || 0}
                                </span>
                            </div>

                            <div className="flex flex-wrap gap-1.5">
                                {user.roles?.length > 0 ? (
                                    user.roles.map((r) => (
                                        <Badge
                                            key={r.id}
                                            variant={
                                                r.name === 'super-admin' || r.name === 'admin'
                                                    ? 'purple'
                                                    : r.name === 'manager'
                                                    ? 'primary'
                                                    : 'default'
                                            }
                                            size="md"
                                        >
                                            <Shield className="w-3 h-3" />
                                            {r.name}
                                        </Badge>
                                    ))
                                ) : (
                                    <span className="text-xs text-slate-400 italic">Aucun rôle attribué</span>
                                )}
                            </div>
                        </div>

                        {/* Groups */}
                        <div className="bg-white p-5 rounded-xl border border-slate-200/80 shadow-xs space-y-3">
                            <div className="flex items-center justify-between">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                    <Users className="w-3.5 h-3.5 text-indigo-600" />
                                    Groupes d'appartenance
                                </h3>
                                <span className="text-xs font-semibold text-slate-500">
                                    {user.groups?.length || 0}
                                </span>
                            </div>

                            <div className="space-y-1.5">
                                {user.groups?.length > 0 ? (
                                    user.groups.map((g) => (
                                        <div
                                            key={g.id}
                                            className="flex items-center justify-between p-2 rounded-lg bg-slate-50 border border-slate-100 text-xs"
                                        >
                                            <span className="font-medium text-slate-800">{g.name}</span>
                                            <Badge variant="info" size="sm">
                                                Groupe
                                            </Badge>
                                        </div>
                                    ))
                                ) : (
                                    <span className="text-xs text-slate-400 italic">
                                        N'appartient à aucun groupe
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Right: Activity & Audit Trail */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
                            <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                <h3 className="text-sm font-semibold text-slate-900 flex items-center gap-2">
                                    <Activity className="w-4 h-4 text-indigo-600" />
                                    Journal d'activité récent
                                </h3>
                                <span className="text-xs text-slate-400">
                                    {auditLogs?.length || 0} action(s) répertoriée(s)
                                </span>
                            </div>

                            {auditLogs?.length > 0 ? (
                                <div className="space-y-3">
                                    {auditLogs.map((log) => (
                                        <div
                                            key={log.id}
                                            className="flex items-start gap-3 p-3 rounded-xl bg-slate-50/60 border border-slate-100 transition hover:bg-slate-50"
                                        >
                                            <div className="p-2 rounded-lg bg-indigo-50 text-indigo-600 shrink-0 mt-0.5">
                                                <Clock className="w-3.5 h-3.5" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center justify-between gap-2">
                                                    <span className="text-xs font-semibold text-slate-900">
                                                        {log.action}
                                                    </span>
                                                    <span className="text-[10px] text-slate-400 shrink-0">
                                                        {log.created_at
                                                            ? new Date(log.created_at).toLocaleDateString('fr-FR', {
                                                                  day: '2-digit',
                                                                  month: 'short',
                                                                  hour: '2-digit',
                                                                  minute: '2-digit',
                                                              })
                                                            : ''}
                                                    </span>
                                                </div>
                                                <p className="text-xs text-slate-600 mt-0.5 break-words">
                                                    {log.description || 'Aucune description'}
                                                </p>
                                                {log.ip_address && (
                                                    <span className="inline-block mt-1 text-[10px] text-slate-400 font-mono">
                                                        IP: {log.ip_address}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-xs text-slate-400">
                                    <History className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                    Aucune activité enregistrée récemment pour cet utilisateur.
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Confirm Status Toggle */}
            <ConfirmDialog
                isOpen={confirmToggle}
                onClose={() => setConfirmToggle(false)}
                onConfirm={handleToggleStatus}
                title={user.status === 'active' ? "Désactiver l'utilisateur" : "Activer l'utilisateur"}
                message={`Êtes-vous sûr de vouloir ${
                    user.status === 'active' ? 'désactiver' : 'activer'
                } l'accès de ${user.name} ?`}
                confirmLabel={user.status === 'active' ? 'Désactiver' : 'Activer'}
                variant={user.status === 'active' ? 'danger' : 'primary'}
                loading={isToggling}
            />

            {/* Confirm Delete */}
            <ConfirmDialog
                isOpen={confirmDelete}
                onClose={() => setConfirmDelete(false)}
                onConfirm={handleDelete}
                title="Supprimer l'utilisateur"
                message={`Êtes-vous sûr de vouloir supprimer définitivement ${user.name} ? Cette action supprimera son compte et ses attributions.`}
                confirmLabel="Supprimer"
                variant="danger"
                loading={isDeleting}
            />
        </AuthenticatedLayout>
    );
}
