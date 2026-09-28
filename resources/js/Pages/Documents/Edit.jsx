import React, { useState, useMemo } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import MetadataForm from '../../Components/MetadataForm';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Textarea from '../../Components/Textarea';
import Select from '../../Components/Select';
import Badge from '../../Components/Badge';
import FileIcon from '../../Components/FileIcon';
import {
    Save,
    Building2,
    FileStack,
    FileText,
    ArrowLeft,
    Upload,
    CheckCircle2,
    AlertCircle,
    X,
    RotateCcw,
    Layers,
    FileCheck2
} from 'lucide-react';

export default function DocumentEdit({
    document: doc,
    departments = [],
    currentDepartmentId,
    currentMetadata = {},
    categories = [],
    tags = [],
}) {
    const [selectedDepartmentId, setSelectedDepartmentId] = useState(currentDepartmentId || '');
    const [selectedDocTypeId, setSelectedDocTypeId] = useState(doc.document_type_id || '');
    const [replaceFileActive, setReplaceFileActive] = useState(false);
    const [dragActive, setDragActive] = useState(false);

    const { data, setData, put, post, processing, errors, progress } = useForm({
        _method: 'put',
        name: doc.name || '',
        description: doc.description || '',
        document_type_id: doc.document_type_id || '',
        metadata: { ...currentMetadata },
        file: null,
        change_notes: '',
        category_ids: doc.categories ? doc.categories.map((c) => c.id) : [],
        tags: doc.tags ? doc.tags.map((t) => t.id) : [],
    });

    // Resolve current department object
    const selectedDepartment = useMemo(() => {
        return departments.find((d) => String(d.id) === String(selectedDepartmentId)) || null;
    }, [departments, selectedDepartmentId]);

    // Available document types for this department
    const availableDocTypes = useMemo(() => {
        if (!selectedDepartment) return [];
        return selectedDepartment.document_types || [];
    }, [selectedDepartment]);

    // Resolve current document type object
    const selectedDocType = useMemo(() => {
        return availableDocTypes.find((t) => String(t.id) === String(selectedDocTypeId)) || null;
    }, [availableDocTypes, selectedDocTypeId]);

    // Metadata definitions for current document type
    const metadataDefinitions = useMemo(() => {
        if (!selectedDocType) return [];
        return selectedDocType.metadata_definitions || [];
    }, [selectedDocType]);

    const handleDepartmentChange = (deptId) => {
        setSelectedDepartmentId(deptId);
        setSelectedDocTypeId('');
        setData((prev) => ({
            ...prev,
            document_type_id: '',
            metadata: {},
        }));
    };

    const handleDocTypeChange = (typeId) => {
        setSelectedDocTypeId(typeId);
        setData('document_type_id', typeId);
    };

    const handleMetadataChange = (key, value) => {
        setData('metadata', {
            ...data.metadata,
            [key]: value,
        });
    };

    const handleFileDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            const file = e.dataTransfer.files[0];
            setData('file', file);
            setReplaceFileActive(true);
        }
    };

    const handleFileSelect = (e) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            setData('file', file);
            setReplaceFileActive(true);
        }
    };

    const cancelFileReplacement = () => {
        setData('file', null);
        setData('change_notes', '');
        setReplaceFileActive(false);
    };

    const formatBytes = (bytes) => {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'Ko', 'Mo', 'Go'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        // Since we may send a file, in Inertia we use post with _method: 'put' or router.post
        post(`/documents/${doc.id}`, {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Modifier — ${doc.name}`} />

            <div className="max-w-5xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-5">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-slate-500 mb-1">
                            <Link href={`/documents/${doc.id}`} className="hover:text-indigo-600 transition flex items-center gap-1">
                                <ArrowLeft className="w-3.5 h-3.5" />
                                Revenir au document
                            </Link>
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Modifier le document
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-500 mt-1">
                            Mettez à jour les métadonnées métier ou remplacez le fichier en créant une nouvelle version sécurisée.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href={`/documents/${doc.id}`}
                            className="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            Annuler
                        </Link>
                        <Button
                            type="button"
                            onClick={handleSubmit}
                            variant="primary"
                            className="text-xs font-bold"
                            disabled={processing}
                        >
                            <Save className="w-4 h-4 mr-1.5" />
                            {processing ? 'Enregistrement...' : 'Enregistrer les modifications'}
                        </Button>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Section 1: Direction & Type documentaire */}
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-5">
                        <div className="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                            <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xs">
                                1
                            </span>
                            <div>
                                <h2 className="text-sm font-bold text-slate-900">Périmètre métier</h2>
                                <p className="text-xs text-slate-400">Direction et Type documentaire rattaché</p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                    <Building2 className="w-3.5 h-3.5 text-indigo-600" />
                                    Direction
                                </label>
                                <Select
                                    id="department_select"
                                    value={selectedDepartmentId}
                                    onChange={(e) => handleDepartmentChange(e.target.value)}
                                    placeholder="Sélectionnez une direction..."
                                    options={departments.map((dept) => ({
                                        value: dept.id,
                                        label: dept.name,
                                    }))}
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                    <FileStack className="w-3.5 h-3.5 text-indigo-600" />
                                    Type documentaire
                                </label>
                                <Select
                                    id="doc_type_select"
                                    value={selectedDocTypeId}
                                    onChange={(e) => handleDocTypeChange(e.target.value)}
                                    placeholder="Sélectionnez un type..."
                                    disabled={!selectedDepartmentId || availableDocTypes.length === 0}
                                    options={availableDocTypes.map((type) => ({
                                        value: type.id,
                                        label: type.name,
                                    }))}
                                />
                            </div>
                        </div>
                    </div>

                    {/* Section 2: Formulaire métier dynamique */}
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-5">
                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                            <div className="flex items-center gap-2.5">
                                <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xs">
                                    2
                                </span>
                                <div>
                                    <h2 className="text-sm font-bold text-slate-900">
                                        Métadonnées métier
                                        {selectedDocType && (
                                            <span className="ml-2 font-normal text-indigo-600 text-xs">
                                                ({selectedDocType.name})
                                            </span>
                                        )}
                                    </h2>
                                    <p className="text-xs text-slate-400">Valeurs associées au document</p>
                                </div>
                            </div>

                            {metadataDefinitions.length > 0 && (
                                <Badge variant="indigo" size="sm">
                                    {metadataDefinitions.length} champ(s)
                                </Badge>
                            )}
                        </div>

                        {metadataDefinitions.length === 0 ? (
                            <div className="p-6 text-center rounded-xl bg-slate-50/70 border border-dashed border-slate-200 text-slate-400 text-xs">
                                Aucune définition de métadonnées n'est rattachée à ce type documentaire.
                            </div>
                        ) : (
                            <MetadataForm
                                definitions={metadataDefinitions}
                                values={data.metadata}
                                onChange={handleMetadataChange}
                                errors={errors}
                                disabled={processing}
                                columns={2}
                            />
                        )}
                    </div>

                    {/* Section 3: Intitulé & Remplacement du fichier */}
                    <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-5">
                        <div className="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                            <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xs">
                                3
                            </span>
                            <div>
                                <h2 className="text-sm font-bold text-slate-900">Fichier & Intitulé</h2>
                                <p className="text-xs text-slate-400">Informations du document et gestion de version</p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Input
                                    id="name"
                                    label="Titre / Nom du document"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    error={errors.name}
                                    required
                                    disabled={processing}
                                />
                            </div>

                            <div>
                                <Input
                                    id="description"
                                    label="Note / Description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    error={errors.description}
                                    disabled={processing}
                                />
                            </div>
                        </div>

                        {/* Current File Box */}
                        <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <FileIcon filename={doc.file_name || doc.name} className="w-10 h-10 shrink-0" />
                                <div>
                                    <div className="flex items-center gap-2">
                                        <p className="text-sm font-semibold text-slate-900 truncate">
                                            {doc.file_name || doc.name}
                                        </p>
                                        <Badge variant="indigo" size="xs">
                                            Version {doc.current_version?.version_number || 1} active
                                        </Badge>
                                    </div>
                                    <p className="text-xs text-slate-400 mt-0.5">
                                        {formatBytes(doc.size)} • Extension : {doc.extension?.toUpperCase()}
                                    </p>
                                </div>
                            </div>

                            {!replaceFileActive && !data.file && (
                                <button
                                    type="button"
                                    onClick={() => setReplaceFileActive(true)}
                                    className="px-3.5 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition shrink-0"
                                >
                                    Remplacer le fichier
                                </button>
                            )}
                        </div>

                        {/* File Replacement Dropzone if active */}
                        {(replaceFileActive || data.file) && (
                            <div className="p-4 rounded-xl border-2 border-dashed border-indigo-300 bg-indigo-50/20 space-y-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2 text-xs font-bold text-indigo-900">
                                        <Upload className="w-4 h-4 text-indigo-600" />
                                        <span>Nouvelle version du fichier</span>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={cancelFileReplacement}
                                        className="text-xs text-slate-500 hover:text-rose-600 flex items-center gap-1"
                                    >
                                        <X className="w-3.5 h-3.5" />
                                        Annuler le remplacement
                                    </button>
                                </div>

                                {!data.file ? (
                                    <div
                                        onDragOver={(e) => {
                                            e.preventDefault();
                                            setDragActive(true);
                                        }}
                                        onDragLeave={() => setDragActive(false)}
                                        onDrop={handleFileDrop}
                                        className={`border-2 border-dashed rounded-xl p-5 text-center transition cursor-pointer ${
                                            dragActive ? 'border-indigo-500 bg-indigo-50' : 'border-slate-300 bg-white hover:border-indigo-400'
                                        }`}
                                    >
                                        <input
                                            type="file"
                                            className="hidden"
                                            id="replacement_file"
                                            onChange={handleFileSelect}
                                        />
                                        <label htmlFor="replacement_file" className="cursor-pointer block">
                                            <Upload className="w-6 h-6 mx-auto text-indigo-500 mb-1" />
                                            <p className="text-xs font-bold text-slate-700">Sélectionnez le nouveau fichier</p>
                                            <p className="text-[11px] text-slate-400">Une nouvelle version sera automatiquement incrémentée</p>
                                        </label>
                                    </div>
                                ) : (
                                    <div className="flex items-center justify-between p-3 rounded-lg border border-indigo-200 bg-white">
                                        <div className="flex items-center gap-2.5">
                                            <FileIcon filename={data.file.name} className="w-7 h-7" />
                                            <div className="text-xs">
                                                <p className="font-semibold text-slate-800">{data.file.name}</p>
                                                <p className="text-slate-400">{formatBytes(data.file.size)}</p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => setData('file', null)}
                                            className="p-1 text-slate-400 hover:text-rose-600"
                                        >
                                            <X className="w-4 h-4" />
                                        </button>
                                    </div>
                                )}

                                <div>
                                    <Input
                                        id="change_notes"
                                        label="Commentaire de révision (optionnel)"
                                        placeholder="Ex: Correction des montants, signature ajoutée..."
                                        value={data.change_notes}
                                        onChange={(e) => setData('change_notes', e.target.value)}
                                        error={errors.change_notes}
                                        disabled={processing}
                                    />
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Action Bar */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link
                            href={`/documents/${doc.id}`}
                            className="px-5 py-2.5 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            Annuler
                        </Link>
                        <Button
                            type="submit"
                            variant="primary"
                            className="px-6 py-2.5 text-sm font-bold shadow-md shadow-indigo-100"
                            disabled={processing}
                        >
                            <Save className="w-4 h-4 mr-2" />
                            {processing ? 'Enregistrement...' : 'Enregistrer les modifications'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
