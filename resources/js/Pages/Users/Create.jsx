import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Card from '../../Components/Card';
import Breadcrumb from '../../Components/Breadcrumb';
import {
    UserPlus,
    ArrowLeft,
    Shield,
    Users,
    KeyRound,
    UserCheck,
    Briefcase,
    Phone,
    Mail
} from 'lucide-react';

export default function UsersCreate({ roles = [], groups = [] }) {
    const form = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        job_title: '',
        password: '',
        password_confirmation: '',
        role: roles[0]?.name || 'utilisateur',
        status: 'active',
        group_ids: [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        form.post('/users');
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
            <Head title="Créer un utilisateur" />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Breadcrumb & Navigation */}
                <div className="flex items-center justify-between">
                    <Breadcrumb
                        items={[
                            { label: 'Utilisateurs', href: '/users' },
                            { label: 'Nouvel utilisateur' },
                        ]}
                    />
                    <Link href="/users">
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="w-4 h-4" />
                            Retour à la liste
                        </Button>
                    </Link>
                </div>

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Nouvel utilisateur</h1>
                    <p className="text-xs text-slate-500 mt-1">
                        Créez un nouvel accès pour un membre de votre organisation avec un rôle et des groupes.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Information personnelle */}
                    <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <UserPlus className="w-4 h-4 text-indigo-600" />
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
                                    placeholder="Ex: Jean"
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
                                    placeholder="Ex: Dupont"
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
                                        placeholder="jean.dupont@entreprise.com"
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
                                        placeholder="Ex: Responsable Comptabilité, Juriste, Archiviste..."
                                        className="w-full pl-9 pr-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                    />
                                </div>
                                {form.errors.job_title && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.job_title}</p>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Sécurité & Authentification */}
                    <div className="bg-white p-6 rounded-xl border border-slate-200/80 shadow-xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <KeyRound className="w-4 h-4 text-indigo-600" />
                            <span>Sécurité du compte</span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Mot de passe <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="password"
                                    required
                                    value={form.data.password}
                                    onChange={(e) => form.setData('password', e.target.value)}
                                    placeholder="Au minimum 8 caractères"
                                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                />
                                {form.errors.password && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.password}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Confirmer le mot de passe <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="password"
                                    required
                                    value={form.data.password_confirmation}
                                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                    placeholder="Répétez le mot de passe"
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
                                <p className="text-[11px] text-slate-400 mt-1">
                                    Détermine les autorisations d'accès globales de l'utilisateur.
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Statut du compte <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    required
                                    value={form.data.status}
                                    onChange={(e) => form.setData('status', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                                >
                                    <option value="active">Actif (Peut se connecter)</option>
                                    <option value="inactive">Inactif (Connexion bloquée)</option>
                                </select>
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
                        <Link href="/users">
                            <Button variant="secondary" type="button">
                                Annuler
                            </Button>
                        </Link>
                        <Button variant="primary" type="submit" loading={form.processing}>
                            <UserPlus className="w-4 h-4" />
                            Créer l'utilisateur
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
