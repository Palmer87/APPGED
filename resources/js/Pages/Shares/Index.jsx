import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Tabs from '../../Components/Tabs';
import Table from '../../Components/Table';
import Button from '../../Components/Button';
import Badge from '../../Components/Badge';
import FileIcon from '../../Components/FileIcon';
import EmptyState from '../../Components/EmptyState';
import { Share2, Download, Eye, Trash2, User, Users } from 'lucide-react';

export default function SharesIndex({ receivedShares = [], sentShares = [] }) {
    const [activeTab, setActiveTab] = useState('received');

    const handleRevoke = (share) => {
        router.delete(`/documents/${share.document_id}/shares/${share.id}`, {
            preserveScroll: true,
        });
    };

    const tabs = [
        { id: 'received', label: 'Partagés avec moi', icon: <Share2 className="w-4 h-4" />, badge: receivedShares.length },
        { id: 'sent', label: 'Partagés par moi', icon: <User className="w-4 h-4" />, badge: sentShares.length },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Documents partagés" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Partage documentaire</h1>
                    <p className="text-xs text-slate-500 mt-1">Consultez les fichiers reçus ou gérez les droits accordés à vos collaborateurs.</p>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} className="px-6" />

                    <div className="p-6">
                        {activeTab === 'received' ? (
                            receivedShares.length > 0 ? (
                                <Table headers={['Document', 'Partagé par', 'Permission', 'Expiration', 'Actions']}>
                                    {receivedShares.map((share) => (
                                        <tr key={share.id} className="hover:bg-slate-50/70 transition">
                                            <td className="px-6 py-4">
                                                <div className="flex items-center gap-3">
                                                    <FileIcon extension={share.document?.extension} mimeType={share.document?.mime_type} className="w-6 h-6 shrink-0" />
                                                    <Link
                                                        href={`/documents/${share.document?.id}`}
                                                        className="font-semibold text-slate-900 hover:text-indigo-600 block text-sm truncate"
                                                    >
                                                        {share.document?.name}
                                                    </Link>
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-600">
                                                {share.shared_by?.name || 'Inconnu'}
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                <Badge variant="primary" size="sm">
                                                    {share.permission}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-400">
                                                {share.expires_at ? new Date(share.expires_at).toLocaleDateString('fr-FR') : 'Permanente'}
                                            </td>
                                            <td className="px-6 py-4 text-right text-xs">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Link
                                                        href={`/documents/${share.document?.id}`}
                                                        className="p-1.5 text-slate-400 hover:text-indigo-600"
                                                        title="Consulter"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>
                                                    <a
                                                        href={`/documents/${share.document?.id}/download`}
                                                        className="p-1.5 text-slate-400 hover:text-slate-700"
                                                        title="Télécharger"
                                                    >
                                                        <Download className="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </Table>
                            ) : (
                                <EmptyState
                                    title="Aucun document partagé avec vous"
                                    description="Les documents que d'autres utilisateurs partagent avec vous apparaîtront ici."
                                />
                            )
                        ) : (
                            sentShares.length > 0 ? (
                                <Table headers={['Document', 'Bénéficiaire', 'Permission', 'Date', 'Actions']}>
                                    {sentShares.map((share) => (
                                        <tr key={share.id} className="hover:bg-slate-50/70 transition">
                                            <td className="px-6 py-4 font-medium text-slate-900 text-sm">
                                                {share.document?.name}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-700">
                                                {share.user ? (
                                                    <span className="inline-flex items-center gap-1.5 font-medium">
                                                        <User className="w-3.5 h-3.5 text-slate-400" />
                                                        {share.user.name}
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center gap-1.5 font-medium text-purple-700">
                                                        <Users className="w-3.5 h-3.5 text-purple-500" />
                                                        {share.group?.name}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 text-xs">
                                                <Badge variant="primary" size="sm">{share.permission}</Badge>
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-400">
                                                {new Date(share.created_at).toLocaleDateString('fr-FR')}
                                            </td>
                                            <td className="px-6 py-4 text-right text-xs">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-rose-600 hover:text-rose-700"
                                                    onClick={() => handleRevoke(share)}
                                                >
                                                    <Trash2 className="w-3.5 h-3.5" />
                                                    Révoquer
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </Table>
                            ) : (
                                <EmptyState
                                    title="Vous n'avez partagé aucun document"
                                    description="Ouvrez un document et cliquez sur Partager pour accorder des droits à vos équipes."
                                />
                            )
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
