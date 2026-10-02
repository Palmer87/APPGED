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
    Activity,
    Network,
    KeyRound,
    CheckCircle2,
    FileText,
    Folder,
    FolderOpen,
    Check,
    Sparkles,
    Lock,
    Unlock
} from 'lucide-react';

export default function UsersShow({ user, effectiveRights = null, auditLogs = [], can = {}, auth }) {
    const [confirmDelete, setConfirmDelete] = useState(false);
    const [confirmToggle, setConfirmToggle] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isToggling, setIsToggling] = useState(false);
    const [activeTab, setActiveTab] = useState('rights'); // 'rights' | 'activity'

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

    const rights = effectiveRights || {
        is_super_admin: user.roles?.some((r) => r.name === 'super-admin'),
        is_admin: user.roles?.some((r) => r.name === 'admin'),
        roles: user.roles || [],
        grouped_permissions: [],
        all_permissions: [],
        service_access: {
            direction: user.primary_service?.direction || null,
            primary_service: user.primary_service || null,
            associated_services: user.services || [],
        },
        scopes: user.access_scopes || [],
        groups: user.groups || [],
        direct_acls: { folders: [], documents: [], total: 0 },
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Utilisateur : ${user.name}`} />

            <div className="max-w-6xl mx-auto space-y-6">
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
                            <ArrowLeft className="w-4 h-4 mr-1.5" />
                            Retour à la liste
                        </Button>
                    </Link>
                </div>

                {/* Profile Banner Card */}
                <div className="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 md:p-8">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div className="flex items-start md:items-center gap-5">
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
                                    {primaryRole && (
                                        <Badge
                                            variant={
                                                primaryRole === 'super-admin' || primaryRole === 'admin'
                                                    ? 'purple'
                                                    : 'primary'
                                            }
                                            size="sm"
                                        >
                                            <Shield className="w-3 h-3" />
                                            {primaryRole}
                                        </Badge>
                                    )}
                                    {isCurrentUser && (
                                        <Badge variant="info" size="sm">
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
                                    {user.organization && (
                                        <div className="flex items-center gap-1.5 font-medium text-slate-600">
                                            <Building2 className="w-3.5 h-3.5 text-slate-400" />
                                            <span>{user.organization.name}</span>
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
                                        <Edit2 className="w-4 h-4 mr-1.5" />
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
                                            <UserX className="w-4 h-4 text-rose-500 mr-1.5" />
                                            Désactiver
                                        </>
                                    ) : (
                                        <>
                                            <UserCheck className="w-4 h-4 mr-1.5" />
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
                                    <Trash2 className="w-4 h-4 mr-1.5" />
                                    Supprimer
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Main 2-Column Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left: Organization structure & perimeter context */}
                    <div className="lg:col-span-1 space-y-6">
                        {/* Structure Organisationnelle */}
                        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                                <Building2 className="w-3.5 h-3.5 text-indigo-600" />
                                Structure Organisationnelle
                            </h3>

                            <div className="space-y-3.5 text-xs">
                                <div>
                                    <span className="text-slate-400 block text-[11px]">Direction rattachée</span>
                                    <span className="text-slate-900 font-semibold text-sm">
                                        {user.primary_service?.direction?.name || (
                                            <span className="text-slate-400 font-normal italic">Non rattaché</span>
                                        )}
                                    </span>
                                </div>

                                <div>
                                    <span className="text-slate-400 block text-[11px]">Service principal</span>
                                    {user.primary_service ? (
                                        <div className="flex items-center gap-2 mt-1">
                                            <span className="font-semibold text-slate-800">
                                                {user.primary_service.name}
                                            </span>
                                            <Badge variant="primary" size="sm">
                                                Principal
                                            </Badge>
                                        </div>
                                    ) : (
                                        <span className="text-slate-400 italic">Aucun service principal</span>
                                    )}
                                </div>

                                <div>
                                    <span className="text-slate-400 block text-[11px] mb-1.5">
                                        Services associés ({user.services?.length || 0})
                                    </span>
                                    {user.services?.length > 0 ? (
                                        <div className="flex flex-wrap gap-1.5">
                                            {user.services.map((srv) => (
                                                <Badge key={srv.id} variant="default" size="sm">
                                                    <Network className="w-3 h-3 text-slate-500 mr-1" />
                                                    {srv.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    ) : (
                                        <span className="text-slate-400 italic">Aucun service associé</span>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Périmètres d'accès (Access Scopes) */}
                        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                    <KeyRound className="w-3.5 h-3.5 text-purple-600" />
                                    Périmètres d'accès (Scopes)
                                </h3>
                                <span className="text-xs font-semibold text-slate-500">
                                    {rights.scopes?.length || 0}
                                </span>
                            </div>

                            {rights.scopes?.length > 0 ? (
                                <div className="space-y-2">
                                    {rights.scopes.map((s) => (
                                        <div
                                            key={s.id}
                                            className="p-3 rounded-xl bg-purple-50/50 border border-purple-100 flex items-start justify-between gap-2"
                                        >
                                            <div className="space-y-0.5">
                                                <div className="flex items-center gap-1.5">
                                                    <Badge variant="purple" size="sm">
                                                        {s.scope_label || s.scope_type}
                                                    </Badge>
                                                    <span className="text-xs font-semibold text-slate-800">
                                                        {s.target_name || 'Global'}
                                                    </span>
                                                </div>
                                                <span className="text-[10px] text-slate-400 block">
                                                    Octroyé {s.created_at ? new Date(s.created_at).toLocaleDateString('fr-FR') : ''}
                                                </span>
                                            </div>
                                            <Badge variant={s.is_active ? 'success' : 'default'} size="sm">
                                                {s.is_active ? 'Actif' : 'Inactif'}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-4 text-xs text-slate-400 italic">
                                    Aucun périmètre d'accès explicite configuré.
                                </div>
                            )}
                        </div>

                        {/* Groupes d'appartenance */}
                        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
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

                        {/* Compte & Méta */}
                        <div className="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3 text-xs">
                            <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Horodatages & Connexions
                            </h3>
                            <div className="space-y-2 text-slate-600">
                                <div>
                                    <span className="text-slate-400 block text-[11px]">Compte créé le</span>
                                    <span className="font-medium text-slate-700">
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
                                    <span className="font-medium text-slate-700">
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
                    </div>

                    {/* Right: Droits Effectifs & Journal d'Audit */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Tabs Bar */}
                        <div className="flex border-b border-slate-200 bg-white px-4 rounded-t-2xl pt-2">
                            <button
                                onClick={() => setActiveTab('rights')}
                                className={`pb-3 px-4 text-xs font-semibold border-b-2 transition flex items-center gap-2 ${
                                    activeTab === 'rights'
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                <Sparkles className="w-4 h-4" />
                                Droits effectifs & Permissions
                            </button>
                            <button
                                onClick={() => setActiveTab('activity')}
                                className={`pb-3 px-4 text-xs font-semibold border-b-2 transition flex items-center gap-2 ${
                                    activeTab === 'activity'
                                        ? 'border-indigo-600 text-indigo-600'
                                        : 'border-transparent text-slate-500 hover:text-slate-800'
                                }`}
                            >
                                <Activity className="w-4 h-4" />
                                Journal d'activité ({auditLogs?.length || 0})
                            </button>
                        </div>

                        {activeTab === 'rights' && (
                            <div className="space-y-6">
                                {/* Super-Admin Alert if applicable */}
                                {rights.is_super_admin && (
                                    <div className="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3">
                                        <Shield className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                                        <div className="text-xs space-y-1">
                                            <span className="font-bold text-sm block">Statut Super-Administrateur</span>
                                            <p className="text-amber-800">
                                                Cet utilisateur dispose du statut global <code>super-admin</code>.
                                                Il bénéficie d'un accès illimité à l'intégralité des organisations,
                                                documents, workflows et paramètres de la plateforme via la règle maître Gate.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* Admin Notice if applicable */}
                                {rights.is_admin && !rights.is_super_admin && (
                                    <div className="p-4 rounded-2xl bg-purple-50 border border-purple-200 text-purple-900 flex items-start gap-3">
                                        <Shield className="w-5 h-5 text-purple-600 shrink-0 mt-0.5" />
                                        <div className="text-xs space-y-1">
                                            <span className="font-bold text-sm block">Administrateur d'organisation</span>
                                            <p className="text-purple-800">
                                                En tant qu'administrateur de son organisation, cet utilisateur dispose d'un
                                                accès complet à tous les documents, directions, services et utilisateurs de l'organisation.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* Résumé de calcul des droits effectifs */}
                                <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
                                    <div className="border-b border-slate-100 pb-3 flex items-center justify-between">
                                        <div>
                                            <h2 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                                <Shield className="w-4 h-4 text-indigo-600" />
                                                Permissions effectives par domaine
                                            </h2>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                Permissions accordées via les rôles Spatie rattachés à l'organisation.
                                            </p>
                                        </div>
                                        <Badge variant="purple" size="md">
                                            {rights.permissions_count} permission(s)
                                        </Badge>
                                    </div>

                                    {/* Grouped permissions */}
                                    {rights.grouped_permissions?.length > 0 ? (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {rights.grouped_permissions.map((group) => (
                                                <div
                                                    key={group.domain}
                                                    className="p-3.5 rounded-xl bg-slate-50/75 border border-slate-200/70 space-y-2"
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                                            {group.label}
                                                        </span>
                                                        <span className="text-[10px] font-semibold text-slate-400">
                                                            {group.permissions.length} droit(s)
                                                        </span>
                                                    </div>
                                                    <div className="flex flex-wrap gap-1">
                                                        {group.permissions.map((perm) => (
                                                            <span
                                                                key={perm}
                                                                className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-medium bg-white text-slate-700 border border-slate-200 shadow-2xs"
                                                            >
                                                                <Check className="w-2.5 h-2.5 text-emerald-600" />
                                                                {perm.split('.')[1] || perm}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <div className="text-center py-6 text-xs text-slate-400 italic">
                                            Aucune permission spécifique accordée directement ou via un rôle.
                                        </div>
                                    )}
                                </div>

                                {/* Périmètre & Autorisations d'accès aux ressources */}
                                <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
                                    <div className="border-b border-slate-100 pb-3">
                                        <h2 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                            <Network className="w-4 h-4 text-indigo-600" />
                                            Périmètre d'application & Accès aux documents
                                        </h2>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            Comment le système résout où cet utilisateur peut lire et agir sur les documents.
                                        </p>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                                        {/* Par Structure */}
                                        <div className="p-3.5 rounded-xl bg-indigo-50/40 border border-indigo-100/80 space-y-1.5">
                                            <span className="font-semibold text-indigo-950 flex items-center gap-1.5">
                                                <Building2 className="w-3.5 h-3.5 text-indigo-600" />
                                                Via les Services
                                            </span>
                                            <p className="text-slate-600 text-[11px] leading-relaxed">
                                                Accès aux documents rattachés à son Service principal{' '}
                                                <span className="font-semibold text-slate-900">
                                                    ({user.primary_service?.name || 'Aucun'})
                                                </span>{' '}
                                                et à ses {user.services?.length || 0} service(s) associé(s).
                                            </p>
                                        </div>

                                        {/* Par Périmètres */}
                                        <div className="p-3.5 rounded-xl bg-purple-50/40 border border-purple-100/80 space-y-1.5">
                                            <span className="font-semibold text-purple-950 flex items-center gap-1.5">
                                                <KeyRound className="w-3.5 h-3.5 text-purple-600" />
                                                Via Périmètres (Scopes)
                                            </span>
                                            <p className="text-slate-600 text-[11px] leading-relaxed">
                                                {rights.scopes?.length || 0} règle(s) de périmètre active(s).
                                                Étend ou restreint la visibilité documentaire selon la politique configurée.
                                            </p>
                                        </div>

                                        {/* Par ACL Directes */}
                                        <div className="p-3.5 rounded-xl bg-emerald-50/40 border border-emerald-100/80 space-y-1.5">
                                            <span className="font-semibold text-emerald-950 flex items-center gap-1.5">
                                                <FileText className="w-3.5 h-3.5 text-emerald-600" />
                                                ACLs Directes & Partages
                                            </span>
                                            <p className="text-slate-600 text-[11px] leading-relaxed">
                                                {rights.direct_acls?.total || 0} droit(s) nominatif(s) direct(s)
                                                attribué(s) spécifiquement sur des dossiers ou documents individuels.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {activeTab === 'activity' && (
                            <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
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
                        )}
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
