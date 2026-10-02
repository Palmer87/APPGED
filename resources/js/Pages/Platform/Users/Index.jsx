import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Users,
    Search,
    Building2,
    Shield,
    CheckCircle2,
    XCircle,
    Mail,
    Phone,
    Briefcase
} from 'lucide-react';

export default function UsersIndex({
    users,
    organizations = [],
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [organizationId, setOrganizationId] = useState(filters.organization_id || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/users', {
            search: search.trim() || undefined,
            organization_id: organizationId || undefined,
            status: status || undefined,
        }, { preserveState: true });
    };

    return (
        <PlatformLayout title="Annuaire des Utilisateurs Clients">
            <Head title="Utilisateurs Clients — Console Propriétaire" />

            <div className="space-y-6">
                {/* Search & Filter Bar */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        <div className="relative w-full sm:w-72">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="Nom, email, fonction..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-10 pr-4 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            />
                        </div>

                        <select
                            value={organizationId}
                            onChange={(e) => {
                                setOrganizationId(e.target.value);
                                router.get('/platform/users', {
                                    search: search.trim() || undefined,
                                    organization_id: e.target.value || undefined,
                                    status: status || undefined,
                                }, { preserveState: true });
                            }}
                            className="w-full sm:w-56 px-3 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                        >
                            <option value="">Toutes les organisations</option>
                            {organizations.map((org) => (
                                <option key={org.id} value={org.id}>
                                    {org.name}
                                </option>
                            ))}
                        </select>

                        <select
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                router.get('/platform/users', {
                                    search: search.trim() || undefined,
                                    organization_id: organizationId || undefined,
                                    status: e.target.value || undefined,
                                }, { preserveState: true });
                            }}
                            className="w-full sm:w-40 px-3 py-2.5 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                        >
                            <option value="">Tous les statuts</option>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                            <option value="pending">En attente</option>
                        </select>

                        <button
                            type="submit"
                            className="w-full sm:w-auto px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold rounded-xl transition"
                        >
                            Filtrer
                        </button>
                    </form>

                    <div className="text-xs text-slate-400">
                        Consulter sans altérer directement les permissions de l'organisation.
                    </div>
                </div>

                {/* Directory Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl shadow-slate-950/40">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-400">
                            <thead className="bg-slate-950/60 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80">
                                <tr>
                                    <th className="px-6 py-4">Utilisateur</th>
                                    <th className="px-6 py-4">Organisation</th>
                                    <th className="px-6 py-4">Direction / Service</th>
                                    <th className="px-6 py-4">Rôles</th>
                                    <th className="px-6 py-4">Statut</th>
                                    <th className="px-6 py-4">Dernière Connexion</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {users.data && users.data.length > 0 ? (
                                    users.data.map((user) => (
                                        <tr key={user.id} className="hover:bg-slate-800/30 transition">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-slate-300 text-xs">
                                                        {user.name.slice(0, 2).toUpperCase()}
                                                    </div>
                                                    <div>
                                                        <div className="font-bold text-slate-200">
                                                            {user.name}
                                                        </div>
                                                        <div className="text-xs text-slate-400 flex items-center gap-1.5 mt-0.5">
                                                            <Mail className="w-3 h-3 text-slate-500" />
                                                            {user.email}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-1.5">
                                                    <Building2 className="w-4 h-4 text-slate-500" />
                                                    <Link
                                                        href={`/platform/organizations/${user.organization_id}`}
                                                        className="font-medium text-slate-200 hover:text-indigo-400 transition"
                                                    >
                                                        {user.organization_name}
                                                    </Link>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                <div className="text-slate-300 font-medium">
                                                    {user.direction_name || 'Direction N/A'}
                                                </div>
                                                <div className="text-slate-500 mt-0.5">
                                                    {user.service_name || 'Sans service assigné'}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap gap-1">
                                                    {user.roles && user.roles.length > 0 ? (
                                                        user.roles.map((r, i) => (
                                                            <span
                                                                key={i}
                                                                className="px-2 py-0.5 rounded text-[11px] font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20"
                                                            >
                                                                {r}
                                                            </span>
                                                        ))
                                                    ) : (
                                                        <span className="text-xs text-slate-600">Aucun</span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4">
                                                {user.status === 'active' ? (
                                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-400">
                                                        <CheckCircle2 className="w-3.5 h-3.5" /> Actif
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1 text-xs font-semibold text-slate-500">
                                                        <XCircle className="w-3.5 h-3.5" /> {user.status || 'Inactif'}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-400">
                                                {user.last_login_at || 'Jamais connecté'}
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="6" className="px-6 py-12 text-center text-slate-500">
                                            <Users className="w-10 h-10 mx-auto mb-3 opacity-40" />
                                            Aucun utilisateur client trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {users.links && users.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between">
                            <span className="text-xs text-slate-500">
                                Total : {users.total} utilisateur(s)
                            </span>
                            <div className="flex gap-1">
                                {users.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                                            link.active
                                                ? 'bg-indigo-600 text-white'
                                                : link.url
                                                ? 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                                                : 'bg-slate-900 text-slate-600 cursor-not-allowed'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </PlatformLayout>
    );
}
