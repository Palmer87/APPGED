import React, { useState } from 'react';
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
    Building2,
    Network
} from 'lucide-react';

export default function UsersEdit({ user, roles = [], groups = [], directions = [], services = [], auth }) {
    const isCurrentUser = auth?.user?.id === user.id;

    const [selectedDirection, setSelectedDirection] = useState(user.direction_id ? String(user.direction_id) : '');

    const form = useForm({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        email: user.email || '',
        phone: user.phone || '',
        job_title: user.job_title || '',
        password: '',
        password_confirmation: '',
        direction_id: user.direction_id || '',
        primary_service_id: user.primary_service_id || '',
        associated_service_ids: user.associated_service_ids || [],
        role: user.role || roles[0]?.name || 'utilisateur',
        status: user.status || 'active',
        group_ids: user.group_ids || [],
    });

    const availableServices = selectedDirection
        ? services.filter((s) => String(s.direction_id) === String(selectedDirection))
        : services;

    const handleDirectionChange = (e) => {
        const dirId = e.target.value;
        setSelectedDirection(dirId);
        form.setData({
            ...form.data,
            direction_id: dirId,
            primary_service_id: '',
            associated_service_ids: [],
        });
    };

    const handlePrimaryServiceChange = (e) => {
        const srvId = e.target.value;
        const filteredAssociated = (form.data.associated_service_ids || []).filter(
            (id) => String(id) !== String(srvId)
        );
        form.setData({
            ...form.data,
            primary_service_id: srvId,
            associated_service_ids: filteredAssociated,
        });
    };

    const toggleAssociatedService = (srvId) => {
        const current = form.data.associated_service_ids || [];
        if (current.includes(srvId)) {
            form.setData('associated_service_ids', current.filter((id) => id !== srvId));
        } else {
            form.setData('associated_service_ids', [...current, srvId]);
        }
    };

    const toggleGroup = (groupId) => {
        const current = form.data.group_ids || [];
        if (current.includes(groupId)) {
            form.setData('group_ids', current.filter((id) => id !== groupId));
        } else {
            form.setData('group_ids', [...current, groupId]);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        form.put(`/users/${user.id}`);
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Modifier ${user.first_name} ${user.last_name}`} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Breadcrumb & Navigation */}
                <div className="flex items-center justify-between">
                    <Breadcrumb
                        items={[
                            { label: 'Utilisateurs', href: '/users' },
                            { label: `${user.first_name} ${user.last_name}`, href: `/users/${user.id}` },
                            { label: 'Modifier' },
                        ]}
                    />
                    <Link href={`/users/${user.id}`}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="w-4 h-4 mr-1.5" />
                            Retour au profil
                        </Button>
                    </Link>
                </div>

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
                        Modifier l'utilisateur
                    </h1>
                    <p className="text-xs text-slate-500 mt-1">
                        Mise à jour des coordonnées, rattachement organisationnel et rôles pour <span className="font-semibold text-slate-700">{user.first_name} {user.last_name}</span>.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Information personnelle */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <Edit3 className="w-4 h-4 text-blue-600" />
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
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                                {form.errors.first_name && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.first_name}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Nom de famille <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={form.data.last_name}
                                    onChange={(e) => form.setData('last_name', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                                {form.errors.last_name && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.last_name}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Adresse email <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    required
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                                {form.errors.email && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Téléphone
                                </label>
                                <input
                                    type="tel"
                                    value={form.data.phone || ''}
                                    onChange={(e) => form.setData('phone', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                            </div>

                            <div className="md:col-span-2">
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Fonction / Poste
                                </label>
                                <div className="relative">
                                    <Briefcase className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                    <input
                                        type="text"
                                        value={form.data.job_title || ''}
                                        onChange={(e) => form.setData('job_title', e.target.value)}
                                        className="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Structure Organisationnelle V2 */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <Building2 className="w-4 h-4 text-indigo-600" />
                            <span>Rattachement Organisationnel (V2)</span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Direction principale
                                </label>
                                <select
                                    value={selectedDirection}
                                    onChange={handleDirectionChange}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                                >
                                    <option value="">Sélectionner une direction</option>
                                    {directions.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Service principal
                                </label>
                                <select
                                    value={form.data.primary_service_id || ''}
                                    onChange={handlePrimaryServiceChange}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                                >
                                    <option value="">Sélectionner un service principal</option>
                                    {availableServices.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        {/* Services associés */}
                        {availableServices.length > 0 && (
                            <div className="pt-2">
                                <label className="block text-xs font-semibold text-slate-700 mb-2">
                                    Services associés (optionnel, rattachés à la direction)
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    {availableServices
                                        .filter((s) => String(s.id) !== String(form.data.primary_service_id))
                                        .map((srv) => {
                                            const isSelected = form.data.associated_service_ids.includes(srv.id);
                                            return (
                                                <button
                                                    key={srv.id}
                                                    type="button"
                                                    onClick={() => toggleAssociatedService(srv.id)}
                                                    className={`flex items-center gap-2 p-2.5 rounded-xl border text-left transition ${
                                                        isSelected
                                                            ? 'bg-indigo-50 border-indigo-300 text-indigo-900 font-medium'
                                                            : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                                                    }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={() => {}}
                                                        className="w-3.5 h-3.5 rounded text-indigo-600 focus:ring-indigo-500 border-slate-300"
                                                    />
                                                    <span className="text-xs truncate">{srv.name}</span>
                                                </button>
                                            );
                                        })}
                                </div>
                                {form.errors.associated_service_ids && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.associated_service_ids}</p>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Droits & Statut */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <Shield className="w-4 h-4 text-purple-600" />
                            <span>Rôle & Statut</span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Rôle <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    required
                                    value={form.data.role}
                                    onChange={(e) => form.setData('role', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                                >
                                    {roles.map((r) => (
                                        <option key={r.id} value={r.name}>
                                            {r.name}
                                        </option>
                                    ))}
                                </select>
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
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white disabled:bg-slate-100"
                                >
                                    <option value="active">Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                            </div>
                        </div>

                        {/* Groupes */}
                        {groups.length > 0 && (
                            <div className="pt-2 border-t border-slate-100">
                                <label className="block text-xs font-semibold text-slate-700 mb-2">
                                    Groupes
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                    {groups.map((group) => {
                                        const isSelected = form.data.group_ids.includes(group.id);
                                        return (
                                            <button
                                                key={group.id}
                                                type="button"
                                                onClick={() => toggleGroup(group.id)}
                                                className={`flex items-center gap-2 p-2.5 rounded-xl border text-left transition ${
                                                    isSelected
                                                        ? 'bg-purple-50 border-purple-300 text-purple-900 font-medium'
                                                        : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={isSelected}
                                                    onChange={() => {}}
                                                    className="w-3.5 h-3.5 rounded text-purple-600 focus:ring-purple-500 border-slate-300"
                                                />
                                                <span className="text-xs truncate">{group.name}</span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Changer mot de passe (optionnel) */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <KeyRound className="w-4 h-4 text-slate-600" />
                            <span>Changer le mot de passe (optionnel)</span>
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
                                    placeholder="Laisser vide pour ne pas modifier"
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Confirmer le mot de passe
                                </label>
                                <input
                                    type="password"
                                    value={form.data.password_confirmation}
                                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                    placeholder="Répétez le mot de passe"
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={`/users/${user.id}`}>
                            <Button variant="secondary" type="button">
                                Annuler
                            </Button>
                        </Link>
                        <Button variant="primary" type="submit" disabled={form.processing}>
                            <Edit3 className="w-4 h-4 mr-1.5" />
                            {form.processing ? 'Enregistrement...' : 'Enregistrer les modifications'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
