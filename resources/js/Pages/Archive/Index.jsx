import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Table from '../../Components/Table';
import Pagination from '../../Components/Pagination';
import FileIcon from '../../Components/FileIcon';
import EmptyState from '../../Components/EmptyState';
import { Archive, RotateCcw, Eye, Download } from 'lucide-react';

export default function ArchiveIndex({ documents }) {
    const handleUnarchive = (doc) => {
        router.post(`/documents/${doc.id}/unarchive`, {}, {
            preserveScroll: true,
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
            <Head title="Archives" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <Archive className="w-6 h-6 text-indigo-600" />
                        Documents archivés
                    </h1>
                    <p className="text-xs text-slate-500 mt-1">Consultez ou réactivez les fichiers archivés de votre organisation.</p>
                </div>

                {docList.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Document', 'Dossier', 'Taille', 'Date d\'archivage', 'Actions']}>
                            {docList.map((doc) => (
                                <tr key={doc.id} className="hover:bg-slate-50/70 transition">
                                    <td className="px-6 py-4">
                                        <div className="flex items-center gap-3">
                                            <FileIcon extension={doc.extension} mimeType={doc.mime_type} className="w-6 h-6 shrink-0" />
                                            <div className="min-w-0">
                                                <Link
                                                    href={`/documents/${doc.id}`}
                                                    className="font-semibold text-slate-900 hover:text-indigo-600 block truncate text-sm"
                                                >
                                                    {doc.name}
                                                </Link>
                                                <span className="text-[11px] uppercase font-bold text-slate-400">
                                                    {doc.extension}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-600">
                                        {doc.folder?.name || 'Racine'}
                                    </td>
                                    <td className="px-6 py-4 font-mono text-xs text-slate-500">
                                        {formatBytes(doc.size)}
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-400">
                                        {new Date(doc.updated_at).toLocaleDateString('fr-FR')}
                                    </td>
                                    <td className="px-6 py-4 text-right text-xs">
                                        <div className="flex items-center justify-end gap-2">
                                            <Link
                                                href={`/documents/${doc.id}`}
                                                className="p-1.5 text-slate-400 hover:text-indigo-600"
                                                title="Ouvrir"
                                            >
                                                <Eye className="w-4 h-4" />
                                            </Link>
                                            <a
                                                href={`/documents/${doc.id}/download`}
                                                className="p-1.5 text-slate-400 hover:text-slate-700"
                                                title="Télécharger"
                                            >
                                                <Download className="w-4 h-4" />
                                            </a>
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                onClick={() => handleUnarchive(doc)}
                                                className="text-indigo-600 hover:text-indigo-800"
                                            >
                                                <RotateCcw className="w-3.5 h-3.5" />
                                                Désarchiver
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
                        title="Aucun document archivé"
                        description="Les documents archivés à des fins de conservation légale ou historique apparaîtront ici."
                    />
                )}
            </div>
        </AuthenticatedLayout>
    );
}
