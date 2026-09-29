import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import MetadataForm from '../../Components/MetadataForm';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Select from '../../Components/Select';
import Table from '../../Components/Table';
import Pagination from '../../Components/Pagination';
import FileIcon from '../../Components/FileIcon';
import Badge from '../../Components/Badge';
import EmptyState from '../../Components/EmptyState';
import {
    Search,
    Filter,
    Building2,
    FileStack,
    Calendar,
    Eye,
    Edit2,
    Download,
    Star,
    Sparkles,
    RotateCcw,
    SlidersHorizontal,
    Grid,
    List,
    Clock,
    CheckCircle2,
    ChevronDown,
    ChevronUp
} from 'lucide-react';

export default function SearchIndex({
    results,
    filters = {},
    departments = [],
    folders = [],
    categories = [],
    tags = [],
    userContext = {},
}) {
    const [departmentId, setDepartmentId] = useState(filters.department_id || '');
    const [documentTypeId, setDocumentTypeId] = useState(filters.document_type_id || '');
    const [q, setQ] = useState(filters.q || '');
    const [metadata, setMetadata] = useState(filters.metadata || {});
    const [createdFrom, setCreatedFrom] = useState(filters.created_from || '');
    const [createdTo, setCreatedTo] = useState(filters.created_to || '');
    const [extension, setExtension] = useState(filters.extension || '');
    const [status, setStatus] = useState(filters.status || '');
    const [advancedOpen, setAdvancedOpen] = useState(
        Boolean(filters.extension || filters.status || filters.created_from || filters.created_to)
    );
    const [viewMode, setViewMode] = useState('grid'); // 'grid' | 'table'

    // Selected Department object
    const selectedDepartment = useMemo(() => {
        return departments.find((d) => String(d.id) === String(departmentId)) || null;
    }, [departments, departmentId]);

    // Available Document Types
    const availableDocTypes = useMemo(() => {
        if (!selectedDepartment) {
            // Flatten all document types if no department selected
            return departments.flatMap((d) => d.document_types || []);
        }
        return selectedDepartment.document_types || [];
    }, [selectedDepartment, departments]);

    // Selected Document Type object
    const selectedDocType = useMemo(() => {
        return availableDocTypes.find((t) => String(t.id) === String(documentTypeId)) || null;
    }, [availableDocTypes, documentTypeId]);

    // Metadata definitions for the selected document type
    const metadataDefinitions = useMemo(() => {
        if (!selectedDocType) return [];
        return selectedDocType.metadata_definitions || [];
    }, [selectedDocType]);

    const handleDepartmentChange = (val) => {
        setDepartmentId(val);
        setDocumentTypeId('');
        setMetadata({});
    };

    const handleDocTypeChange = (val) => {
        setDocumentTypeId(val);
        setMetadata({});
    };

    const handleMetadataChange = (key, val) => {
        setMetadata((prev) => ({
            ...prev,
            [key]: val,
        }));
    };

    const handleSearch = (e) => {
        if (e) e.preventDefault();

        // Clean empty metadata values
        const cleanMetadata = {};
        Object.keys(metadata).forEach((k) => {
            if (metadata[k] !== '' && metadata[k] !== null && metadata[k] !== undefined) {
                cleanMetadata[k] = metadata[k];
            }
        });

        router.get(
            '/search',
            {
                q: q.trim() || undefined,
                department_id: departmentId || undefined,
                document_type_id: documentTypeId || undefined,
                metadata: Object.keys(cleanMetadata).length > 0 ? cleanMetadata : undefined,
                created_from: createdFrom || undefined,
                created_to: createdTo || undefined,
                extension: extension || undefined,
                status: status || undefined,
            },
            { preserveState: true }
        );
    };

    const handleReset = () => {
        setDepartmentId('');
        setDocumentTypeId('');
        setQ('');
        setMetadata({});
        setCreatedFrom('');
        setCreatedTo('');
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
            <Head title="Recherche de documents" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                            <Sparkles className="w-3.5 h-3.5" />
                            Moteur métier
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Rechercher un document
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-500 mt-1">
                            Recherchez par Direction, Type documentaire, métadonnées ou texte intégral indexé (OCR).
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link href="/documents/create">
                            <Button variant="primary" size="sm" className="font-bold">
                                Importer un document
                            </Button>
                        </Link>
                    </div>
                </div>

                {/* Quick contextual filter if user has space */}
                {userContext.primary_service_name && (
                    <div className="flex items-center justify-between p-3.5 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200/70 rounded-2xl text-xs text-blue-900 shadow-2xs">
                        <div className="flex items-center gap-2.5">
                            <span className="p-1.5 rounded-lg bg-blue-600 text-white">
                                <Building2 className="w-3.5 h-3.5" />
                            </span>
                            <div>
                                <span className="font-bold">Votre espace de travail :</span>{' '}
                                <span className="font-semibold text-blue-700">
                                    {userContext.primary_direction_name} → {userContext.primary_service_name}
                                </span>
                            </div>
                        </div>
                        {userContext.primary_direction_id && (
                            <button
                                type="button"
                                onClick={() => handleDepartmentChange(String(userContext.primary_direction_id))}
                                className="px-3 py-1 rounded-xl bg-white border border-blue-200 text-blue-700 font-bold hover:bg-blue-100 transition shadow-2xs"
                            >
                                Filtrer sur mon espace
                            </button>
                        )}
                    </div>
                )}

                {/* Search Form Card */}
                <form onSubmit={handleSearch} className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-5">
                    {/* Primary Row: Direction & Type Documentaire */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                <Building2 className="w-3.5 h-3.5 text-indigo-600" />
                                Direction
                            </label>
                            <Select
                                id="search_department"
                                value={departmentId}
                                onChange={(e) => handleDepartmentChange(e.target.value)}
                                placeholder="Toutes les directions"
                                options={departments.map((d) => ({ value: d.id, label: d.name }))}
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                <FileStack className="w-3.5 h-3.5 text-indigo-600" />
                                Type documentaire
                            </label>
                            <Select
                                id="search_doc_type"
                                value={documentTypeId}
                                onChange={(e) => handleDocTypeChange(e.target.value)}
                                placeholder="Tous les types"
                                options={availableDocTypes.map((t) => ({ value: t.id, label: t.name }))}
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                <Search className="w-3.5 h-3.5 text-indigo-600" />
                                Recherche textuelle / OCR
                            </label>
                            <div className="relative">
                                <input
                                    type="search"
                                    value={q}
                                    onChange={(e) => setQ(e.target.value)}
                                    placeholder="Titre, contenu OCR, description..."
                                    className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Dynamic Metadata Search Section */}
                    {selectedDocType && metadataDefinitions.length > 0 && (
                        <div className="rounded-xl border border-indigo-100 bg-indigo-50/30 p-5 space-y-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <FileStack className="w-4 h-4 text-indigo-600" />
                                    <h3 className="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                        Critères spécifiques : {selectedDocType.name}
                                    </h3>
                                </div>
                                <span className="text-[11px] text-slate-400">
                                    Filtrage combiné sur les métadonnées
                                </span>
                            </div>

                            <MetadataForm
                                definitions={metadataDefinitions}
                                values={metadata}
                                onChange={handleMetadataChange}
                                isSearchMode={true}
                                columns={3}
                            />
                        </div>
                    )}

                    {/* Advanced Filters Expandable Toggle */}
                    <div className="pt-1">
                        <button
                            type="button"
                            onClick={() => setAdvancedOpen(!advancedOpen)}
                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-indigo-600 transition"
                        >
                            <SlidersHorizontal className="w-3.5 h-3.5" />
                            <span>Filtres avancés (Dates, Format, Statut)</span>
                            {advancedOpen ? <ChevronUp className="w-3.5 h-3.5" /> : <ChevronDown className="w-3.5 h-3.5" />}
                        </button>

                        {advancedOpen && (
                            <div className="mt-4 pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-slate-600 mb-1">Date début (Création)</label>
                                    <Input
                                        type="date"
                                        value={createdFrom}
                                        onChange={(e) => setCreatedFrom(e.target.value)}
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-600 mb-1">Date fin (Création)</label>
                                    <Input
                                        type="date"
                                        value={createdTo}
                                        onChange={(e) => setCreatedTo(e.target.value)}
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-600 mb-1">Format de fichier</label>
                                    <Select
                                        value={extension}
                                        onChange={(e) => setExtension(e.target.value)}
                                        placeholder="Tous les formats"
                                        options={[
                                            { value: 'pdf', label: 'PDF' },
                                            { value: 'docx', label: 'Word (DOCX)' },
                                            { value: 'xlsx', label: 'Excel (XLSX)' },
                                            { value: 'png', label: 'Image (PNG)' },
                                            { value: 'jpg', label: 'Image (JPG)' },
                                        ]}
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-slate-600 mb-1">Statut</label>
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
                            </div>
                        )}
                    </div>

                    {/* Actions Row */}
                    <div className="flex items-center justify-between pt-3 border-t border-slate-100">
                        <Button type="button" variant="secondary" size="sm" onClick={handleReset}>
                            <RotateCcw className="w-3.5 h-3.5 mr-1" />
                            Réinitialiser
                        </Button>

                        <Button type="submit" variant="primary" size="sm" className="px-6 font-bold shadow-xs">
                            <Search className="w-4 h-4 mr-1.5" />
                            RECHERCHER
                        </Button>
                    </div>
                </form>

                {/* Results Section */}
                {results && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <div className="text-xs font-semibold text-slate-600">
                                {results.total} document(s) trouvé(s)
                            </div>

                            {/* View Switcher */}
                            <div className="flex items-center gap-1 bg-white border border-slate-200 rounded-lg p-0.5">
                                <button
                                    type="button"
                                    onClick={() => setViewMode('grid')}
                                    className={`p-1.5 rounded-md transition ${viewMode === 'grid' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-400 hover:text-slate-700'}`}
                                    title="Vue Cartes"
                                >
                                    <Grid className="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setViewMode('table')}
                                    className={`p-1.5 rounded-md transition ${viewMode === 'table' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-400 hover:text-slate-700'}`}
                                    title="Vue Tableau"
                                >
                                    <List className="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        {results.data.length === 0 ? (
                            <EmptyState
                                icon={Search}
                                title="Aucun document trouvé"
                                description="Aucun document ne correspond à vos critères de recherche. Essayez d'élargir votre sélection."
                            />
                        ) : viewMode === 'grid' ? (
                            /* Professional Business Cards View */
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                {results.data.map((doc) => {
                                    const deptName = doc.document_type?.parent?.name
                                        || (doc.folder?.folder_type === 'department' ? doc.folder?.name : (doc.folder?.parent?.name || null));
                                    const typeName = doc.document_type?.name
                                        || (doc.folder?.folder_type === 'document_type' ? doc.folder?.name : null);

                                    return (
                                        <div
                                            key={doc.id}
                                            className="bg-white rounded-2xl border border-slate-200 p-5 shadow-2xs hover:shadow-md transition flex flex-col justify-between group"
                                        >
                                            <div className="space-y-3">
                                                {/* Card Header: Icon + Title */}
                                                <div className="flex items-start gap-3">
                                                    <div className="p-2.5 rounded-xl bg-slate-50 border border-slate-100 shrink-0 group-hover:scale-105 transition">
                                                        <FileIcon extension={doc.extension} className="w-8 h-8" />
                                                    </div>
                                                    <div className="min-w-0 flex-1">
                                                        <Link
                                                            href={`/documents/${doc.id}`}
                                                            className="text-sm font-bold text-slate-900 hover:text-indigo-600 transition truncate block"
                                                        >
                                                            {doc.name}
                                                        </Link>
                                                        <div className="flex items-center gap-1.5 mt-1 flex-wrap">
                                                            {deptName && (
                                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 text-blue-700">
                                                                    <Building2 className="w-3 h-3" />
                                                                    {deptName}
                                                                </span>
                                                            )}
                                                            {typeName && (
                                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-purple-50 text-purple-700">
                                                                    <FileStack className="w-3 h-3" />
                                                                    {typeName}
                                                                </span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Key Business Metadata Box */}
                                                {doc.metadata_values && doc.metadata_values.length > 0 && (
                                                    <div className="rounded-xl bg-slate-50 p-3 border border-slate-100 space-y-1 text-xs">
                                                        {doc.metadata_values.slice(0, 4).map((mv) => (
                                                            <div key={mv.id} className="flex items-center justify-between text-[11px] gap-2">
                                                                <span className="text-slate-500 font-medium truncate">
                                                                    {mv.definition?.name || mv.metadata_definition_id} :
                                                                </span>
                                                                <span className="text-slate-900 font-bold truncate text-right">
                                                                    {mv.value_string || mv.value_text || mv.value_integer || mv.value_decimal || mv.value_date || (mv.value_boolean !== null ? (mv.value_boolean ? 'Oui' : 'Non') : '—')}
                                                                </span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}

                                                {/* Document Info line */}
                                                <div className="flex items-center gap-2 text-[11px] text-slate-400 pt-1">
                                                    <span>{formatBytes(doc.size)}</span>
                                                    <span>•</span>
                                                    <span>Version {doc.current_version?.version_number || 1}</span>
                                                    <span>•</span>
                                                    <span>{new Date(doc.created_at).toLocaleDateString('fr-FR')}</span>
                                                </div>
                                            </div>

                                            {/* Action Buttons Bar */}
                                            <div className="flex items-center justify-between pt-4 mt-4 border-t border-slate-100">
                                                <div className="flex items-center gap-1.5">
                                                    <Link href={`/documents/${doc.id}`}>
                                                        <Button variant="secondary" size="xs">
                                                            <Eye className="w-3.5 h-3.5 mr-1" />
                                                            Voir
                                                        </Button>
                                                    </Link>
                                                    <Link href={`/documents/${doc.id}/edit`}>
                                                        <Button variant="secondary" size="xs">
                                                            <Edit2 className="w-3.5 h-3.5 mr-1" />
                                                            Modifier
                                                        </Button>
                                                    </Link>
                                                </div>

                                                <a href={`/documents/${doc.id}/download`}>
                                                    <Button variant="secondary" size="xs" title="Télécharger">
                                                        <Download className="w-3.5 h-3.5" />
                                                    </Button>
                                                </a>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        ) : (
                            /* Table View */
                            <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                                <Table headers={['Document', 'Direction & Type', 'Métadonnées clés', 'Taille', 'Date', 'Actions']}>
                                    {results.data.map((doc) => {
                                        const deptName = doc.document_type?.parent?.name
                                            || (doc.folder?.folder_type === 'department' ? doc.folder?.name : (doc.folder?.parent?.name || null));
                                        const typeName = doc.document_type?.name
                                            || (doc.folder?.folder_type === 'document_type' ? doc.folder?.name : null);

                                        return (
                                            <tr key={doc.id} className="hover:bg-slate-50 transition">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <FileIcon extension={doc.extension} className="w-7 h-7 shrink-0" />
                                                        <div>
                                                            <Link href={`/documents/${doc.id}`} className="text-sm font-bold text-slate-900 hover:text-indigo-600 block truncate max-w-xs">
                                                                {doc.name}
                                                            </Link>
                                                            <span className="text-[11px] text-slate-400">V{doc.current_version?.version_number || 1}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-xs">
                                                    <div className="space-y-0.5">
                                                        {deptName && <div className="font-semibold text-slate-800">{deptName}</div>}
                                                        {typeName && <div className="text-slate-400">{typeName}</div>}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-xs">
                                                    {doc.metadata_values && doc.metadata_values.length > 0 ? (
                                                        <div className="text-[11px] space-y-0.5">
                                                            {doc.metadata_values.slice(0, 2).map((mv) => (
                                                                <div key={mv.id} className="truncate max-w-xs">
                                                                    <span className="text-slate-400">{mv.definition?.name} : </span>
                                                                    <span className="font-bold text-slate-800">
                                                                        {mv.value !== undefined && mv.value !== null
                                                                            ? (typeof mv.value === 'boolean' ? (mv.value ? 'Oui' : 'Non') : String(mv.value))
                                                                            : (mv.value_string || mv.value_text || mv.value_integer || mv.value_decimal || mv.value_date || '—')}
                                                                    </span>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <span className="text-slate-400">—</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-xs font-mono text-slate-600">
                                                    {formatBytes(doc.size)}
                                                </td>
                                                <td className="px-6 py-4 text-xs text-slate-500">
                                                    {new Date(doc.created_at).toLocaleDateString('fr-FR')}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link href={`/documents/${doc.id}`} className="p-1 text-slate-400 hover:text-indigo-600" title="Voir">
                                                            <Eye className="w-4 h-4" />
                                                        </Link>
                                                        <Link href={`/documents/${doc.id}/edit`} className="p-1 text-slate-400 hover:text-indigo-600" title="Modifier">
                                                            <Edit2 className="w-4 h-4" />
                                                        </Link>
                                                        <a href={`/documents/${doc.id}/download`} className="p-1 text-slate-400 hover:text-indigo-600" title="Télécharger">
                                                            <Download className="w-4 h-4" />
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </Table>
                            </div>
                        )}

                        <Pagination links={results.links} />
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
