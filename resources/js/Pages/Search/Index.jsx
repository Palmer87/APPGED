import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Select from '../../Components/Select';
import Table from '../../Components/Table';
import Pagination from '../../Components/Pagination';
import FileIcon from '../../Components/FileIcon';
import Badge from '../../Components/Badge';
import EmptyState from '../../Components/EmptyState';
import { Search, Filter, Folder as FolderIcon, Eye, Download, Star } from 'lucide-react';

export default function SearchIndex({
    results,
    filters = {},
    folders = [],
    categories = [],
    tags = [],
}) {
    const [q, setQ] = useState(filters.q || '');
    const [folderId, setFolderId] = useState(filters.folder_id || '');
    const [categoryId, setCategoryId] = useState(filters.category_id || '');
    const [tagId, setTagId] = useState(filters.tag_id || '');
    const [extension, setExtension] = useState(filters.extension || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleSearch = (e) => {
        if (e) e.preventDefault();
        router.get('/search', {
            q,
            folder_id: folderId,
            category_id: categoryId,
            tag_id: tagId,
            extension,
            status,
        }, { preserveState: true });
    };

    const handleReset = () => {
        setQ('');
        setFolderId('');
        setCategoryId('');
        setTagId('');
        setExtension('');
        setStatus('');
        router.get('/search');
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
            <Head title="Recherche avancée" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Recherche documentaire</h1>
                    <p className="text-xs text-slate-500 mt-1">Recherchez instantanément par texte intégral, métadonnées ou filtres combinés.</p>
                </div>

                {/* Search & Filters Form */}
                <form onSubmit={handleSearch} className="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs space-y-4">
                    <div className="relative">
                        <Search className="w-5 h-5 absolute left-3.5 top-3 text-slate-400" />
                        <input
                            type="search"
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Rechercher par titre, description, métadonnées ou mots-clés..."
                            className="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2">
                        <Select
                            value={folderId}
                            onChange={(e) => setFolderId(e.target.value)}
                            placeholder="Tous les dossiers"
                            options={folders.map(f => ({ value: f.id, label: f.name }))}
                        />

                        <Select
                            value={categoryId}
                            onChange={(e) => setCategoryId(e.target.value)}
                            placeholder="Toutes les catégories"
                            options={categories.map(c => ({ value: c.id, label: c.name }))}
                        />

                        <Select
                            value={tagId}
                            onChange={(e) => setTagId(e.target.value)}
                            placeholder="Tous les tags"
                            options={tags.map(t => ({ value: t.id, label: t.name }))}
                        />

                        <Select
                            value={extension}
                            onChange={(e) => setExtension(e.target.value)}
                            placeholder="Format / Extension"
                            options={[
                                { value: 'pdf', label: 'PDF' },
                                { value: 'docx', label: 'Word (DOCX)' },
                                { value: 'xlsx', label: 'Excel (XLSX)' },
                                { value: 'png', label: 'PNG' },
                                { value: 'jpg', label: 'JPG' },
                            ]}
                        />

                        <Select
                            value={status}
                            onChange={(e) => setStatus(e.target.value)}
                            placeholder="Tous les statuts"
                            options={[
                                { value: 'active', label: 'Actif' },
                                { value: 'draft', label: 'Brouillon' },
                                { value: 'archived', label: 'Archivé' },
                            ]}
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-2 border-t border-slate-100">
                        <Button variant="secondary" size="sm" onClick={handleReset}>
                            Réinitialiser
                        </Button>
                        <Button type="submit" variant="primary" size="sm">
                            <Search className="w-4 h-4" />
                            Lancer la recherche
                        </Button>
                    </div>
                </form>

                {/* Results View */}
                {results ? (
                    results.data && results.data.length > 0 ? (
                        <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                            <div className="p-4 border-b border-slate-100 text-xs font-semibold text-slate-500">
                                {results.total} document(s) trouvé(s) pour votre recherche
                            </div>
                            <Table headers={['Document', 'Dossier', 'Catégories', 'Taille', 'Date', 'Actions']}>
                                {results.data.map((doc) => (
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
                                        <td className="px-6 py-4 text-xs">
                                            <div className="flex flex-wrap gap-1">
                                                {doc.categories?.map(c => (
                                                    <span key={c.id} className="px-2 py-0.5 rounded-full text-[10px] bg-indigo-50 text-indigo-700 font-medium">
                                                        {c.name}
                                                    </span>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 font-mono text-xs text-slate-600">
                                            {formatBytes(doc.size)}
                                        </td>
                                        <td className="px-6 py-4 text-xs text-slate-400">
                                            {new Date(doc.created_at).toLocaleDateString('fr-FR')}
                                        </td>
                                        <td className="px-6 py-4 text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                <Link
                                                    href={`/documents/${doc.id}`}
                                                    className="p-1.5 text-slate-400 hover:text-indigo-600"
                                                    title="Voir"
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
                            <Pagination pagination={results} />
                        </div>
                    ) : (
                        <EmptyState
                            title="Aucun résultat"
                            description="Aucun document ne correspond aux filtres appliqués."
                            actionLabel="Effacer les filtres"
                            onAction={handleReset}
                        />
                    )
                ) : (
                    <div className="p-12 text-center text-slate-400 border border-slate-200 rounded-2xl bg-white">
                        <Search className="w-8 h-8 mx-auto mb-2 text-slate-300" />
                        <p className="text-sm font-medium text-slate-600">Saisissez un mot-clé ou sélectionnez un filtre pour lancer la recherche.</p>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
