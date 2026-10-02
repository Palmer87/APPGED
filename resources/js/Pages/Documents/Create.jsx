import React, { useState, useMemo, useEffect } from 'react';
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
    Upload,
    Building2,
    Network,
    FileStack,
    FileText,
    ArrowRight,
    CheckCircle2,
    AlertCircle,
    X,
    FileUp,
    Check,
    Layers,
    Clock,
    Sparkles
} from 'lucide-react';

export default function DocumentCreate({
    departments = [],
    directions = [],
    services = [],
    documentTypes = [],
    categories = [],
    tags = [],
    preselected = {},
    userContext = {},
}) {
    const availableDirections = useMemo(() => {
        if (directions && directions.length > 0) return directions;
        return departments;
    }, [directions, departments]);

    const defaultDeptId = preselected.direction_id
        || preselected.department_id
        || userContext.primary_direction_id
        || (availableDirections.length === 1 ? String(availableDirections[0].id) : '')
        || '';

    const defaultServiceId = preselected.service_id
        || userContext.primary_service_id
        || '';

    const [selectedDepartmentId, setSelectedDepartmentId] = useState(defaultDeptId);
    const [selectedServiceId, setSelectedServiceId] = useState(defaultServiceId);
    const [selectedDocTypeId, setSelectedDocTypeId] = useState(preselected.document_type_id || '');
    const [dragActive, setDragActive] = useState(false);

    const { data, setData, post, processing, errors, reset, progress } = useForm({
        file: null,
        name: '',
        description: '',
        direction_id: userContext.primary_direction_id || defaultDeptId || '',
        service_id: defaultServiceId,
        department_id: defaultDeptId,
        document_type_id: preselected.document_type_id || '',
        metadata: {},
        category_ids: [],
        tags: [],
    });

    // Resolve currently selected direction object
    const selectedDepartment = useMemo(() => {
        return availableDirections.find(
            (d) => String(d.id) === String(selectedDepartmentId) || String(d.folder_id) === String(selectedDepartmentId)
        ) || null;
    }, [availableDirections, selectedDepartmentId]);

    // Available services filtered by selected department/direction
    const availableServices = useMemo(() => {
        if (!selectedDepartmentId) return [];
        return services.filter((s) => {
            return String(s.direction_id) === String(selectedDepartment?.id) ||
                String(s.direction_id) === String(selectedDepartmentId) ||
                String(s.department_id) === String(selectedDepartment?.folder_id) ||
                String(s.department_id) === String(selectedDepartmentId);
        });
    }, [services, selectedDepartment, selectedDepartmentId]);

    // Resolve currently selected service object
    const selectedService = useMemo(() => {
        return services.find((s) => String(s.id) === String(selectedServiceId)) || null;
    }, [services, selectedServiceId]);

    // Available document types filtered by selected service or direction
    const availableDocTypes = useMemo(() => {
        if (!selectedDepartment) return [];

        if (documentTypes && documentTypes.length > 0) {
            return documentTypes.filter((t) => {
                const dirId = selectedDepartment.id;
                const dirFolderId = selectedDepartment.folder_id;
                const svcId = selectedService?.id;
                const svcFolderId = selectedService?.folder_id;

                if (svcId) {
                    return (
                        String(t.service_id) === String(svcId) ||
                        String(t.parent_id) === String(svcFolderId) ||
                        (String(t.direction_id) === String(dirId) && !t.service_id) ||
                        (String(t.parent_id) === String(dirFolderId) && !t.service_id)
                    );
                }

                const matchesDir = String(t.direction_id) === String(dirId) ||
                    String(t.parent_id) === String(dirFolderId);
                const matchesAnyChildService = availableServices.some(
                    (s) => String(s.id) === String(t.service_id) || String(s.folder_id) === String(t.parent_id)
                );

                return matchesDir || matchesAnyChildService;
            });
        }

        return selectedDepartment.document_types || [];
    }, [selectedDepartment, selectedService, availableServices, documentTypes]);

    // Resolve currently selected document type object
    const selectedDocType = useMemo(() => {
        return availableDocTypes.find((t) => String(t.id) === String(selectedDocTypeId)) || null;
    }, [availableDocTypes, selectedDocTypeId]);

    // Metadata definitions associated with the selected document type
    const metadataDefinitions = useMemo(() => {
        if (!selectedDocType) return [];
        return selectedDocType.metadata_definitions || [];
    }, [selectedDocType]);

    // Handle department change -> reset service, doc type and metadata
    const handleDepartmentChange = (deptId) => {
        setSelectedDepartmentId(deptId);
        const dirObj = availableDirections.find(
            (d) => String(d.id) === String(deptId) || String(d.folder_id) === String(deptId)
        );

        const matchingServices = services.filter((s) => {
            return String(s.direction_id) === String(dirObj?.id) ||
                String(s.direction_id) === String(deptId) ||
                String(s.department_id) === String(dirObj?.folder_id) ||
                String(s.department_id) === String(deptId);
        });

        const primaryMatch = matchingServices.find((s) => String(s.id) === String(userContext.primary_service_id));
        const autoServiceId = primaryMatch ? String(primaryMatch.id) : '';

        setSelectedServiceId(autoServiceId);
        setSelectedDocTypeId('');
        setData((prev) => ({
            ...prev,
            department_id: dirObj?.folder_id || deptId,
            direction_id: dirObj?.id || deptId,
            service_id: autoServiceId,
            document_type_id: '',
            metadata: {},
        }));
    };

    // Handle service change -> reset doc type and metadata
    const handleServiceChange = (serviceId) => {
        setSelectedServiceId(serviceId);
        setSelectedDocTypeId('');
        setData((prev) => ({
            ...prev,
            service_id: serviceId,
            document_type_id: '',
            metadata: {},
        }));
    };

    // Handle doc type change -> reset metadata with defaults
    const handleDocTypeChange = (typeId) => {
        setSelectedDocTypeId(typeId);
        setData((prev) => ({
            ...prev,
            document_type_id: typeId,
            metadata: {},
        }));
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
            if (!data.name) {
                setData('name', file.name.replace(/\.[^/.]+$/, ''));
            }
        }
    };

    const handleFileSelect = (e) => {
        if (e.target.files && e.target.files[0]) {
            const file = e.target.files[0];
            setData('file', file);
            if (!data.name) {
                setData('name', file.name.replace(/\.[^/.]+$/, ''));
            }
        }
    };

    const removeSelectedFile = () => {
        setData('file', null);
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
        post('/documents', {
            preserveScroll: true,
        });
    };

    // Count filled metadata fields for the summary
    const filledMetadataCount = Object.keys(data.metadata || {}).filter(
        (k) => data.metadata[k] !== '' && data.metadata[k] !== null && data.metadata[k] !== undefined
    ).length;

    return (
        <AuthenticatedLayout>
            <Head title="Importer un document" />

            <div className="max-w-6xl mx-auto space-y-6">
                {/* Header Section */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/80 pb-5">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                            <Sparkles className="w-3.5 h-3.5" />
                            Gestion documentaire métier
                        </div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Importer un document
                        </h1>
                        <p className="text-xs sm:text-sm text-slate-500 mt-1">
                            Sélectionnez la Direction et le Type documentaire pour renseigner le formulaire métier correspondant.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <Link
                            href="/documents"
                            className="px-3.5 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            Retour aux documents
                        </Link>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left & Middle Column: Interactive Business Form */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Contexte Utilisateur (Section 14) */}
                        {userContext.primary_service_name && (
                            <div className="p-3.5 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200/70 rounded-2xl flex items-center justify-between text-xs text-blue-900 shadow-2xs">
                                <div className="flex items-center gap-2.5">
                                    <span className="p-1.5 rounded-lg bg-blue-600 text-white">
                                        <Building2 className="w-3.5 h-3.5" />
                                    </span>
                                    <div>
                                        <span className="font-bold">Espace prérempli selon votre rattachement :</span>{' '}
                                        <span className="font-semibold text-blue-700">
                                            {userContext.primary_direction_name} → {userContext.primary_service_name}
                                        </span>
                                    </div>
                                </div>
                                <span className="text-[10px] text-blue-500 font-medium hidden sm:inline">
                                    Contexte automatique
                                </span>
                            </div>
                        )}

                        {/* Section 1: Direction, Service & Type documentaire */}
                        <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-5">
                            <div className="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                                <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xs">
                                    1
                                </span>
                                <div>
                                    <h2 className="text-sm font-bold text-slate-900">Périmètre métier</h2>
                                    <p className="text-xs text-slate-400">Direction, Service rattaché et type de pièce</p>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                {/* 1. Direction */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                        <Building2 className="w-3.5 h-3.5 text-indigo-600" />
                                        Direction <span className="text-rose-500">*</span>
                                    </label>
                                    <Select
                                        id="department_select"
                                        value={selectedDepartmentId}
                                        onChange={(e) => handleDepartmentChange(e.target.value)}
                                        placeholder="Sélectionnez une direction..."
                                        error={errors.department_id || errors.direction_id}
                                        required
                                        options={availableDirections.map((dept) => ({
                                            value: dept.id,
                                            label: dept.name,
                                        }))}
                                    />
                                    {selectedDepartment?.description && (
                                        <p className="mt-1 text-[11px] text-slate-400 truncate">{selectedDepartment.description}</p>
                                    )}
                                </div>

                                {/* 2. Service */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                        <Network className="w-3.5 h-3.5 text-indigo-600" />
                                        Service
                                    </label>
                                    <Select
                                        id="service_select"
                                        value={selectedServiceId}
                                        onChange={(e) => handleServiceChange(e.target.value)}
                                        placeholder={
                                            !selectedDepartmentId
                                                ? '← Choisissez d\'abord une direction'
                                                : availableServices.length === 0
                                                    ? 'Aucun service dans cette direction'
                                                    : 'Sélectionnez un service...'
                                        }
                                        error={errors.service_id}
                                        disabled={!selectedDepartmentId || availableServices.length === 0}
                                        options={availableServices.map((svc) => ({
                                            value: svc.id,
                                            label: svc.name,
                                        }))}
                                    />
                                    {selectedService?.name && (
                                        <p className="mt-1 text-[11px] text-slate-400 truncate">Service actif sélectionné</p>
                                    )}
                                </div>

                                {/* 3. Type Documentaire */}
                                <div>
                                    <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5 flex items-center gap-1.5">
                                        <FileStack className="w-3.5 h-3.5 text-indigo-600" />
                                        Type documentaire <span className="text-rose-500">*</span>
                                    </label>
                                    <Select
                                        id="doc_type_select"
                                        value={selectedDocTypeId}
                                        onChange={(e) => handleDocTypeChange(e.target.value)}
                                        placeholder={
                                            !selectedDepartmentId
                                                ? '← Choisissez d\'abord une direction'
                                                : availableDocTypes.length === 0
                                                    ? 'Aucun type configuré'
                                                    : 'Sélectionnez un type...'
                                        }
                                        error={errors.document_type_id}
                                        disabled={!selectedDepartmentId || availableDocTypes.length === 0}
                                        required
                                        options={availableDocTypes.map((type) => ({
                                            value: type.id,
                                            label: type.name,
                                        }))}
                                    />
                                    {selectedDocType?.description && (
                                        <p className="mt-1 text-[11px] text-slate-400 truncate">{selectedDocType.description}</p>
                                    )}
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
                                            Formulaire métier
                                            {selectedDocType && (
                                                <span className="ml-2 font-normal text-indigo-600 text-xs">
                                                    ({selectedDocType.name})
                                                </span>
                                            )}
                                        </h2>
                                        <p className="text-xs text-slate-400">Métadonnées obligatoires et descriptives du document</p>
                                    </div>
                                </div>

                                {selectedDocType && (
                                    <Badge variant="indigo" size="sm">
                                        {metadataDefinitions.length} champ(s)
                                    </Badge>
                                )}
                            </div>

                            {!selectedDocTypeId ? (
                                <div className="p-8 text-center rounded-xl bg-slate-50/70 border border-dashed border-slate-200 text-slate-400 text-xs">
                                    <FileStack className="w-8 h-8 mx-auto text-slate-300 mb-2" />
                                    Veuillez d'abord sélectionner une Direction et un Type documentaire ci-dessus pour afficher le formulaire de métadonnées.
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

                        {/* Section 3: Fichier & Détails */}
                        <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs space-y-5">
                            <div className="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                                <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xs">
                                    3
                                </span>
                                <div>
                                    <h2 className="text-sm font-bold text-slate-900">Fichier & Intitulé</h2>
                                    <p className="text-xs text-slate-400">Pièce jointe physique (PDF, Word, Excel, Image)</p>
                                </div>
                            </div>

                            {/* Drag & Drop Area */}
                            <div>
                                <label className="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                    Fichier du document <span className="text-rose-500">*</span>
                                </label>

                                {!data.file ? (
                                    <div
                                        onDragOver={(e) => {
                                            e.preventDefault();
                                            setDragActive(true);
                                        }}
                                        onDragLeave={() => setDragActive(false)}
                                        onDrop={handleFileDrop}
                                        className={`relative border-2 border-dashed rounded-2xl p-6 text-center transition cursor-pointer ${
                                            dragActive
                                                ? 'border-indigo-500 bg-indigo-50/50'
                                                : errors.file
                                                    ? 'border-rose-300 bg-rose-50/20'
                                                    : 'border-slate-200 hover:border-indigo-400 hover:bg-slate-50/60'
                                        }`}
                                    >
                                        <input
                                            type="file"
                                            id="file_input"
                                            className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                            onChange={handleFileSelect}
                                            disabled={processing}
                                        />
                                        <div className="flex flex-col items-center pointer-events-none">
                                            <div className="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3 shadow-2xs">
                                                <Upload className="w-6 h-6" />
                                            </div>
                                            <p className="text-sm font-bold text-slate-800">
                                                Glissez-déposez votre fichier ici
                                            </p>
                                            <p className="text-xs text-slate-400 mt-1">
                                                ou <span className="text-indigo-600 underline font-semibold">parcourez votre ordinateur</span>
                                            </p>
                                            <p className="text-[11px] text-slate-400 mt-2">
                                                PDF, DOCX, XLSX, PNG, JPG jusqu'à 50 Mo
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="flex items-center justify-between p-4 rounded-xl border border-indigo-200 bg-indigo-50/40">
                                        <div className="flex items-center gap-3 min-w-0">
                                            <FileIcon filename={data.file.name} className="w-9 h-9 shrink-0" />
                                            <div className="min-w-0">
                                                <p className="text-sm font-semibold text-slate-900 truncate">
                                                    {data.file.name}
                                                </p>
                                                <p className="text-xs text-slate-500">
                                                    {formatBytes(data.file.size)} • Type: {data.file.type || 'Fichier'}
                                                </p>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={removeSelectedFile}
                                            className="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition"
                                            title="Retirer le fichier"
                                        >
                                            <X className="w-5 h-5" />
                                        </button>
                                    </div>
                                )}
                                {errors.file && (
                                    <p className="mt-1.5 text-xs text-rose-600 font-medium">{errors.file}</p>
                                )}
                            </div>

                            {/* Document Title & Description */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <Input
                                        id="doc_name"
                                        label="Titre / Nom du document"
                                        placeholder="Ex: Facture FAC-2026-001 (ABC)"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        error={errors.name}
                                        disabled={processing}
                                    />
                                    <p className="mt-1 text-[11px] text-slate-400">Par défaut : nom du fichier sélectionné</p>
                                </div>

                                <div>
                                    <Input
                                        id="doc_description"
                                        label="Note / Description (optionnel)"
                                        placeholder="Brève note de contexte..."
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        error={errors.description}
                                        disabled={processing}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Right Column: Live Summary & Action Card */}
                    <div className="space-y-6">
                        <div className="bg-white rounded-2xl border border-slate-200/80 p-6 shadow-2xs sticky top-20 space-y-5">
                            <div className="flex items-center gap-2 pb-3 border-b border-slate-100">
                                <FileText className="w-4 h-4 text-indigo-600" />
                                <h3 className="text-sm font-bold text-slate-900 uppercase tracking-wider text-[11px]">
                                    Résumé de l'importation
                                </h3>
                            </div>

                            {/* Summary Items */}
                            <div className="space-y-4 text-xs">
                                <div>
                                    <span className="text-slate-400 block mb-0.5">Direction :</span>
                                    {selectedDepartment ? (
                                        <span className="font-semibold text-slate-800 flex items-center gap-1.5">
                                            <Building2 className="w-3.5 h-3.5 text-indigo-500" />
                                            {selectedDepartment.name}
                                        </span>
                                    ) : (
                                        <span className="text-slate-400 italic">Non sélectionnée</span>
                                    )}
                                </div>

                                <div>
                                    <span className="text-slate-400 block mb-0.5">Service :</span>
                                    {selectedService ? (
                                        <span className="font-semibold text-slate-800 flex items-center gap-1.5">
                                            <Network className="w-3.5 h-3.5 text-indigo-500" />
                                            {selectedService.name}
                                        </span>
                                    ) : (
                                        <span className="text-slate-400 italic">Non sélectionné</span>
                                    )}
                                </div>

                                <div>
                                    <span className="text-slate-400 block mb-0.5">Type documentaire :</span>
                                    {selectedDocType ? (
                                        <span className="font-semibold text-slate-800 flex items-center gap-1.5">
                                            <FileStack className="w-3.5 h-3.5 text-indigo-500" />
                                            {selectedDocType.name}
                                        </span>
                                    ) : (
                                        <span className="text-slate-400 italic">Non sélectionné</span>
                                    )}
                                </div>

                                <div>
                                    <span className="text-slate-400 block mb-0.5">Fichier :</span>
                                    {data.file ? (
                                        <span className="font-semibold text-slate-800 truncate block">
                                            {data.file.name} ({formatBytes(data.file.size)})
                                        </span>
                                    ) : (
                                        <span className="text-slate-400 italic">Aucun fichier sélectionné</span>
                                    )}
                                </div>

                                {/* Metadata Live Preview */}
                                <div className="pt-2 border-t border-slate-100">
                                    <span className="text-slate-400 block mb-1.5">
                                        Métadonnées renseignées ({filledMetadataCount}/{metadataDefinitions.length}) :
                                    </span>
                                    {metadataDefinitions.length === 0 ? (
                                        <span className="text-slate-400 italic">Aucun champ configuré</span>
                                    ) : filledMetadataCount === 0 ? (
                                        <span className="text-slate-400 italic">Champs en attente de saisie...</span>
                                    ) : (
                                        <ul className="space-y-1.5 bg-slate-50 p-2.5 rounded-xl border border-slate-100 max-h-48 overflow-y-auto">
                                            {metadataDefinitions.map((def) => {
                                                const val = data.metadata[def.key] ?? data.metadata[def.id];
                                                if (val === '' || val === null || val === undefined) return null;
                                                return (
                                                    <li key={def.key || def.id} className="flex items-start justify-between gap-2 text-[11px]">
                                                        <span className="text-slate-500 font-medium truncate">{def.name} :</span>
                                                        <span className="text-slate-900 font-bold text-right truncate">
                                                            {typeof val === 'boolean' ? (val ? 'Oui' : 'Non') : String(val)}
                                                        </span>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    )}
                                </div>

                                <div className="pt-2 border-t border-slate-100 text-[11px] text-slate-400 space-y-1">
                                    <div className="flex items-center gap-1.5">
                                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                        <span>Version 1 créée automatiquement</span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                        <span>Indexation OCR & recherche activées</span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                        <span>Stockage sécurisé sous le dossier métier</span>
                                    </div>
                                </div>
                            </div>

                            {/* Progress bar if uploading */}
                            {progress && (
                                <div className="space-y-1">
                                    <div className="flex justify-between text-xs text-indigo-600 font-semibold">
                                        <span>Téléversement en cours...</span>
                                        <span>{progress.percentage}%</span>
                                    </div>
                                    <div className="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div
                                            className="bg-indigo-600 h-full transition-all duration-300"
                                            style={{ width: `${progress.percentage}%` }}
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Submit Button */}
                            <Button
                                type="submit"
                                variant="primary"
                                className="w-full justify-center py-2.5 text-sm font-bold shadow-md shadow-indigo-100"
                                disabled={processing || !selectedDocTypeId || !data.file}
                            >
                                <FileUp className="w-4 h-4 mr-2" />
                                {processing ? 'Importation en cours...' : 'IMPORTER LE DOCUMENT'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
