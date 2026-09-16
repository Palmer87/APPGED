import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Pagination from '../../Components/Pagination';
import EmptyState from '../../Components/EmptyState';
import { Bell, CheckCheck, Check, Trash2, ExternalLink } from 'lucide-react';

export default function NotificationsIndex({ notifications, unread_count = 0 }) {
    const handleMarkAsRead = (id) => {
        router.post(`/notifications/${id}/read`, {}, { preserveScroll: true });
    };

    const handleMarkAllAsRead = () => {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
    };

    const handleDelete = (id) => {
        router.delete(`/notifications/${id}`, { preserveScroll: true });
    };

    const notifList = notifications?.data || [];

    return (
        <AuthenticatedLayout>
            <Head title="Centre de notifications" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            <Bell className="w-6 h-6 text-indigo-600" />
                            Notifications
                        </h1>
                        <p className="text-xs text-slate-500 mt-1">
                            {unread_count > 0
                                ? `Vous avez ${unread_count} notification(s) non lue(s).`
                                : 'Toutes vos notifications sont à jour.'}
                        </p>
                    </div>

                    {unread_count > 0 && (
                        <Button variant="secondary" size="sm" onClick={handleMarkAllAsRead}>
                            <CheckCheck className="w-4 h-4" />
                            Tout marquer comme lu
                        </Button>
                    )}
                </div>

                {notifList.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs divide-y divide-slate-100">
                        {notifList.map((notif) => {
                            const isUnread = notif.read_at === null;
                            const notifData = notif.data || {};
                            return (
                                <div
                                    key={notif.id}
                                    className={`p-4 sm:p-5 flex items-start justify-between gap-4 transition ${
                                        isUnread ? 'bg-indigo-50/40' : 'hover:bg-slate-50/50'
                                    }`}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className={`w-2.5 h-2.5 rounded-full mt-2 shrink-0 ${isUnread ? 'bg-indigo-600' : 'bg-transparent'}`} />
                                        <div className="space-y-1">
                                            <p className={`text-sm ${isUnread ? 'font-bold text-slate-900' : 'font-medium text-slate-700'}`}>
                                                {notifData.message || notifData.title || notif.type}
                                            </p>
                                            {notifData.description && (
                                                <p className="text-xs text-slate-500">{notifData.description}</p>
                                            )}
                                            <span className="text-[11px] text-slate-400 block">
                                                {new Date(notif.created_at).toLocaleString('fr-FR')}
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2 shrink-0">
                                        {notifData.document_id && (
                                            <Link
                                                href={`/documents/${notifData.document_id}`}
                                                className="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100"
                                                title="Ouvrir le document"
                                            >
                                                <ExternalLink className="w-4 h-4" />
                                            </Link>
                                        )}
                                        {isUnread && (
                                            <button
                                                type="button"
                                                onClick={() => handleMarkAsRead(notif.id)}
                                                className="p-1.5 text-slate-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50"
                                                title="Marquer comme lu"
                                            >
                                                <Check className="w-4 h-4" />
                                            </button>
                                        )}
                                        <button
                                            type="button"
                                            onClick={() => handleDelete(notif.id)}
                                            className="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50"
                                            title="Supprimer"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                        <Pagination pagination={notifications} />
                    </div>
                ) : (
                    <EmptyState
                        title="Aucune notification"
                        description="Vous recevrez ici des alertes lors des partages, validations et commentaires."
                    />
                )}
            </div>
        </AuthenticatedLayout>
    );
}
