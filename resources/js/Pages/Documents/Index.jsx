import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Select from '../../Components/Select';
import Textarea from '../../Components/Textarea';
import Modal from '../../Components/Modal';
import Badge from '../../Components/Badge';
import Table from '../../Components/Table';
import Pagination from '../../Components/Pagination';
import FileIcon from '../../Components/FileIcon';
import EmptyState from '../../Components/EmptyState';
import ConfirmDialog from '../../Components/ConfirmDialog';
import {
    Upload,
    Filter,
    Folder as FolderIcon,
    Star,
    Eye,
    Download,
    Trash2,
    Archive,
    Grid,
    List,
    Search,
    ChevronRight,
    FileText,
    Plus
} from 'lucide-react';

export default function DocumentsIndex({
    documents,
    filters = {},
    currentFolder,
    folders = [],
    categories = [],
    tags = [],
}) {
    const [viewMode, setViewMode] = useState('list'); // 'list' | 'grid'
    const [uploadModalOpen, setUploadModalOpen] = useState(false);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [selectedDocument, setSelectedDocument] = useState(null);
    const [dragActive, setDragActive] = useState(false);

    // Filter state
    const [search, setSearch] = useState(filters.search || '');
    const [folderId, setFolderId] = useState(filters.folder_id || '');
    const [status, setStatus] = useState(filters.status || '');
    const [extension, setExtension] = useState(filters.extension || '');

    // Upload Form
    const { data, setData, post, processing, errors, reset, progress } = useForm({
        file: null,
        name: '',
        description: '',
        folder_id: currentFolder?.id || '',
        category_ids: [],
        tags: [],
    });

    const handleFilterChange = (newFilters) => {
        router.get('/documents', {
            ...filters,
            search,
            folder_id: folderId,
            status,
            extension,
            ...newFilters,
        }, { preserveState: true, preserveScroll: true });
    };

    const handleFileDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            const file = e.dataTransfer.files[0];
            setData('file', file);
            if (!data.name) {
                setData('name', file.name);
            }
        }
    };

    const handleFileSelect = (e) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            setData('file', file);
            if (!data.name) {
                setData('name', file.name);
            }
        }
    };

    const handleUploadSubmit = (e) => {
        e.preventDefault();
        post('/documents', {
            onSuccess: () => {
                setUploadModalOpen(false);
                reset();
            },
        });
    };

    const handleFavoriteToggle = (docId) => {
        router.post(`/documents/${docId}/favorite`, {}, {
            preserveScroll: true,
        });
    };

    const handleTrash = () => {
        if (!selectedDocument) return;
        router.delete(`/documents/${selectedDocument.id}`, {
            preserveScroll: true,
            onSuccess: () => setDeleteModalOpen(false),
        });
    };

    const formatBytes = (bytes) => {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'Ko', 'Mo', 'Go'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const statusBadge = (st) => {
        switch (st) {
            case 'draft': return <Badge variant="warning">Brouillon</Badge>;
            case 'active': return <Badge variant="success">Actif</Badge>;
            case 'archived': return <Badge variant="default">Archivé</Badge>;
            default: return <Badge variant="default">{st}</Badge>;
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title="Tous les documents" />

            <div className="space-y-6">
                {/* Top Action Bar */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
                            {currentFolder ? `Dossier : ${currentFolder.name}` : 'Tous les documents'}
                        </h1>
                        <p className="text-xs text-slate-500 mt-1">
                            Gérez, organisez et collaborez sur vos fichiers d'entreprise.
                        </p>
                    </div>

                    <div className="flex items-center gap-2.5">
                        <Button
                            variant="primary"
                            size="md"
                            onClick={() => setUploadModalOpen(true)}
                        >
                            <Upload className="w-4 h-4" />
                            Téléverser un document
                        </Button>
                    </div>
                </div>

                {/* Filters & View Toggles */}
                <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs space-y-3">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                        {/* Search input */}
                        <div className="relative lg:col-span-2">
                            <Search className="w-4 h-4 absolute left-3 top-2.5 text-slate-400" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && handleFilterChange({ search })}
                                placeholder="Recherche par mot-clé..."
                                className="w-full pl-9 pr-3 py-1.5 text-sm rounded-lg border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            />
                        </div>

                        {/* Folder filter */}
                        <Select
                            value={folderId}
                            onChange={(e) => {
                                setFolderId(e.target.value);
                                handleFilterChange({ folder_id: e.target.value });
                            }}
                            placeholder="Tous les dossiers"
                            options={folders.map(f => ({ value: f.id, label: f.name }))}
                        />

                        {/* Status filter */}
                        <Select
                            value={status}
                            onChange={(e) => {
                                setStatus(e.target.value);
                                handleFilterChange({ status: e.target.value });
                            }}
                            placeholder="Tous les statuts"
                            options={[
                                { value: 'active', label: 'Actif' },
                                { value: 'draft', label: 'Brouillon' },
                            ]}
                        />

                        {/* Extension filter */}
                        <Select
                            value={extension}
                            onChange={(e) => {
                                setExtension(e.target.value);
                                handleFilterChange({ extension: e.target.value });
                            }}
                            placeholder="Toutes extensions"
                            options={[
                                { value: 'pdf', label: 'PDF' },
                                { value: 'docx', label: 'Word (DOCX)' },
                                { value: 'xlsx', label: 'Excel (XLSX)' },
                                { value: 'png', label: 'Images (PNG/JPG)' },
                            ]}
                        />
                    </div>

                    <div className="flex items-center justify-between pt-2 border-t border-slate-100 text-xs text-slate-500">
                        <span>{documents?.total || 0} document(s) trouvé(s)</span>
                        <div className="flex items-center gap-1">
                            <button
                                type="button"
                                onClick={() => setViewMode('list')}
                                className={`p-1.5 rounded-md transition ${viewMode === 'list' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-400 hover:text-slate-600'}`}
                                title="Vue liste"
                            >
                                <List className="w-4 h-4" />
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode('grid')}
                                className={`p-1.5 rounded-md transition ${viewMode === 'grid' ? 'bg-indigo-50 text-indigo-600' : 'text-slate-400 hover:text-slate-600'}`}
                                title="Vue grille"
                            >
                                <Grid className="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                {/* Document List / Grid */}
                {documents?.data && documents.data.length > 0 ? (
                    viewMode === 'list' ? (
                        <div className="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                            <Table
                                headers={['Nom', 'Dossier', 'Taille', 'Statut', 'Créateur', 'Date', 'Actions']}
                            >
                                {documents.data.map((doc) => (
                                    <tr key={doc.id} className="hover:bg-slate-50/70 transition">
                                        <td className="px-6 py-4">
                                            <div className="flex items-center gap-3">
                                                <FileIcon extension={doc.extension} mimeType={doc.mime_type} className="w-6 h-6 shrink-0" />
                                                <div className="min-w-0">
                                                    <Link
                                                        href={`/documents/${doc.id}`}
                                                        className="font-semibold text-slate-900 hover:text-indigo-600 truncate block text-sm"
                                                    >
                                                        {doc.name}
                                                    </Link>
                                                    <div className="flex items-center gap-1.5 mt-0.5">
                                                        <span className="text-[11px] uppercase font-bold text-slate-400">
                                                            {doc.extension}
                                                        </span>
                                                        {doc.latest_version && (
                                                            <span className="text-[11px] font-medium text-slate-400">
                                                                • V{doc.latest_version.version_number}
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                            {doc.folder ? (
                                                <span className="inline-flex items-center gap-1 font-medium text-slate-700">
                                                    <FolderIcon className="w-3.5 h-3.5 text-amber-500" />
                                                    {doc.folder.name}
                                                </span>
                                            ) : (
                                                <span className="text-slate-400">Racine</span>
                                            )}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-xs text-slate-600 font-mono">
                                            {formatBytes(doc.size)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap">
                                            {statusBadge(doc.status)}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                            {doc.creator?.name || 'Inconnu'}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-xs text-slate-400">
                                            {new Date(doc.created_at).toLocaleDateString('fr-FR')}
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-right text-xs">
                                            <div className="flex items-center justify-end gap-2">
                                                <button
                                                    type="button"
                                                    onClick={() => handleFavoriteToggle(doc.id)}
                                                    className="p-1 text-slate-400 hover:text-amber-500 transition"
                                                    title="Favori"
                                                >
                                                    <Star className="w-4 h-4" />
                                                </button>
                                                <Link
                                                    href={`/documents/${doc.id}`}
                                                    className="p-1 text-slate-400 hover:text-indigo-600 transition"
                                                    title="Ouvrir le document"
                                                >
                                                    <Eye className="w-4 h-4" />
                                                </Link>
                                                <a
                                                    href={`/documents/${doc.id}/download`}
                                                    className="p-1 text-slate-400 hover:text-slate-700 transition"
                                                    title="Télécharger"
                                                >
                                                    <Download className="w-4 h-4" />
                                                </a>
                                                <button
                                                    type="button"
                                                    onClick={() => {
                                                        setSelectedDocument(doc);
                                                        setDeleteModalOpen(true);
                                                    }}
                                                    className="p-1 text-slate-400 hover:text-rose-600 transition"
                                                    title="Mettre à la corbeille"
                                                >
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </Table>
                            <Pagination pagination={documents} />
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            {documents.data.map((doc) => (
                                <div
                                    key={doc.id}
                                    className="group relative rounded-xl border border-slate-200 bg-white p-4 shadow-2xs hover:shadow-md hover:border-indigo-200 transition flex flex-col justify-between"
                                >
                                    <div>
                                        <div className="flex items-start justify-between gap-2 mb-3">
                                            <div className="p-2.5 rounded-xl bg-slate-50 border border-slate-100 group-hover:scale-105 transition">
                                                <FileIcon extension={doc.extension} mimeType={doc.mime_type} className="w-8 h-8" />
                                            </div>
                                            <div className="flex items-center gap-1">
                                                <button
                                                    type="button"
                                                    onClick={() => handleFavoriteToggle(doc.id)}
                                                    className="p-1 text-slate-300 hover:text-amber-500 transition"
                                                >
                                                    <Star className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </div>

                                        <Link
                                            href={`/documents/${doc.id}`}
                                            className="font-semibold text-sm text-slate-900 group-hover:text-indigo-600 line-clamp-2"
                                        >
                                            {doc.name}
                                        </Link>

                                        <div className="mt-2 flex items-center gap-2 text-[11px] text-slate-400">
                                            <span className="font-mono">{formatBytes(doc.size)}</span>
                                            <span>•</span>
                                            <span>{new Date(doc.created_at).toLocaleDateString('fr-FR')}</span>
                                        </div>
                                    </div>

                                    <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                        {statusBadge(doc.status)}
                                        <div className="flex items-center gap-2">
                                            <a
                                                href={`/documents/${doc.id}/download`}
                                                className="p-1 text-slate-400 hover:text-slate-700"
                                                title="Télécharger"
                                            >
                                                <Download className="w-3.5 h-3.5" />
                                            </a>
                                            <Link
                                                href={`/documents/${doc.id}`}
                                                className="p-1 text-slate-400 hover:text-indigo-600"
                                                title="Voir"
                                            >
                                                <ChevronRight className="w-4 h-4" />
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )
                ) : (
                    <EmptyState
                        title="Aucun document trouvé"
                        description="Téléversez votre premier fichier ou modifiez vos critères de recherche."
                        actionLabel="Téléverser un document"
                        onAction={() => setUploadModalOpen(true)}
                    />
                )}
            </div>

            {/* Upload Modal */}
            <Modal
                isOpen={uploadModalOpen}
                onClose={() => {
                    setUploadModalOpen(false);
                    reset();
                }}
                title="Téléverser un nouveau document"
                maxWidth="max-w-xl"
            >
                <form onSubmit={handleUploadSubmit} className="space-y-4">
                    {/* Drag & Drop Area */}
                    <div
                        onDragEnter={() => setDragActive(true)}
                        onDragLeave={() => setDragActive(false)}
                        onDragOver={(e) => e.preventDefault()}
                        onDrop={handleFileDrop}
                        className={`border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition ${
                            dragActive ? 'border-indigo-500 bg-indigo-50/50' : 'border-slate-300 hover:border-slate-400 bg-slate-50/30'
                        }`}
                        onClick={() => document.getElementById('file-upload-input').click()}
                    >
                        <input
                            id="file-upload-input"
                            type="file"
                            className="hidden"
                            onChange={handleFileSelect}
                        />
                        <div className="w-12 h-12 mx-auto rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 mb-2">
                            <Upload className="w-6 h-6" />
                        </div>
                        {data.file ? (
                            <div>
                                <p className="text-sm font-semibold text-indigo-600 truncate max-w-sm mx-auto">{data.file.name}</p>
                                <p className="text-xs text-slate-400 mt-0.5">{formatBytes(data.file.size)}</p>
                            </div>
                        ) : (
                            <div>
                                <p className="text-sm font-medium text-slate-700">Glissez-déposez votre fichier ici, ou <span className="text-indigo-600 underline">parcourez</span></p>
                                <p className="text-xs text-slate-400 mt-1">PDF, Word, Excel, Images jusqu'à 50 Mo</p>
                            </div>
                        )}
                    </div>
                    {errors.file && <p className="text-xs text-rose-600 font-medium">{errors.file}</p>}

                    {/* Progress Bar */}
                    {progress && (
                        <div className="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div
                                className="bg-indigo-600 h-2 transition-all duration-200"
                                style={{ width: `${progress.percentage}%` }}
                            />
                        </div>
                    )}

                    <Input
                        id="doc-name"
                        label="Nom du document"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="Ex: Contrat de prestation 2026"
                        error={errors.name}
                    />

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <Select
                            id="doc-folder"
                            label="Dossier de destination"
                            value={data.folder_id}
                            onChange={(e) => setData('folder_id', e.target.value)}
                            placeholder="Racine (aucun dossier)"
                            options={folders.map(f => ({ value: f.id, label: f.name }))}
                            error={errors.folder_id}
                        />

                        <Select
                            id="doc-category"
                            label="Catégorie principale"
                            value={data.category_ids[0] || ''}
                            onChange={(e) => setData('category_ids', e.target.value ? [e.target.value] : [])}
                            placeholder="Aucune catégorie"
                            options={categories.map(c => ({ value: c.id, label: c.name }))}
                            error={errors.category_ids}
                        />
                    </div>

                    <Textarea
                        id="doc-desc"
                        label="Description (optionnelle)"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        placeholder="Brève description du document..."
                        rows={2}
                        error={errors.description}
                    />

                    <div className="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <Button
                            variant="secondary"
                            onClick={() => {
                                setUploadModalOpen(false);
                                reset();
                            }}
                            disabled={processing}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            variant="primary"
                            loading={processing}
                            disabled={!data.file}
                        >
                            Téléverser le document
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Delete Confirmation */}
            <ConfirmDialog
                isOpen={deleteModalOpen}
                onClose={() => setDeleteModalOpen(false)}
                onConfirm={handleTrash}
                title="Déplacer vers la corbeille"
                message={`Êtes-vous sûr de vouloir déplacer "${selectedDocument?.name}" vers la corbeille ? Vous pourrez le restaurer ultérieurement.`}
                confirmLabel="Mettre à la corbeille"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
