import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import PlatformLayout from '../../../Layouts/PlatformLayout';
import {
    Headphones,
    Search,
    PlusCircle,
    Building2,
    Clock,
    CheckCircle2,
    AlertCircle,
    X,
    Filter,
    Edit3
} from 'lucide-react';

export default function SupportIndex({
    tickets,
    organizations = [],
    agents = [],
    filters = {},
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [priority, setPriority] = useState(filters.priority || '');
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [editModalTicket, setEditModalTicket] = useState(null);

    const createForm = useForm({
        organization_id: organizations[0]?.id || '',
        subject: '',
        description: '',
        priority: 'normal',
        platform_user_id: agents[0]?.id || '',
    });

    const editForm = useForm({
        status: 'open',
        priority: 'normal',
        platform_user_id: '',
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/platform/support', {
            search: search.trim() || undefined,
            status: status || undefined,
            priority: priority || undefined,
        }, { preserveState: true });
    };

    const handleCreateTicket = (e) => {
        e.preventDefault();
        createForm.post('/platform/support', {
            onSuccess: () => {
                createForm.reset();
                setCreateModalOpen(false);
            }
        });
    };

    const openEditModal = (t) => {
        setEditModalTicket(t);
        editForm.setData({
            status: t.status,
            priority: t.priority,
            platform_user_id: agents.find(a => a.name === t.assigned_agent_name)?.id || '',
        });
    };

    const handleUpdateTicket = (e) => {
        e.preventDefault();
        if (!editModalTicket) return;

        editForm.put(`/platform/support/${editModalTicket.id}`, {
            onSuccess: () => {
                setEditModalTicket(null);
            }
        });
    };

    const getPriorityBadge = (p) => {
        switch (p) {
            case 'urgent':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">Urgent</span>;
            case 'high':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30">Élevée</span>;
            case 'normal':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-500/20 text-blue-400 border border-blue-500/30">Normale</span>;
            case 'low':
                return <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-800 text-slate-400">Faible</span>;
            default:
                return <span>{p}</span>;
        }
    };

    const getStatusBadge = (s) => {
        switch (s) {
            case 'open':
                return <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-500/20 text-sky-400 border border-sky-500/30"><Clock className="w-3 h-3" /> Ouvert</span>;
            case 'in_progress':
                return <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30">En cours</span>;
            case 'resolved':
                return <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30"><CheckCircle2 className="w-3 h-3" /> Résolu</span>;
            case 'closed':
                return <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-800 text-slate-400">Fermé</span>;
            default:
                return <span>{s}</span>;
        }
    };

    return (
        <PlatformLayout title="Assistance & Support Client">
            <Head title="Support Clients — Console Propriétaire" />

            <div className="space-y-6">
                {/* Actions & Filters */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-4 flex flex-col md:flex-row items-center justify-between gap-4">
                    <form onSubmit={handleSearch} className="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                        <div className="relative w-full sm:w-64">
                            <Search className="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="N° ticket, sujet, organisation..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                            />
                        </div>

                        <select
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                router.get('/platform/support', {
                                    search: search.trim() || undefined,
                                    status: e.target.value || undefined,
                                    priority: priority || undefined
                                }, { preserveState: true });
                            }}
                            className="w-full sm:w-36 px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                        >
                            <option value="">Tous statuts</option>
                            <option value="open">Ouvert</option>
                            <option value="in_progress">En cours</option>
                            <option value="resolved">Résolu</option>
                            <option value="closed">Fermé</option>
                        </select>

                        <select
                            value={priority}
                            onChange={(e) => {
                                setPriority(e.target.value);
                                router.get('/platform/support', {
                                    search: search.trim() || undefined,
                                    status: status || undefined,
                                    priority: e.target.value || undefined
                                }, { preserveState: true });
                            }}
                            className="w-full sm:w-36 px-3 py-2 bg-slate-950/60 border border-slate-800 rounded-xl text-sm text-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                        >
                            <option value="">Toutes priorités</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">Élevée</option>
                            <option value="normal">Normale</option>
                            <option value="low">Faible</option>
                        </select>

                        <button
                            type="submit"
                            className="w-full sm:w-auto px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm font-semibold rounded-xl transition"
                        >
                            Filtrer
                        </button>
                    </form>

                    <button
                        onClick={() => setCreateModalOpen(true)}
                        className="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-semibold rounded-xl transition shadow-lg shadow-indigo-600/30 whitespace-nowrap"
                    >
                        <PlusCircle className="w-4 h-4" /> Nouveau Ticket
                    </button>
                </div>

                {/* Tickets Table */}
                <div className="bg-slate-900/60 border border-slate-800/80 rounded-3xl overflow-hidden shadow-xl shadow-slate-950/40">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-sm text-slate-400">
                            <thead className="bg-slate-950/60 text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800/80">
                                <tr>
                                    <th className="px-6 py-4">N° Ticket</th>
                                    <th className="px-6 py-4">Organisation</th>
                                    <th className="px-6 py-4">Sujet</th>
                                    <th className="px-6 py-4">Priorité</th>
                                    <th className="px-6 py-4">Statut</th>
                                    <th className="px-6 py-4">Assigné à</th>
                                    <th className="px-6 py-4">Créé le</th>
                                    <th className="px-6 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-800/60">
                                {tickets.data && tickets.data.length > 0 ? (
                                    tickets.data.map((ticket) => (
                                        <tr key={ticket.id} className="hover:bg-slate-800/30 transition">
                                            <td className="px-6 py-4 font-mono font-bold text-slate-200">
                                                {ticket.ticket_number}
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-1.5">
                                                    <Building2 className="w-4 h-4 text-slate-500" />
                                                    <Link
                                                        href={`/platform/organizations/${ticket.organization_id}`}
                                                        className="font-medium text-slate-200 hover:text-indigo-400 transition"
                                                    >
                                                        {ticket.organization_name}
                                                    </Link>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 font-medium text-slate-200">
                                                {ticket.subject}
                                            </td>
                                            <td className="px-6 py-4">
                                                {getPriorityBadge(ticket.priority)}
                                            </td>
                                            <td className="px-6 py-4">
                                                {getStatusBadge(ticket.status)}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-300">
                                                {ticket.assigned_agent_name}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-400">
                                                {ticket.created_at}
                                            </td>
                                            <td className="px-6 py-4 text-right">
                                                <button
                                                    onClick={() => openEditModal(ticket)}
                                                    className="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg transition"
                                                >
                                                    <Edit3 className="w-3.5 h-3.5" /> Gérer
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan="8" className="px-6 py-12 text-center text-slate-500">
                                            <Headphones className="w-10 h-10 mx-auto mb-3 opacity-40" />
                                            Aucun ticket de support trouvé.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {tickets.links && tickets.links.length > 3 && (
                        <div className="px-6 py-4 border-t border-slate-800/80 flex items-center justify-between">
                            <span className="text-xs text-slate-500">
                                Total : {tickets.total} ticket(s)
                            </span>
                            <div className="flex gap-1">
                                {tickets.links.map((link, idx) => (
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

                {/* Create Ticket Modal */}
                {createModalOpen && (
                    <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
                        <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-lg shadow-2xl relative">
                            <button
                                onClick={() => setCreateModalOpen(false)}
                                className="absolute top-6 right-6 text-slate-400 hover:text-slate-200"
                            >
                                <X className="w-5 h-5" />
                            </button>

                            <h3 className="text-lg font-bold text-white mb-4">Ouvrir un Ticket de Support</h3>

                            <form onSubmit={handleCreateTicket} className="space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Organisation</label>
                                    <select
                                        value={createForm.data.organization_id}
                                        onChange={(e) => createForm.setData('organization_id', e.target.value)}
                                        className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                        required
                                    >
                                        {organizations.map((org) => (
                                            <option key={org.id} value={org.id}>{org.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Sujet</label>
                                    <input
                                        type="text"
                                        value={createForm.data.subject}
                                        onChange={(e) => createForm.setData('subject', e.target.value)}
                                        placeholder="Ex: Problème d'indexation OCR sur les contrats"
                                        className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                        required
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Description détaillée</label>
                                    <textarea
                                        rows={4}
                                        value={createForm.data.description}
                                        onChange={(e) => createForm.setData('description', e.target.value)}
                                        placeholder="Décrire le problème rencontré par le client..."
                                        className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                        required
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-300 mb-1">Priorité</label>
                                        <select
                                            value={createForm.data.priority}
                                            onChange={(e) => createForm.setData('priority', e.target.value)}
                                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                        >
                                            <option value="low">Faible</option>
                                            <option value="normal">Normale</option>
                                            <option value="high">Élevée</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-semibold text-slate-300 mb-1">Agent Assigné</label>
                                        <select
                                            value={createForm.data.platform_user_id}
                                            onChange={(e) => createForm.setData('platform_user_id', e.target.value)}
                                            className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                        >
                                            <option value="">Non assigné</option>
                                            {agents.map((ag) => (
                                                <option key={ag.id} value={ag.id}>{ag.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="pt-2 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setCreateModalOpen(false)}
                                        className="px-4 py-2 text-sm text-slate-400 hover:text-slate-200"
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={createForm.processing}
                                        className="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition"
                                    >
                                        Créer le Ticket
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* Edit/Update Ticket Modal */}
                {editModalTicket && (
                    <div className="fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
                        <div className="bg-slate-900 border border-slate-800 rounded-3xl p-6 w-full max-w-md shadow-2xl relative">
                            <button
                                onClick={() => setEditModalTicket(null)}
                                className="absolute top-6 right-6 text-slate-400 hover:text-slate-200"
                            >
                                <X className="w-5 h-5" />
                            </button>

                            <h3 className="text-lg font-bold text-white mb-2">Ticket #{editModalTicket.ticket_number}</h3>
                            <p className="text-xs text-slate-400 mb-4">{editModalTicket.subject}</p>

                            <form onSubmit={handleUpdateTicket} className="space-y-4">
                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Statut du Ticket</label>
                                    <select
                                        value={editForm.data.status}
                                        onChange={(e) => editForm.setData('status', e.target.value)}
                                        className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    >
                                        <option value="open">Ouvert</option>
                                        <option value="in_progress">En cours</option>
                                        <option value="resolved">Résolu</option>
                                        <option value="closed">Fermé</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Priorité</label>
                                    <select
                                        value={editForm.data.priority}
                                        onChange={(e) => editForm.setData('priority', e.target.value)}
                                        className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    >
                                        <option value="low">Faible</option>
                                        <option value="normal">Normale</option>
                                        <option value="high">Élevée</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-slate-300 mb-1">Assigner à</label>
                                    <select
                                        value={editForm.data.platform_user_id}
                                        onChange={(e) => editForm.setData('platform_user_id', e.target.value)}
                                        className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50"
                                    >
                                        <option value="">Non assigné</option>
                                        {agents.map((ag) => (
                                            <option key={ag.id} value={ag.id}>{ag.name}</option>
                                        ))}
                                    </select>
                                </div>

                                <div className="pt-2 flex justify-end gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setEditModalTicket(null)}
                                        className="px-4 py-2 text-sm text-slate-400 hover:text-slate-200"
                                    >
                                        Fermer
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={editForm.processing}
                                        className="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold rounded-xl transition"
                                    >
                                        Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </PlatformLayout>
    );
}
