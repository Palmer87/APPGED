import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Card from '../../Components/Card';
import Badge from '../../Components/Badge';
import { User as UserIcon, Building2, Bell, Shield, Check } from 'lucide-react';

export default function ProfileIndex({ user, preferences = [], supportedTypes = [] }) {
    const profileForm = useForm({
        first_name: user.first_name || '',
        last_name: user.last_name || '',
        phone: user.phone || '',
        job_title: user.job_title || '',
    });

    // Notifications preferences mapping
    const initialPrefs = supportedTypes.map((type) => {
        const existing = preferences.find(p => p.notification_type === type);
        return {
            notification_type: type,
            database_enabled: existing ? !!existing.database_enabled : true,
            email_enabled: existing ? !!existing.email_enabled : false,
        };
    });

    const prefForm = useForm({
        preferences: initialPrefs,
    });

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        profileForm.put('/profile');
    };

    const handlePrefSubmit = (e) => {
        e.preventDefault();
        prefForm.put('/profile/preferences');
    };

    const togglePref = (index, field) => {
        const updated = [...prefForm.data.preferences];
        updated[index][field] = !updated[index][field];
        prefForm.setData('preferences', updated);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Mon Profil" />

            <div className="max-w-4xl mx-auto space-y-8">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Profil & Paramètres</h1>
                    <p className="text-xs text-slate-500 mt-1">Gérez vos informations personnelles et configurez vos alertes.</p>
                </div>

                {/* Organization & Roles Overview */}
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-2xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <div className="w-14 h-14 rounded-2xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-indigo-100">
                            {user.first_name ? user.first_name[0].toUpperCase() : 'U'}
                        </div>
                        <div>
                            <h2 className="text-lg font-bold text-slate-900">{user.first_name} {user.last_name}</h2>
                            <p className="text-xs text-slate-500">{user.email}</p>
                            <div className="flex items-center gap-2 mt-2">
                                <span className="inline-flex items-center gap-1 text-xs text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                                    <Building2 className="w-3.5 h-3.5 text-slate-400" />
                                    {user.organization?.name || 'Aucune organisation'}
                                </span>
                                {user.roles?.map(r => (
                                    <Badge key={r} variant="primary" size="sm">
                                        {r}
                                    </Badge>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Personal Information Form */}
                <Card title="Informations personnelles" subtitle="Mettez à jour vos coordonnées.">
                    <form onSubmit={handleProfileSubmit} className="space-y-4">
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input
                                id="first-name"
                                label="Prénom"
                                required
                                value={profileForm.data.first_name}
                                onChange={(e) => profileForm.setData('first_name', e.target.value)}
                                error={profileForm.errors.first_name}
                            />
                            <Input
                                id="last-name"
                                label="Nom"
                                required
                                value={profileForm.data.last_name}
                                onChange={(e) => profileForm.setData('last_name', e.target.value)}
                                error={profileForm.errors.last_name}
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <Input
                                id="job-title"
                                label="Fonction / Poste"
                                value={profileForm.data.job_title}
                                onChange={(e) => profileForm.setData('job_title', e.target.value)}
                                placeholder="Ex: Directeur Financier"
                                error={profileForm.errors.job_title}
                            />
                            <Input
                                id="phone"
                                label="Téléphone"
                                value={profileForm.data.phone}
                                onChange={(e) => profileForm.setData('phone', e.target.value)}
                                placeholder="+33 6 12 34 56 78"
                                error={profileForm.errors.phone}
                            />
                        </div>

                        <div className="flex justify-end pt-3">
                            <Button type="submit" variant="primary" loading={profileForm.processing}>
                                Enregistrer les modifications
                            </Button>
                        </div>
                    </form>
                </Card>

                {/* Notification Preferences Form */}
                <Card title="Préférences de notifications" subtitle="Choisissez les canaux sur lesquels vous souhaitez être averti.">
                    <form onSubmit={handlePrefSubmit} className="space-y-4">
                        <div className="divide-y divide-slate-100">
                            {prefForm.data.preferences.map((pref, idx) => (
                                <div key={pref.notification_type} className="py-3.5 flex items-center justify-between gap-4">
                                    <div>
                                        <span className="text-sm font-semibold text-slate-800 block">
                                            {pref.notification_type}
                                        </span>
                                        <span className="text-xs text-slate-400">
                                            Notifications liées aux événements de type {pref.notification_type}.
                                        </span>
                                    </div>
                                    <div className="flex items-center gap-6 text-xs font-medium text-slate-700">
                                        <label className="flex items-center gap-1.5 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={pref.database_enabled}
                                                onChange={() => togglePref(idx, 'database_enabled')}
                                                className="rounded text-indigo-600 focus:ring-indigo-500"
                                            />
                                            Application (In-App)
                                        </label>
                                        <label className="flex items-center gap-1.5 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={pref.email_enabled}
                                                onChange={() => togglePref(idx, 'email_enabled')}
                                                className="rounded text-indigo-600 focus:ring-indigo-500"
                                            />
                                            E-mail
                                        </label>
                                    </div>
                                </div>
                            ))}
                        </div>

                        <div className="flex justify-end pt-4 border-t border-slate-100">
                            <Button type="submit" variant="primary" loading={prefForm.processing}>
                                Mettre à jour les préférences
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
