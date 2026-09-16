import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Table from '../../Components/Table';
import FileIcon from '../../Components/FileIcon';
import EmptyState from '../../Components/EmptyState';
import { Star, Eye, Download, StarOff } from 'lucide-react';

export default function FavoritesIndex({ favorites = [] }) {
    const handleRemoveFavorite = (docId) => {
        router.post(`/documents/${docId}/favorite`, {}, {
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

    return (
        <AuthenticatedLayout>
            <Head title="Mes Favoris" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                        <Star className="w-6 h-6 fill-amber-400 text-amber-500" />
                        Mes favoris
                    </h1>
                    <p className="text-xs text-slate-500 mt-1">Accédez en un clic à vos documents préférés.</p>
                </div>

                {favorites.length > 0 ? (
                    <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                        <Table headers={['Document', 'Dossier', 'Taille', 'Dernière modification', 'Actions']}>
                            {favorites.map((doc) => (
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
                                    <td className="px-6 py-4 font-mono text-xs text-slate-600">
                                        {formatBytes(doc.size)}
                                    </td>
                                    <td className="px-6 py-4 text-xs text-slate-400">
                                        {new Date(doc.updated_at).toLocaleDateString('fr-FR')}
                                    </td>
                                    <td className="px-6 py-4 text-right text-xs">
                                        <div className="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                onClick={() => handleRemoveFavorite(doc.id)}
                                                className="p-1.5 text-amber-500 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                                title="Retirer des favoris"
                                            >
                                                <StarOff className="w-4 h-4" />
                                            </button>
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
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </Table>
                    </div>
                ) : (
                    <EmptyState
                        title="Aucun document en favori"
                        description="Cliquez sur l'étoile sur n'importe quel document pour l'épingler dans vos favoris."
                        actionLabel="Explorer les documents"
                        onAction={() => router.get('/documents')}
                    />
                )}
            </div>
        </AuthenticatedLayout>
    );
}
