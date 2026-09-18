import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Breadcrumb from '../../Components/Breadcrumb';
import {
    Edit3,
    ArrowLeft,
    Shield,
    Users,
    KeyRound,
    Briefcase,
    Phone,
    Mail,
    CheckCircle2
} from 'lucide-react';

export default function UsersEdit({ user, roles = [], groups = [], auth }) {
    const isCurrentUser = auth?.user?.id === user.id;

    const form = useForm({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        phone: user.phone || '',
        job_title: user.job_title || '',
        password: '',
        password_confirmation: '',
        role: user.roles?.[0]?.name || roles[0]?.name || 'utilisateur',
        status: user.status || 'active',
        group_ids: user.groups?.map((g) => g.id) || [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        form.put(`/users/${user.id}`);
    };

    const toggleGroup = (groupId) => {
        const current = form.data.group_ids || [];
        if (current.includes(groupId)) {
            form.setData('group_ids', current.filter((id) => id !== groupId));
        } else {
            form.setData('group_ids', [...current, groupId]);
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Modifier ${user.name}`} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Breadcrumb & Navigation */}
                <div className="flex items-center justify-between">
                    <Breadcrumb
                        items={[
                            { label: 'Utilisateurs', href: '/users' },
                            { label: user.name, href: `/users/${user.id}` },
                            { label: 'Modifier' },
                        ]}
                    />
                    <Link href={`/users/${user.id}`}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="w-4 h-4" />
                            Retour au profil
                        </Button>
                    </Link>
                </div>

                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
                            Modifier l'utilisateur
                        </h1>
                        <p className="text-xs text-slate-500 mt-1">
                            Mise à jour des coordonnées, habilitations et rôles pour <span className="font-semibold text-slate-700">{user.name}</span>.
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Information personnelle */}
                    <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <Edit3 className="w-4 h-4 text-indigo-600" />
                            <span>Informations personnelles</span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Prénom <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={form.data.first_name}
                                    onChange={(e) => form.setData('first_name', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                />
                                {form.errors.first_name && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.first_name}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Nom <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={form.data.last_name}
                                    onChange={(e) => form.setData('last_name', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                />
                                {form.errors.last_name && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.last_name}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Adresse email <span className="text-rose-500">*</span>
                                </label>
                                <div className="relative">
                                    <Mail className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                    <input
                                        type="email"
                                        required
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        className="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                    />
                                </div>
                                {form.errors.email && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Numéro de téléphone
                                </label>
                                <div className="relative">
                                    <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                    <input
                                        type="tel"
                                        value={form.data.phone}
                                        onChange={(e) => form.setData('phone', e.target.value)}
                                        placeholder="+33 6 12 34 56 78"
                                        className="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                    />
                                </div>
                                {form.errors.phone && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.phone}</p>
                                )}
                            </div>

                            <div className="md:col-span-2">
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Fonction / Poste
                                </label>
                                <div className="relative">
                                    <Briefcase className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                    <input
                                        type="text"
                                        value={form.data.job_title}
                                        onChange={(e) => form.setData('job_title', e.target.value)}
                                        className="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                    />
                                </div>
                                {form.errors.job_title && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.job_title}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Sécurité & Mot de passe */}
                    <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div className="flex items-center gap-2 text-slate-900 font-semibold text-sm">
                                <KeyRound className="w-4 h-4 text-indigo-600" />
                                <span>Sécurité du mot de passe</span>
                            </div>
                            <span className="text-[11px] text-slate-400">Optionnel si inchangé</span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Nouveau mot de passe
                                </label>
                                <input
                                    type="password"
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    placeholder="Laisser vide pour ne pas changer"
                                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                />
                                {form.errors.password && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.password}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Confirmer le mot de passe
                                </label>
                                <input
                                    type="password"
                                    value={form.data.password_confirmation}
                                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                    placeholder="Confirmer si un nouveau mot de passe est saisi"
                                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Droits & Organisation */}
                    <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <Shield className="w-4 h-4 text-indigo-600" />
                            <span>Rôle et Statut</span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Rôle principal <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    required
                                    value={form.data.role}
                                    onChange={(e) => form.setData('role', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                                >
                                    {roles.map((r) => (
                                        <option key={r.id} value={r.name}>
                                            {r.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.role && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.role}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Statut du compte <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    required
                                    disabled={isCurrentUser}
                                    value={form.data.status}
                                    onChange={(e) => form.setData('status', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white disabled:bg-slate-100 disabled:text-slate-400"
                                >
                                    <option value="active">Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                                {isCurrentUser && (
                                    <p className="text-[11px] text-amber-600 mt-1">
                                        Vous ne pouvez pas désactiver votre propre compte.
                                    </p>
                                )}
                                {form.errors.status && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.status}</p>
                                )}
                            </div>
                        </div>

                        {/* Groupes */}
                        {groups.length > 0 && (
                            <div className="pt-3 border-t border-slate-100">
                                <label className="block text-xs font-semibold text-slate-700 mb-2">
                                    Appartenance aux groupes
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    {groups.map((group) => {
                                        const isSelected = form.data.group_ids.includes(group.id);
                                        return (
                                            <button
                                                key={group.id}
                                                type="button"
                                                onClick={() => toggleGroup(group.id)}
                                                className={`flex items-center gap-2 p-2.5 rounded-lg border text-left transition ${
                                                    isSelected
                                                        ? 'bg-indigo-50 border-indigo-300 text-indigo-900 font-medium'
                                                        : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={isSelected}
                                                    onChange={() => {}}
                                                    className="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
                                                />
                                                <span className="text-xs truncate">{group.name}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                                {form.errors.group_ids && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.group_ids}</p>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={`/users/${user.id}`}>
                            <Button variant="secondary" type="button">
                                Annuler
                            </Button>
                        </Link>
                        <Button variant="primary" type="submit" loading={form.processing}>
                            <CheckCircle2 className="w-4 h-4" />
                            Enregistrer les modifications
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
