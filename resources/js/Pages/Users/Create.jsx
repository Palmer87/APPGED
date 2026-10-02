import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Breadcrumb from '../../Components/Breadcrumb';
import {
    UserPlus,
    ArrowLeft,
    Shield,
    Users,
    KeyRound,
    Briefcase,
    Building2,
    Network
} from 'lucide-react';

export default function UsersCreate({ roles = [], groups = [], directions = [], services = [] }) {
    const form = useForm({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        job_title: '',
        password: '',
        password_confirmation: '',
        direction_id: '',
        primary_service_id: '',
        associated_service_ids: [],
        role: roles[0]?.name || 'utilisateur',
        access_scope_type: 'service',
        access_scope_direction_id: '',
        access_scope_service_id: '',
        status: 'active',
        group_ids: [],
    });

    const [selectedDirection, setSelectedDirection] = useState('');

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
            access_scope_direction_id: dirId,
            access_scope_service_id: '',
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
            access_scope_service_id: srvId,
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
        form.post('/users');
    };

    return (
        <AuthenticatedLayout>
            <Head title="Créer un utilisateur (V2)" />

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
                            <ArrowLeft className="w-4 h-4 mr-1.5" />
                            Retour à la liste
                        </Button>
                    </Link>
                </div>

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Nouvel utilisateur</h1>
                    <p className="text-xs text-slate-500 mt-1">
                        Créez un nouvel accès utilisateur avec son rattachement organisationnel et son périmètre d'accès.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Information personnelle */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <UserPlus className="w-4 h-4 text-blue-600" />
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
                                    placeholder="Ex: Dupont"
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                                {form.errors.last_name && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.last_name}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Adresse email professionnelle <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    required
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    placeholder="jean.dupont@entreprise.com"
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                                {form.errors.email && (
                                    <p className="text-[11px] text-rose-500 mt-1">{form.errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Numéro de téléphone
                                </label>
                                <input
                                    type="tel"
                                    value={form.data.phone}
                                    onChange={(e) => form.setData('phone', e.target.value)}
                                    placeholder="+33 6 12 34 56 78"
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
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
                                        placeholder="Ex: Comptable, Responsable Paie, Juriste..."
                                        className="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Rattachement Organisationnel V2 */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <Building2 className="w-4 h-4 text-indigo-600" />
                            <span>Structure Organisationnelle (V2)</span>
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
                                <p className="text-[11px] text-slate-400 mt-1">
                                    Filtre les services proposés ci-dessous.
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Service principal
                                </label>
                                <select
                                    value={form.data.primary_service_id}
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
                                <p className="text-[11px] text-slate-400 mt-1">
                                    Service par défaut pour le dashboard et l'importation de documents.
                                </p>
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

                    {/* Rôles & Périmètres d'accès */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <Shield className="w-4 h-4 text-purple-600" />
                            <span>Rôle & Périmètre d'accès</span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Rôle (Ce qu'il peut faire) <span className="text-rose-500">*</span>
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
                                    Périmètre initial (Où il peut le faire)
                                </label>
                                <select
                                    value={form.data.access_scope_type}
                                    onChange={(e) => form.setData('access_scope_type', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                                >
                                    <option value="service">Limité à son service principal</option>
                                    <option value="direction">Toute sa direction</option>
                                    <option value="organization">Organisation entière (Directeurs / Admins)</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-xs font-semibold text-slate-700 mb-1">
                                    Statut du compte <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    required
                                    value={form.data.status}
                                    onChange={(e) => form.setData('status', e.target.value)}
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 bg-white"
                                >
                                    <option value="active">Actif (Peut se connecter)</option>
                                    <option value="inactive">Inactif (Connexion bloquée)</option>
                                </select>
                            </div>
                        </div>

                        {/* Groupes */}
                        {groups.length > 0 && (
                            <div className="pt-2 border-t border-slate-100">
                                <label className="block text-xs font-semibold text-slate-700 mb-2">
                                    Groupes fonctionnels
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

                    {/* Mot de passe */}
                    <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-2xs space-y-4">
                        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 text-slate-900 font-semibold text-sm">
                            <KeyRound className="w-4 h-4 text-slate-600" />
                            <span>Mot de passe</span>
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
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
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
                                    className="w-full px-3 py-2 text-xs rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href="/users">
                            <Button variant="secondary" type="button">
                                Annuler
                            </Button>
                        </Link>
                        <Button variant="primary" type="submit" disabled={form.processing}>
                            <UserPlus className="w-4 h-4 mr-1.5" />
                            {form.processing ? 'Création...' : "Créer l'utilisateur"}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
