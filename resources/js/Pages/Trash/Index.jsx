import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Table from '../../Components/Table';
import Pagination from '../../Components/Pagination';
import FileIcon from '../../Components/FileIcon';
import ConfirmDialog from '../../Components/ConfirmDialog';
import EmptyState from '../../Components/EmptyState';
import { Trash2, RotateCcw, AlertTriangle } from 'lucide-react';

export default function TrashIndex({ documents }) {
    const [selectedDoc, setSelectedDoc] = useState(null);
    const [confirmForceModalOpen, setConfirmForceModalOpen] = useState(false);
    const [confirmEmptyModalOpen, setConfirmEmptyModalOpen] = useState(false);

    const handleRestore = (doc) => {
        router.post(`/documents/${doc.id}/restore`, {}, {
            preserveScroll: true,
        });
    };

    const handleForceDelete = () => {
        if (!selectedDoc) return;
        router.delete(`/documents/${selectedDoc.id}/force`, {
            preserveScroll: true,
            onSuccess: () => setConfirmForceModalOpen(false),
        });
    };

    const handleEmptyTrash = () => {
        router.post('/documents/trash/empty', {}, {
            preserveScroll: true,
            onSuccess: () => setConfirmEmptyModalOpen(false),
        });
    };

    const formatBytes = (bytes) => {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'Ko', 'Mo', 'Go'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const docList = documents?.data || [];

    return (
        <AuthenticatedLayout>
            <Head title="Corbeille" />

            <div className="space-y-6">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            <Trash2 className="w-6 h-6 text-rose-600" />
                            Corbeille
                        </h1>
                        <p className="text-xs text-slate-500 mt-1">Consultez, restaurez ou purgez définitivement les fichiers supprimés.</p>
                    </div>

                    {docList.length > 0 && (
                        <Button
                            variant="danger"
                            size="sm"
                            onClick={() => setConfirmEmptyModalOpen(true)}
                        >
                            <Trash2 className="w-4 h-4" />
                            Vider la corbeille
                        </Button>
                    )}
                </div>

                {docList.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Document', 'Taille', 'Supprimé le', 'Actions']}>
                            {docList.map((doc) => (
                                <tr key={doc.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <FileIcon extension={doc.extension} mimeType={doc.mime_type} className="w-6 h-6 shrink-0 opacity-60" />
                                            <div>
                                                <span className="font-semibold text-sm text-slate-700 block truncate line-through">
                                                    {doc.name}
                                                </span>
                                                <span className="text-[11px] uppercase font-bold text-slate-400">
                                                    {doc.extension}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 font-mono text-xs text-slate-500">
                                        {formatBytes(doc.size)}
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-400">
                                        {doc.deleted_at ? new Date(doc.deleted_at).toLocaleString('fr-FR') : '—'}
                                    </td>
                                    <td className="px-6 py-4 text-right text-xs">
                                        <div className="flex items-center justify-end gap-2">
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                onClick={() => handleRestore(doc)}
                                                className="text-indigo-600 hover:text-indigo-800"
                                            >
                                                <RotateCcw className="w-3.5 h-3.5" />
                                                Restaurer
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-rose-600 hover:text-rose-700"
                                                onClick={() => {
                                                    setSelectedDoc(doc);
                                                    setConfirmForceModalOpen(true);
                                                }}
                                            >
                                                <Trash2 className="w-3.5 h-3.5" />
                                                Purger
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </Table>
                        <Pagination pagination={documents} />
                    </div>
                ) : (
                    <EmptyState
                        title="La corbeille est vide"
                        description="Tous vos documents sont en sécurité dans vos dossiers."
                    />
                )}
            </div>

            {/* Confirm Force Delete */}
            <ConfirmDialog
                isOpen={confirmForceModalOpen}
                onClose={() => setConfirmForceModalOpen(false)}
                onConfirm={handleForceDelete}
                title="Suppression définitive"
                message={`Êtes-vous sûr de vouloir supprimer définitivement "${selectedDoc?.name}" ? Cette action est irréversible et effacera tous les fichiers associés sur le disque.`}
                confirmLabel="Supprimer définitivement"
                variant="danger"
            />

            {/* Confirm Empty Trash */}
            <ConfirmDialog
                isOpen={confirmEmptyModalOpen}
                onClose={() => setConfirmEmptyModalOpen(false)}
                onConfirm={handleEmptyTrash}
                title="Vider la corbeille"
                message="Êtes-vous absolument certain de vouloir vider l'ensemble de la corbeille ? Tous les documents seront définitivement détruits."
                confirmLabel="Vider définitivement"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
