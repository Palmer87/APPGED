import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Breadcrumb from '../../Components/Breadcrumb';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Select from '../../Components/Select';
import Textarea from '../../Components/Textarea';
import Modal from '../../Components/Modal';
import Badge from '../../Components/Badge';
import Tabs from '../../Components/Tabs';
import FileIcon from '../../Components/FileIcon';
import ConfirmDialog from '../../Components/ConfirmDialog';
import {
    Download,
    Eye,
    Share2,
    Star,
    Upload,
    Clock,
    History as HistoryIcon,
    GitBranch,
    MessageSquare,
    Tags as TagIcon,
    Folder,
    FileText,
    CheckCircle2,
    XCircle,
    RotateCcw,
    AlertCircle,
    User as UserIcon,
    Send,
    Copy,
    Check,
    Building2,
    FileStack,
    Edit2
} from 'lucide-react';

export default function DocumentShow({
    document: doc,
    activeWorkflow,
    canApproveCurrentStep = false,
    pastWorkflows = [],
    isFavorite,
    history = [],
    metadataDefinitions = [],
    typeMetadataDefinitions = [],
    permissions = {},
    previewUrl,
    availableUsers = [],
    availableGroups = [],
    availableWorkflows = [],
}) {
    const initialTab = typeof window !== 'undefined'
        ? (new URLSearchParams(window.location.search).get('tab') || 'preview')
        : 'preview';
    const [activeTab, setActiveTab] = useState(initialTab);
    const [newVersionModalOpen, setNewVersionModalOpen] = useState(false);
    const [shareModalOpen, setShareModalOpen] = useState(false);
    const [startWorkflowModalOpen, setStartWorkflowModalOpen] = useState(false);
    const [selectedWorkflowId, setSelectedWorkflowId] = useState('');
    const [workflowActionModalOpen, setWorkflowActionModalOpen] = useState(false);
    const [workflowActionType, setWorkflowActionType] = useState('approve'); // approve | reject | correction
    const [replyToCommentId, setReplyToCommentId] = useState(null);
    const [copiedText, setCopiedText] = useState(false);
    const [isRetryingOcr, setIsRetryingOcr] = useState(false);

    // Format metadata value for display safely
    const getMetadataDisplay = (val, def) => {
        if (!val) return null;

        // Check if boolean first
        if (val.value_boolean !== null && val.value_boolean !== undefined) {
            return (
                <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-bold ${val.value_boolean ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'}`}>
                    {val.value_boolean ? 'Oui' : 'Non'}
                </span>
            );
        }

        const raw = val.value !== undefined && val.value !== null
            ? val.value
            : (val.value_string ?? val.value_text ?? val.value_integer ?? val.value_decimal ?? val.value_date ?? val.value_datetime);

        if (raw === null || raw === undefined || raw === '') {
            return null;
        }

        const type = def?.type || val.definition?.type;

        if (type === 'boolean') {
            const isTrue = raw === true || raw === '1' || raw === 1 || raw === 'true';
            return (
                <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-bold ${isTrue ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'}`}>
                    {isTrue ? 'Oui' : 'Non'}
                </span>
            );
        }

        if (type === 'date' || val.value_date) {
            try {
                const dateStr = String(raw).split('T')[0];
                const parts = dateStr.split('-');
                if (parts.length === 3) {
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                return new Date(raw).toLocaleDateString('fr-FR');
            } catch {
                return String(raw);
            }
        }

        if (type === 'datetime' || val.value_datetime) {
            try {
                return new Date(raw).toLocaleString('fr-FR');
            } catch {
                return String(raw);
            }
        }

        if (type === 'decimal' || val.value_decimal !== null) {
            return String(raw);
        }

        return String(raw);
    };

    // New Version Form
    const versionForm = useForm({
        file: null,
        change_notes: '',
    });

    // Share Form
    const shareForm = useForm({
        target_type: 'user', // user | group
        user_id: '',
        group_id: '',
        target_id: '',
        permission: 'view',
        expires_at: '',
    });

    // Workflow Action Form
    const workflowForm = useForm({
        comment: '',
    });

    // Comment Form
    const commentForm = useForm({
        content: '',
        parent_id: null,
    });

    const formatBytes = (bytes) => {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'Ko', 'Mo', 'Go'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    };

    const isPreviewable = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'svg'].includes((doc.extension || '').toLowerCase());

    const handleFavoriteToggle = () => {
        router.post(`/documents/${doc.id}/favorite`, {}, { preserveScroll: true });
    };

    const handleVersionSubmit = (e) => {
        e.preventDefault();
        versionForm.post(`/documents/${doc.id}/versions`, {
            onSuccess: () => {
                setNewVersionModalOpen(false);
                versionForm.reset();
            },
        });
    };

    const handleVersionRestore = (versionId) => {
        router.post(`/documents/${doc.id}/versions/${versionId}/restore`, {}, {
            preserveScroll: true,
        });
    };

    const handleCommentSubmit = (e) => {
        e.preventDefault();
        if (replyToCommentId) {
            commentForm.post(`/comments/${replyToCommentId}/reply`, {
                onSuccess: () => {
                    commentForm.reset();
                    setReplyToCommentId(null);
                },
            });
        } else {
            commentForm.post(`/documents/${doc.id}/comments`, {
                onSuccess: () => commentForm.reset(),
            });
        }
    };

    const handleWorkflowActionSubmit = (e) => {
        e.preventDefault();
        if (!activeWorkflow) return;

        const endpoint = `/workflow-instances/${activeWorkflow.id}/${workflowActionType}`;
        workflowForm.post(endpoint, {
            onSuccess: () => {
                setWorkflowActionModalOpen(false);
                workflowForm.reset();
            },
        });
    };

    const handleRetryOcr = () => {
        setIsRetryingOcr(true);
        router.post(`/documents/${doc.id}/ocr/retry`, {}, {
            preserveScroll: true,
            onFinish: () => setIsRetryingOcr(false),
        });
    };

    const handleCopyOcrText = (text) => {
        if (!text) return;
        navigator.clipboard.writeText(text);
        setCopiedText(true);
        setTimeout(() => setCopiedText(false), 2000);
    };

    const ocrStatus = typeof doc.current_ocr?.status === 'object'
        ? doc.current_ocr?.status?.value
        : (doc.current_ocr?.status || 'none');

    const tabs = [
        { id: 'preview', label: 'Aperçu', icon: <Eye className="w-4 h-4" /> },
        { id: 'versions', label: 'Versions', icon: <Clock className="w-4 h-4" />, badge: doc.versions?.length || 1 },
        { id: 'ocr', label: 'Texte OCR', icon: <FileText className="w-4 h-4" />, badge: ocrStatus === 'completed' ? '✓' : undefined },
        { id: 'metadata', label: 'Métadonnées', icon: <FileText className="w-4 h-4" /> },
        { id: 'comments', label: 'Discussions', icon: <MessageSquare className="w-4 h-4" />, badge: doc.comments?.length || 0 },
        { id: 'shares', label: 'Partages', icon: <Share2 className="w-4 h-4" />, badge: doc.shares?.length || 0 },
        { id: 'workflow', label: 'Workflow', icon: <GitBranch className="w-4 h-4" />, badge: activeWorkflow ? 1 : 0 },
        { id: 'history', label: 'Historique', icon: <HistoryIcon className="w-4 h-4" /> },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={doc.name} />

            <div className="space-y-6">
                {/* Breadcrumb */}
                <Breadcrumb
                    items={[
                        { label: 'Documents', href: '/documents' },
                        ...(doc.folder ? [{ label: doc.folder.name, href: `/documents?folder_id=${doc.folder.id}` }] : []),
                        { label: doc.name },
                    ]}
                />

                {/* Header Card */}
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-2xs">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div className="flex items-start gap-4">
                            <div className="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 shrink-0">
                                <FileIcon extension={doc.extension} mimeType={doc.mime_type} className="w-10 h-10" />
                            </div>
                            <div className="min-w-0">
                                <div className="flex items-center gap-2.5 flex-wrap">
                                    <h1 className="text-xl sm:text-2xl font-bold text-slate-900 truncate">
                                        {doc.name}
                                    </h1>
                                    <Badge variant={doc.status === 'active' ? 'success' : 'warning'}>
                                        {doc.status}
                                    </Badge>

                                    {/* OCR Status Pill */}
                                    {ocrStatus === 'completed' && (
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-600" />
                                            OCR : Indexé ({doc.current_ocr?.word_count || 0} mots)
                                        </span>
                                    )}
                                    {ocrStatus === 'processing' && (
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 animate-pulse">
                                            <RotateCcw className="w-3.5 h-3.5 text-blue-600 animate-spin" />
                                            OCR : Traitement en cours...
                                        </span>
                                    )}
                                    {ocrStatus === 'pending' && (
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                            <Clock className="w-3.5 h-3.5 text-amber-600" />
                                            OCR : En attente
                                        </span>
                                    )}
                                    {ocrStatus === 'failed' && (
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                            <AlertCircle className="w-3.5 h-3.5 text-rose-600" />
                                            OCR : Échec
                                        </span>
                                    )}
                                    {ocrStatus === 'skipped' && (
                                        <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                            OCR : Non applicable
                                        </span>
                                    )}
                                </div>

                                {/* Direction and Document Type metadata badges */}
                                {(doc.document_type?.parent?.name || doc.document_type?.name || doc.folder) && (
                                    <div className="mt-2 flex items-center gap-2 flex-wrap">
                                        {(doc.document_type?.parent?.name || (doc.folder?.folder_type === 'department' ? doc.folder?.name : null)) && (
                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                <Building2 className="w-3.5 h-3.5 text-blue-600" />
                                                Direction : {doc.document_type?.parent?.name || doc.folder?.name}
                                            </span>
                                        )}
                                        {(doc.document_type?.name || (doc.folder?.folder_type === 'document_type' ? doc.folder?.name : null)) && (
                                            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                                <FileStack className="w-3.5 h-3.5 text-purple-600" />
                                                Type : {doc.document_type?.name || doc.folder?.name}
                                            </span>
                                        )}
                                    </div>
                                )}

                                <div className="mt-2 flex items-center gap-3 text-xs text-slate-500 flex-wrap">
                                    <span className="font-mono">{formatBytes(doc.size)}</span>
                                    <span>•</span>
                                    <span>Version {doc.latest_version?.version_number || 1}</span>
                                    <span>•</span>
                                    <span>Créé le {new Date(doc.created_at).toLocaleDateString('fr-FR')}</span>
                                    {doc.creator && (
                                        <>
                                            <span>•</span>
                                            <span>Par {doc.creator.name}</span>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Action Buttons */}
                        <div className="flex items-center gap-2 flex-wrap">
                            {permissions.can_edit && (
                                <Link href={`/documents/${doc.id}/edit`}>
                                    <Button
                                        variant="primary"
                                        size="sm"
                                        className="bg-indigo-600 hover:bg-indigo-700 text-white font-bold shadow-xs"
                                    >
                                        <Edit2 className="w-4 h-4 mr-1.5" />
                                        Modifier
                                    </Button>
                                </Link>
                            )}

                            <Button
                                variant="secondary"
                                size="sm"
                                onClick={handleFavoriteToggle}
                                className={isFavorite ? 'text-amber-500 border-amber-200 bg-amber-50/50' : ''}
                            >
                                <Star className={`w-4 h-4 ${isFavorite ? 'fill-amber-400 text-amber-500' : ''}`} />
                                {isFavorite ? 'Favori' : 'Ajouter aux favoris'}
                            </Button>

                            <a href={`/documents/${doc.id}/download`} className="inline-block">
                                <Button variant="secondary" size="sm">
                                    <Download className="w-4 h-4" />
                                    Télécharger
                                </Button>
                            </a>

                            {permissions.can_version && (
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={() => setNewVersionModalOpen(true)}
                                >
                                    <Upload className="w-4 h-4" />
                                    Nouvelle version
                                </Button>
                            )}

                            {permissions.can_edit && (
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    disabled={isRetryingOcr || ocrStatus === 'processing'}
                                    onClick={handleRetryOcr}
                                    title="Lancer ou relancer la reconnaissance OCR"
                                >
                                    <RotateCcw className={`w-4 h-4 ${isRetryingOcr ? 'animate-spin' : ''}`} />
                                    Relancer OCR
                                </Button>
                            )}

                            {permissions.can_share && (
                                <Button
                                    variant="primary"
                                    size="sm"
                                    onClick={() => setShareModalOpen(true)}
                                >
                                    <Share2 className="w-4 h-4" />
                                    Partager
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* Tabs Navigation */}
                <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} className="px-6" />

                    <div className="p-6">
                        {/* 1. Preview Tab */}
                        {activeTab === 'preview' && (
                            <div className="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                                {/* Left/Main: Preview */}
                                <div className="xl:col-span-2">
                                    {isPreviewable ? (
                                        <div className="rounded-2xl overflow-hidden border border-slate-200 bg-slate-900/5 min-h-[550px] flex items-center justify-center shadow-2xs">
                                            {doc.extension.toLowerCase() === 'pdf' ? (
                                                <iframe
                                                    src={`${previewUrl}#toolbar=0`}
                                                    className="w-full h-[720px] border-0"
                                                    title={`Aperçu de ${doc.name}`}
                                                />
                                            ) : (
                                                <img
                                                    src={previewUrl}
                                                    alt={doc.name}
                                                    className="max-h-[700px] max-w-full object-contain mx-auto p-4 rounded-xl"
                                                />
                                            )}
                                        </div>
                                    ) : (
                                        <div className="flex flex-col items-center justify-center p-16 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50">
                                            <div className="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 mb-3 shadow-2xs">
                                                <FileIcon extension={doc.extension} mimeType={doc.mime_type} className="w-8 h-8" />
                                            </div>
                                            <h3 className="text-base font-semibold text-slate-800">Aperçu direct indisponible</h3>
                                            <p className="text-xs text-slate-500 max-w-sm mt-1 mb-5">
                                                Le format <span className="font-semibold uppercase">{doc.extension}</span> ne peut pas être prévisualisé directement dans le navigateur. Téléchargez le fichier pour le consulter.
                                            </p>
                                            <a href={`/documents/${doc.id}/download`}>
                                                <Button variant="primary" size="md">
                                                    <Download className="w-4 h-4 mr-2" />
                                                    Télécharger le document
                                                </Button>
                                            </a>
                                        </div>
                                    )}
                                </div>

                                {/* Right: Informations Métier & Actions Card */}
                                <div className="space-y-5">
                                    <div className="bg-white rounded-2xl border border-slate-200/90 p-5 shadow-2xs space-y-4">
                                        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
                                            <div className="flex items-center gap-2">
                                                <FileText className="w-4 h-4 text-indigo-600" />
                                                <h3 className="text-xs font-bold text-slate-900 uppercase tracking-wider">
                                                    Informations Métier
                                                </h3>
                                            </div>
                                            {permissions.can_edit && (
                                                <Link
                                                    href={`/documents/${doc.id}/edit`}
                                                    className="text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center gap-1"
                                                >
                                                    <Edit2 className="w-3.5 h-3.5" />
                                                    Modifier
                                                </Link>
                                            )}
                                        </div>

                                        {/* Direction & Type */}
                                        <div className="space-y-2.5 text-xs">
                                            <div>
                                                <span className="text-slate-400 block mb-0.5">Direction :</span>
                                                <div className="font-semibold text-slate-900 flex items-center gap-1.5">
                                                    <Building2 className="w-3.5 h-3.5 text-indigo-500" />
                                                    {doc.document_type?.parent?.name || (doc.folder?.folder_type === 'department' ? doc.folder?.name : (doc.folder?.parent?.name || 'Non spécifiée'))}
                                                </div>
                                            </div>

                                            <div>
                                                <span className="text-slate-400 block mb-0.5">Type documentaire :</span>
                                                <div className="font-semibold text-slate-900 flex items-center gap-1.5">
                                                    <FileStack className="w-3.5 h-3.5 text-indigo-500" />
                                                    {doc.document_type?.name || (doc.folder?.folder_type === 'document_type' ? doc.folder?.name : 'Non spécifié')}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Key Metadata Fields */}
                                        <div className="pt-3 border-t border-slate-100 space-y-2">
                                            <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">
                                                Métadonnées du formulaire
                                            </span>

                                            {(() => {
                                                const docType = doc.document_type || doc.documentType;
                                                const typeDefs = (typeMetadataDefinitions && typeMetadataDefinitions.length > 0)
                                                    ? typeMetadataDefinitions
                                                    : (docType?.metadata_definitions || docType?.metadataDefinitions || []);

                                                if (typeDefs.length === 0 && (!doc.metadata_values || doc.metadata_values.length === 0)) {
                                                    return (
                                                        <div className="p-3 text-center rounded-xl bg-slate-50 text-slate-400 text-xs italic">
                                                            Aucune métadonnée renseignée.
                                                        </div>
                                                    );
                                                }

                                                return (
                                                    <div className="rounded-xl bg-slate-50/80 p-3 border border-slate-100 space-y-2 text-xs">
                                                        {typeDefs.map((def) => {
                                                            const mv = (doc.metadata_values || []).find(
                                                                (v) => v.metadata_definition_id === def.id || v.definition?.key === def.key
                                                            );
                                                            const displayVal = getMetadataDisplay(mv, def);

                                                            return (
                                                                <div key={def.id || def.key} className="flex items-start justify-between gap-2 text-xs">
                                                                    <span className="text-slate-500 font-medium">{def.name} :</span>
                                                                    <span className="text-slate-900 font-bold text-right">
                                                                        {displayVal || <span className="text-slate-300 font-normal italic">—</span>}
                                                                    </span>
                                                                </div>
                                                            );
                                                        })}

                                                        {/* Any extra metadata values */}
                                                        {(doc.metadata_values || [])
                                                            .filter((mv) => !typeDefs.some((td) => td.id === mv.metadata_definition_id))
                                                            .map((mv) => (
                                                                <div key={mv.id} className="flex items-start justify-between gap-2 text-xs">
                                                                    <span className="text-slate-500 font-medium">{mv.definition?.name || mv.metadata_definition_id} :</span>
                                                                    <span className="text-slate-900 font-bold text-right">
                                                                        {getMetadataDisplay(mv, mv.definition) || '—'}
                                                                    </span>
                                                                </div>
                                                            ))}
                                                    </div>
                                                );
                                            })()}
                                        </div>

                                        {/* Actions block */}
                                        <div className="pt-3 border-t border-slate-100 space-y-2">
                                            <span className="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-1">
                                                Actions rapides
                                            </span>

                                            <div className="grid grid-cols-2 gap-2">
                                                {permissions.can_edit && (
                                                    <Link href={`/documents/${doc.id}/edit`} className="w-full">
                                                        <Button variant="primary" size="sm" className="w-full justify-center text-xs font-bold">
                                                            <Edit2 className="w-3.5 h-3.5 mr-1.5" />
                                                            Modifier
                                                        </Button>
                                                    </Link>
                                                )}

                                                <a href={`/documents/${doc.id}/download`} className="w-full">
                                                    <Button variant="secondary" size="sm" className="w-full justify-center text-xs font-bold">
                                                        <Download className="w-3.5 h-3.5 mr-1.5" />
                                                        Télécharger
                                                    </Button>
                                                </a>

                                                {permissions.can_version && (
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        onClick={() => setNewVersionModalOpen(true)}
                                                        className="w-full justify-center text-xs"
                                                    >
                                                        <Upload className="w-3.5 h-3.5 mr-1.5" />
                                                        Nouvelle version
                                                    </Button>
                                                )}

                                                {permissions.can_share && (
                                                    <Button
                                                        variant="secondary"
                                                        size="sm"
                                                        onClick={() => setShareModalOpen(true)}
                                                        className="w-full justify-center text-xs"
                                                    >
                                                        <Share2 className="w-3.5 h-3.5 mr-1.5" />
                                                        Partager
                                                    </Button>
                                                )}
                                            </div>

                                            {/* Quick tab switch buttons */}
                                            <div className="pt-2 flex items-center justify-between text-xs text-slate-500">
                                                <button
                                                    type="button"
                                                    onClick={() => setActiveTab('history')}
                                                    className="hover:text-indigo-600 transition flex items-center gap-1 text-[11px] font-medium"
                                                >
                                                    <HistoryIcon className="w-3 h-3" />
                                                    Historique
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setActiveTab('comments')}
                                                    className="hover:text-indigo-600 transition flex items-center gap-1 text-[11px] font-medium"
                                                >
                                                    <MessageSquare className="w-3 h-3" />
                                                    Commentaires ({doc.comments?.length || 0})
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => setActiveTab('versions')}
                                                    className="hover:text-indigo-600 transition flex items-center gap-1 text-[11px] font-medium"
                                                >
                                                    <Clock className="w-3 h-3" />
                                                    Versions ({doc.versions?.length || 1})
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        {/* 2. Versions Tab */}
                        {activeTab === 'versions' && (
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <h3 className="text-sm font-semibold text-slate-900">Historique des versions</h3>
                                    {permissions.can_version && (
                                        <Button size="sm" variant="secondary" onClick={() => setNewVersionModalOpen(true)}>
                                            <Upload className="w-3.5 h-3.5" />
                                            Ajouter une version
                                        </Button>
                                    )}
                                </div>
                                <div className="divide-y divide-slate-100 rounded-xl border border-slate-200 overflow-hidden">
                                    {doc.versions?.map((v) => (
                                        <div key={v.id} className="p-4 flex items-center justify-between gap-4 bg-white hover:bg-slate-50/50 transition">
                                            <div className="flex items-center gap-3">
                                                <div className="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-xs flex items-center justify-center border border-indigo-100">
                                                    V{v.version_number}
                                                </div>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-semibold text-sm text-slate-800">Version {v.version_number}</span>
                                                        {v.id === doc.latest_version?.id && (
                                                            <Badge variant="primary" size="sm">Actuelle</Badge>
                                                        )}
                                                    </div>
                                                    <p className="text-xs text-slate-400 mt-0.5">
                                                        {formatBytes(v.size)} • {new Date(v.created_at).toLocaleDateString('fr-FR')} • {v.creator?.name || 'Système'}
                                                    </p>
                                                    {v.change_notes && (
                                                        <p className="text-xs text-slate-600 bg-slate-50 p-1.5 rounded-md mt-1 italic">
                                                            « {v.change_notes} »
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2">
                                                <a
                                                    href={`/documents/${doc.id}/versions/${v.id}/download`}
                                                    className="p-1.5 text-slate-500 hover:text-slate-800 rounded-lg hover:bg-slate-100 transition"
                                                    title="Télécharger cette version"
                                                >
                                                    <Download className="w-4 h-4" />
                                                </a>
                                                {permissions.can_version && v.id !== doc.latest_version?.id && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleVersionRestore(v.id)}
                                                        className="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition text-xs font-semibold flex items-center gap-1"
                                                        title="Restaurer cette version"
                                                    >
                                                        <RotateCcw className="w-3.5 h-3.5" />
                                                        Restaurer
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* 2.5 OCR Tab */}
                        {activeTab === 'ocr' && (
                            <div className="space-y-6">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                    <div>
                                        <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                            <FileText className="w-4 h-4 text-indigo-600" />
                                            Reconnaissance de texte (OCR)
                                        </h3>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            Texte extrait automatiquement pour la recherche et l'indexation
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {doc.current_ocr?.extracted_text && (
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                onClick={() => handleCopyOcrText(doc.current_ocr.extracted_text)}
                                            >
                                                {copiedText ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                                {copiedText ? 'Copié !' : 'Copier le texte'}
                                            </Button>
                                        )}
                                        {permissions.can_edit && (
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                disabled={isRetryingOcr || ocrStatus === 'processing'}
                                                onClick={handleRetryOcr}
                                            >
                                                <RotateCcw className={`w-3.5 h-3.5 ${isRetryingOcr ? 'animate-spin' : ''}`} />
                                                Relancer OCR
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                {/* OCR Status Panels */}
                                {ocrStatus === 'completed' && (
                                    <div className="space-y-4">
                                        <div className="flex items-center gap-4 text-xs text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                                            <div>Mots détectés : <strong className="text-slate-800">{doc.current_ocr.word_count || 0}</strong></div>
                                            {doc.current_ocr.confidence && (
                                                <div>Confiance : <strong className="text-slate-800">{Math.round(doc.current_ocr.confidence)}%</strong></div>
                                            )}
                                            {doc.current_ocr.execution_time_ms && (
                                                <div>Temps de traitement : <strong className="text-slate-800">{(doc.current_ocr.execution_time_ms / 1000).toFixed(2)}s</strong></div>
                                            )}
                                        </div>

                                        <div className="p-4 rounded-xl border border-slate-200 bg-slate-50 font-mono text-xs text-slate-800 whitespace-pre-wrap max-h-[500px] overflow-y-auto leading-relaxed select-all">
                                            {doc.current_ocr.extracted_text}
                                        </div>
                                    </div>
                                )}

                                {ocrStatus === 'pending' && (
                                    <div className="text-center py-12 px-4 border border-dashed border-amber-200 rounded-xl bg-amber-50/50">
                                        <Clock className="w-10 h-10 text-amber-500 mx-auto mb-3 animate-pulse" />
                                        <h4 className="text-sm font-bold text-amber-900">Traitement OCR en attente</h4>
                                        <p className="text-xs text-amber-700 max-w-md mx-auto mt-1.5 leading-relaxed">
                                            Le document est placé dans la file d'attente. Il sera traité dès que le gestionnaire de tâches (worker) prendra en charge la requête.
                                        </p>
                                        {permissions.can_edit && (
                                            <div className="mt-4 flex items-center justify-center gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    className="bg-white border-amber-300 text-amber-800 hover:bg-amber-100"
                                                    disabled={isRetryingOcr}
                                                    onClick={handleRetryOcr}
                                                >
                                                    <RotateCcw className={`w-3.5 h-3.5 ${isRetryingOcr ? 'animate-spin' : ''}`} />
                                                    Relancer le traitement
                                                </Button>
                                            </div>
                                        )}
                                    </div>
                                )}

                                {ocrStatus === 'processing' && (
                                    <div className="text-center py-12 px-4 border border-dashed border-blue-200 rounded-xl bg-blue-50/50">
                                        <RotateCcw className="w-10 h-10 text-blue-500 mx-auto mb-3 animate-spin" />
                                        <h4 className="text-sm font-bold text-blue-900">Extraction OCR en cours...</h4>
                                        <p className="text-xs text-blue-700 max-w-md mx-auto mt-1.5">
                                            L'analyse et la reconnaissance du document sont en cours d'exécution.
                                        </p>
                                    </div>
                                )}

                                {ocrStatus === 'failed' && (
                                    <div className="text-center py-12 px-4 border border-dashed border-rose-200 rounded-xl bg-rose-50/50">
                                        <AlertCircle className="w-10 h-10 text-rose-500 mx-auto mb-3" />
                                        <h4 className="text-sm font-bold text-rose-900">Échec du traitement OCR</h4>
                                        <p className="text-xs text-rose-700 max-w-md mx-auto mt-1.5">
                                            {doc.current_ocr?.error_message || "Une erreur est survenue lors de l'extraction OCR."}
                                        </p>
                                        {permissions.can_edit && (
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                className="mt-4 bg-white border-rose-300 text-rose-800 hover:bg-rose-100"
                                                disabled={isRetryingOcr}
                                                onClick={handleRetryOcr}
                                            >
                                                <RotateCcw className="w-3.5 h-3.5 mr-1.5" />
                                                Réessayer l'OCR
                                            </Button>
                                        )}
                                    </div>
                                )}

                                {ocrStatus === 'skipped' && (
                                    <div className="text-center py-12 px-4 border border-dashed border-slate-200 rounded-xl bg-slate-50">
                                        <FileText className="w-10 h-10 text-slate-400 mx-auto mb-3" />
                                        <h4 className="text-sm font-bold text-slate-700">OCR non applicable</h4>
                                        <p className="text-xs text-slate-500 max-w-md mx-auto mt-1.5">
                                            {doc.current_ocr?.error_message || "Ce format ou cette taille de fichier n'est pas éligible au traitement OCR."}
                                        </p>
                                    </div>
                                )}

                                {ocrStatus === 'none' && (
                                    <div className="text-center py-12 px-4 border border-dashed border-slate-200 rounded-xl bg-slate-50">
                                        <FileText className="w-10 h-10 text-slate-400 mx-auto mb-3" />
                                        <h4 className="text-sm font-bold text-slate-700">Aucun traitement OCR</h4>
                                        <p className="text-xs text-slate-500 max-w-md mx-auto mt-1.5">
                                            Aucun processus d'OCR n'a encore été lancé pour ce document.
                                        </p>
                                        {permissions.can_edit && (
                                            <Button
                                                size="sm"
                                                variant="primary"
                                                className="mt-4"
                                                disabled={isRetryingOcr}
                                                onClick={handleRetryOcr}
                                            >
                                                <RotateCcw className="w-3.5 h-3.5 mr-1.5" />
                                                Lancer l'OCR
                                            </Button>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* 3. Metadata Tab */}
                        {activeTab === 'metadata' && (() => {
                            const docType = doc.document_type || doc.documentType;
                            const typeDefs = (typeMetadataDefinitions && typeMetadataDefinitions.length > 0)
                                ? typeMetadataDefinitions
                                : (docType?.metadata_definitions || docType?.metadataDefinitions || []);

                            const otherPopulatedValues = (doc.metadata_values || []).filter(mv => {
                                const notInTypeDefs = !typeDefs.some(td => td.id === mv.metadata_definition_id);
                                const raw = mv.value !== undefined && mv.value !== null ? mv.value : (
                                    mv.value_string ?? mv.value_text ?? mv.value_integer ?? mv.value_decimal ?? mv.value_date ?? mv.value_datetime
                                );
                                const hasVal = (raw !== null && raw !== undefined && raw !== '') ||
                                    (mv.value_boolean !== null && mv.value_boolean !== undefined);
                                return notInTypeDefs && hasVal;
                            });

                            return (
                                <div className="space-y-6">
                                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                                        <div>
                                            <h3 className="text-sm font-bold text-slate-900 flex items-center gap-2">
                                                <FileStack className="w-4 h-4 text-indigo-600" />
                                                Métadonnées du document
                                            </h3>
                                            <p className="text-xs text-slate-500 mt-0.5">
                                                Informations indexées et qualifiées pour ce document
                                            </p>
                                        </div>
                                        {permissions.can_edit && (
                                            <Link
                                                href={`/documents/${doc.id}/edit`}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition"
                                            >
                                                <Edit2 className="w-3.5 h-3.5" />
                                                Modifier les métadonnées
                                            </Link>
                                        )}
                                    </div>

                                    {typeDefs.length > 0 ? (
                                        <div className="space-y-4">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-semibold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                                    <span className="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                    Champs du type : {docType?.name || 'Type documentaire'}
                                                </span>
                                            </div>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                                {typeDefs.map((def) => {
                                                    const val = doc.metadata_values?.find(m => m.metadata_definition_id === def.id);
                                                    const display = getMetadataDisplay(val, def);
                                                    const isRequired = def.pivot?.is_required ?? def.is_required;
                                                    return (
                                                        <div key={def.id} className="p-4 rounded-xl border border-slate-200/80 bg-slate-50/60 hover:border-slate-300 transition shadow-xs">
                                                            <div className="flex items-center justify-between gap-1 mb-1.5">
                                                                <span className="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">
                                                                    {def.name}
                                                                </span>
                                                                {isRequired && (
                                                                    <span className="text-[10px] font-medium text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                                        Requis
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <div className="text-sm font-semibold text-slate-800">
                                                                {display !== null && display !== undefined && display !== '' ? (
                                                                    display
                                                                ) : (
                                                                    <em className="text-slate-400 font-normal">Non renseigné</em>
                                                                )}
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    ) : (
                                        // If no specific document type is set or no typeDefs configured
                                        <div className="space-y-4">
                                            {doc.metadata_values && doc.metadata_values.length > 0 ? (
                                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                                    {doc.metadata_values.map((mv) => {
                                                        const display = getMetadataDisplay(mv, mv.definition);
                                                        return (
                                                            <div key={mv.id} className="p-4 rounded-xl border border-slate-200/80 bg-slate-50/60 shadow-xs">
                                                                <span className="text-[11px] font-bold text-slate-500 block uppercase tracking-wider mb-1.5">
                                                                    {mv.definition?.name || `Champ #${mv.metadata_definition_id}`}
                                                                </span>
                                                                <div className="text-sm font-semibold text-slate-800">
                                                                    {display || <em className="text-slate-400 font-normal">Non renseigné</em>}
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            ) : (
                                                <div className="text-center py-8 px-4 border border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                                                    <FileStack className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                                                    <p className="text-sm font-medium text-slate-700">Aucune métadonnée renseignée</p>
                                                    <p className="text-xs text-slate-500 mt-1">
                                                        Ce document n'a pas encore de valeurs de métadonnées associées.
                                                    </p>
                                                    {permissions.can_edit && (
                                                        <Link
                                                            href={`/documents/${doc.id}/edit`}
                                                            className="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-white border border-indigo-200 hover:bg-indigo-50 rounded-lg shadow-xs transition"
                                                        >
                                                            <Edit2 className="w-3.5 h-3.5" />
                                                            Renseigner des métadonnées
                                                        </Link>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Other populated metadata if any */}
                                    {typeDefs.length > 0 && otherPopulatedValues.length > 0 && (
                                        <div className="space-y-3 pt-4 border-t border-slate-100">
                                            <h4 className="text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                                Autres métadonnées renseignées
                                            </h4>
                                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                                {otherPopulatedValues.map((mv) => {
                                                    const display = getMetadataDisplay(mv, mv.definition);
                                                    return (
                                                        <div key={mv.id} className="p-4 rounded-xl border border-slate-200/60 bg-slate-50/40">
                                                            <span className="text-[11px] font-bold text-slate-500 block uppercase tracking-wider mb-1.5">
                                                                {mv.definition?.name || `Champ #${mv.metadata_definition_id}`}
                                                            </span>
                                                            <div className="text-sm font-semibold text-slate-800">
                                                                {display || <em className="text-slate-400 font-normal">Non renseigné</em>}
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            );
                        })()}

                        {/* 4. Comments Tab */}
                        {activeTab === 'comments' && (
                            <div className="space-y-6">
                                {/* Comment Form */}
                                <form onSubmit={handleCommentSubmit} className="space-y-2">
                                    {replyToCommentId && (
                                        <div className="flex items-center justify-between text-xs text-indigo-600 bg-indigo-50 px-3 py-1.5 rounded-lg">
                                            <span>En réponse au commentaire #{replyToCommentId}</span>
                                            <button type="button" onClick={() => setReplyToCommentId(null)} className="hover:underline">
                                                Annuler
                                            </button>
                                        </div>
                                    )}
                                    <div className="flex gap-2">
                                        <Textarea
                                            value={commentForm.data.content}
                                            onChange={(e) => commentForm.setData('content', e.target.value)}
                                            placeholder="Écrivez un commentaire ou une remarque sur ce document..."
                                            rows={2}
                                            error={commentForm.errors.content}
                                        />
                                    </div>
                                    <div className="flex justify-end">
                                        <Button
                                            type="submit"
                                            variant="primary"
                                            size="sm"
                                            loading={commentForm.processing}
                                            disabled={!commentForm.data.content.trim()}
                                        >
                                            <Send className="w-3.5 h-3.5" />
                                            Publier
                                        </Button>
                                    </div>
                                </form>

                                {/* Comments Thread */}
                                <div className="space-y-4">
                                    {doc.comments && doc.comments.length > 0 ? (
                                        doc.comments.map((c) => (
                                            <div key={c.id} className="p-4 rounded-xl border border-slate-200 bg-slate-50/40 space-y-3">
                                                <div className="flex items-center justify-between text-xs">
                                                    <div className="flex items-center gap-2">
                                                        <div className="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-[10px]">
                                                            {c.user?.first_name ? c.user.first_name[0] : 'U'}
                                                        </div>
                                                        <span className="font-semibold text-slate-800">{c.user?.name || 'Utilisateur'}</span>
                                                    </div>
                                                    <span className="text-slate-400">{new Date(c.created_at).toLocaleDateString('fr-FR')}</span>
                                                </div>
                                                <p className="text-xs text-slate-700 whitespace-pre-wrap">{c.content}</p>
                                                <div className="flex justify-end">
                                                    <button
                                                        type="button"
                                                        onClick={() => setReplyToCommentId(c.id)}
                                                        className="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                                    >
                                                        Répondre
                                                    </button>
                                                </div>

                                                {/* Nested replies */}
                                                {c.replies && c.replies.length > 0 && (
                                                    <div className="ml-6 space-y-2 border-l-2 border-slate-200 pl-3">
                                                        {c.replies.map((r) => (
                                                            <div key={r.id} className="p-2.5 rounded-lg bg-white border border-slate-100 text-xs">
                                                                <div className="flex items-center justify-between font-semibold text-slate-700 mb-1">
                                                                    <span>{r.user?.name || 'Utilisateur'}</span>
                                                                    <span className="text-[10px] text-slate-400 font-normal">{new Date(r.created_at).toLocaleDateString('fr-FR')}</span>
                                                                </div>
                                                                <p className="text-slate-600">{r.content}</p>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-xs text-slate-500 italic text-center py-6">Aucun commentaire pour l'instant. Soyez le premier à commenter !</p>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* 5. Shares Tab */}
                        {activeTab === 'shares' && (
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <h3 className="text-sm font-semibold text-slate-900">Accès partagés sur ce document</h3>
                                    {permissions.can_share && (
                                        <Button size="sm" variant="secondary" onClick={() => setShareModalOpen(true)}>
                                            <Share2 className="w-3.5 h-3.5" />
                                            Partager le document
                                        </Button>
                                    )}
                                </div>
                                <div className="divide-y divide-slate-100 rounded-xl border border-slate-200 overflow-hidden">
                                    {doc.shares && doc.shares.length > 0 ? (
                                        doc.shares.map((s) => (
                                            <div key={s.id} className="p-4 flex items-center justify-between text-xs bg-white">
                                                <div className="flex items-center gap-3">
                                                    <div className="p-2 rounded-lg bg-slate-100 text-slate-600">
                                                        <UserIcon className="w-4 h-4" />
                                                    </div>
                                                    <div>
                                                        <span className="font-semibold text-slate-800">
                                                            {s.user ? s.user.name : s.group?.name}
                                                        </span>
                                                        <div className="flex items-center gap-2 mt-0.5 text-slate-400">
                                                            <span>Permission: <strong className="text-slate-600 uppercase">{s.permission}</strong></span>
                                                            {s.expires_at && <span>• Expire le {new Date(s.expires_at).toLocaleDateString('fr-FR')}</span>}
                                                        </div>
                                                    </div>
                                                </div>
                                                {permissions.can_share && (
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-rose-600 hover:text-rose-700"
                                                        onClick={() => router.delete(`/documents/${doc.id}/shares/${s.id}`, { preserveScroll: true })}
                                                    >
                                                        Révoquer
                                                    </Button>
                                                )}
                                            </div>
                                        ))
                                    ) : (
                                        <p className="text-xs text-slate-500 italic p-6 text-center">Ce document n'a fait l'objet d'aucun partage direct.</p>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* 6. Workflow Tab */}
                        {activeTab === 'workflow' && (
                            <div className="space-y-4">
                                <div className="flex justify-between items-center">
                                    <h3 className="text-sm font-semibold text-slate-900">Circuit de validation</h3>
                                    {!activeWorkflow && availableWorkflows.length > 0 && (
                                        <Button
                                            size="sm"
                                            variant="primary"
                                            onClick={() => setStartWorkflowModalOpen(true)}
                                        >
                                            <GitBranch className="w-3.5 h-3.5" />
                                            Démarrer un circuit
                                        </Button>
                                    )}
                                </div>

                                {activeWorkflow ? (
                                    <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">Workflow</span>
                                                <h4 className="text-base font-bold text-slate-900">{activeWorkflow.workflow?.name}</h4>
                                            </div>
                                            <Badge variant={activeWorkflow.status === 'approved' ? 'success' : activeWorkflow.status === 'rejected' ? 'danger' : 'warning'}>
                                                {activeWorkflow.status}
                                            </Badge>
                                        </div>

                                        <div className="p-3 bg-white rounded-lg border border-slate-200 text-xs space-y-1.5">
                                            <p><strong className="text-slate-700">Étape courante :</strong> {activeWorkflow.current_step?.name || 'Terminé'}</p>
                                            <p><strong className="text-slate-700">Initié par :</strong> {activeWorkflow.started_by?.name || 'Inconnu'}</p>
                                            <p><strong className="text-slate-700">Date de lancement :</strong> {new Date(activeWorkflow.created_at).toLocaleDateString('fr-FR')}</p>
                                        </div>

                                        {/* Action buttons or info */}
                                        {activeWorkflow.status === 'in_progress' && (
                                            canApproveCurrentStep ? (
                                                <div className="flex gap-2 pt-2 border-t border-slate-200">
                                                    <Button
                                                        variant="success"
                                                        size="sm"
                                                        onClick={() => {
                                                            setWorkflowActionType('approve');
                                                            setWorkflowActionModalOpen(true);
                                                        }}
                                                    >
                                                        <CheckCircle2 className="w-4 h-4" />
                                                        Approuver
                                                    </Button>
                                                    <Button
                                                        variant="danger"
                                                        size="sm"
                                                        onClick={() => {
                                                            setWorkflowActionType('reject');
                                                            setWorkflowActionModalOpen(true);
                                                        }}
                                                    >
                                                        <XCircle className="w-4 h-4" />
                                                        Rejeter
                                                    </Button>
                                                    <Button
                                                        variant="warning"
                                                        size="sm"
                                                        onClick={() => {
                                                            setWorkflowActionType('correction');
                                                            setWorkflowActionModalOpen(true);
                                                        }}
                                                    >
                                                        <AlertCircle className="w-4 h-4" />
                                                        Demander correction
                                                    </Button>
                                                </div>
                                            ) : (
                                                <div className="p-3 bg-amber-50/80 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-center gap-2.5">
                                                    <Clock className="w-4 h-4 text-amber-600 shrink-0" />
                                                    <span>
                                                        Circuit en cours d'examen — En attente de :{' '}
                                                        <strong>
                                                            {activeWorkflow.current_step?.approver_user?.first_name
                                                                ? `${activeWorkflow.current_step.approver_user.first_name} ${activeWorkflow.current_step.approver_user.last_name || ''}`
                                                                : activeWorkflow.current_step?.approver_group?.name || activeWorkflow.current_step?.name || "l'approbateur désigné"}
                                                        </strong>
                                                    </span>
                                                </div>
                                            )
                                        )}

                                        {/* Actions history */}
                                        {activeWorkflow.actions && activeWorkflow.actions.length > 0 && (
                                            <div className="pt-3 border-t border-slate-200 space-y-2">
                                                <h5 className="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Historique des décisions</h5>
                                                <div className="space-y-1.5">
                                                    {activeWorkflow.actions.map((act) => (
                                                        <div key={act.id} className="p-2.5 rounded-lg bg-white border border-slate-100 text-xs flex items-start justify-between gap-3">
                                                            <div>
                                                                <div className="font-semibold text-slate-800 flex items-center gap-1.5">
                                                                    <span>{act.user?.name || act.user?.first_name || 'Utilisateur'}</span>
                                                                    <Badge size="xs" variant={act.action === 'approved' ? 'success' : act.action === 'rejected' ? 'danger' : 'warning'}>
                                                                        {act.action}
                                                                    </Badge>
                                                                    {act.step && <span className="text-[11px] text-slate-400 font-normal">({act.step.name})</span>}
                                                                </div>
                                                                {act.comment && <p className="text-slate-600 italic mt-0.5">« {act.comment} »</p>}
                                                            </div>
                                                            <span className="text-[10px] text-slate-400 shrink-0">
                                                                {new Date(act.created_at).toLocaleDateString('fr-FR')}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        <div className="p-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 text-center space-y-3">
                                            <div className="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto">
                                                <GitBranch className="w-6 h-6" />
                                            </div>
                                            <div>
                                                <h4 className="text-sm font-bold text-slate-900">Aucun workflow en cours sur ce document</h4>
                                                <p className="text-xs text-slate-500 max-w-md mx-auto mt-1">
                                                    Lancez un circuit d'approbation pour soumettre ce document aux étapes de validation de votre organisation.
                                                </p>
                                            </div>
                                            {availableWorkflows.length > 0 ? (
                                                <Button
                                                    variant="primary"
                                                    size="sm"
                                                    onClick={() => setStartWorkflowModalOpen(true)}
                                                    className="mt-2"
                                                >
                                                    <GitBranch className="w-4 h-4" />
                                                    Sélectionner et lancer un circuit
                                                </Button>
                                            ) : (
                                                <div className="pt-2">
                                                    <Link href="/workflows" className="text-xs text-indigo-600 hover:text-indigo-700 font-semibold underline">
                                                        Créer un modèle de workflow dans les paramètres →
                                                    </Link>
                                                </div>
                                            )}
                                        </div>

                                        {/* Past completed workflows if any */}
                                        {pastWorkflows && pastWorkflows.length > 0 && (
                                            <div className="space-y-2 pt-2">
                                                <h4 className="text-xs font-bold text-slate-700 uppercase tracking-wider">Circuits précédents</h4>
                                                <div className="space-y-2">
                                                    {pastWorkflows.map((pw) => (
                                                        <div key={pw.id} className="p-3.5 rounded-xl border border-slate-200 bg-white space-y-2 text-xs">
                                                            <div className="flex items-center justify-between">
                                                                <span className="font-semibold text-slate-800">{pw.workflow?.name}</span>
                                                                <Badge variant={pw.status === 'approved' ? 'success' : pw.status === 'rejected' ? 'danger' : 'default'}>
                                                                    {pw.status}
                                                                </Badge>
                                                            </div>
                                                            {pw.actions && pw.actions.length > 0 && (
                                                                <div className="space-y-1 pt-1 border-t border-slate-100 text-[11px] text-slate-500">
                                                                    {pw.actions.map((act) => (
                                                                        <div key={act.id} className="flex items-center justify-between gap-2">
                                                                            <span>{act.user?.name || 'Approbateur'} : <strong>{act.action}</strong> {act.comment ? `« ${act.comment} »` : ''}</span>
                                                                            <span>{new Date(act.created_at).toLocaleDateString('fr-FR')}</span>
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}

                        {/* 7. OCR Tab */}
                        {activeTab === 'ocr' && (
                            <div className="space-y-6">
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
                                    <div>
                                        <h3 className="text-sm font-semibold text-slate-900">Reconnaissance optique de caractères (OCR)</h3>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            Texte extrait et indexé dans PostgreSQL pour la recherche documentaire.
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {permissions.can_edit && (
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                disabled={isRetryingOcr || ocrStatus === 'processing'}
                                                onClick={handleRetryOcr}
                                            >
                                                <RotateCcw className={`w-3.5 h-3.5 ${isRetryingOcr ? 'animate-spin' : ''}`} />
                                                Relancer OCR
                                            </Button>
                                        )}
                                        {doc.current_ocr?.extracted_text && (
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                onClick={() => handleCopyOcrText(doc.current_ocr.extracted_text)}
                                            >
                                                {copiedText ? (
                                                    <>
                                                        <Check className="w-3.5 h-3.5 text-emerald-600" />
                                                        Copié !
                                                    </>
                                                ) : (
                                                    <>
                                                        <Copy className="w-3.5 h-3.5" />
                                                        Copier le texte
                                                    </>
                                                )}
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                {/* OCR Metadata stats */}
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div className="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                        <span className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Statut</span>
                                        <span className="text-xs font-bold text-slate-800 capitalize mt-0.5 block">
                                            {ocrStatus === 'completed' ? '✓ Complété' : ocrStatus === 'processing' ? '⏳ En cours' : ocrStatus === 'failed' ? '⚠ Échec' : ocrStatus === 'skipped' ? '— Non applicable' : 'En attente'}
                                        </span>
                                    </div>
                                    <div className="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                        <span className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Mots indexés</span>
                                        <span className="text-xs font-bold text-slate-800 mt-0.5 block">
                                            {doc.current_ocr?.word_count ?? 0}
                                        </span>
                                    </div>
                                    <div className="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                        <span className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Langues</span>
                                        <span className="text-xs font-bold text-slate-800 mt-0.5 block uppercase">
                                            {doc.current_ocr?.language || 'fra+eng'}
                                        </span>
                                    </div>
                                    <div className="p-3 bg-slate-50 rounded-xl border border-slate-200/80">
                                        <span className="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block">Dernier traitement</span>
                                        <span className="text-xs font-bold text-slate-800 mt-0.5 block">
                                            {doc.current_ocr?.processed_at ? new Date(doc.current_ocr.processed_at).toLocaleString('fr-FR') : '—'}
                                        </span>
                                    </div>
                                </div>

                                {/* Error message banner if failed */}
                                {ocrStatus === 'failed' && (
                                    <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 space-y-1">
                                        <div className="font-semibold flex items-center gap-1.5">
                                            <AlertCircle className="w-4 h-4 text-rose-600" />
                                            Erreur lors du traitement OCR
                                        </div>
                                        <p className="text-rose-700">{doc.current_ocr?.error_message || 'Une erreur inconnue est survenue.'}</p>
                                    </div>
                                )}

                                {/* Skipped message banner */}
                                {ocrStatus === 'skipped' && (
                                    <div className="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                        {doc.current_ocr?.error_message || 'Ce type de document n\'a pas nécessité de traitement OCR.'}
                                    </div>
                                )}

                                {/* Extracted Text View */}
                                {doc.current_ocr?.extracted_text ? (
                                    <div className="space-y-2">
                                        <span className="text-xs font-semibold text-slate-700">Contenu textuel extrait :</span>
                                        <div className="p-4 rounded-xl bg-slate-900 text-slate-100 font-mono text-xs leading-relaxed max-h-[500px] overflow-y-auto whitespace-pre-wrap select-all">
                                            {doc.current_ocr.extracted_text}
                                        </div>
                                    </div>
                                ) : (
                                    ocrStatus === 'completed' && (
                                        <p className="text-xs text-slate-500 italic">Aucun texte n'a été détecté dans ce document.</p>
                                    )
                                )}
                            </div>
                        )}

                        {/* 8. History Tab */}
                        {activeTab === 'history' && (
                            <div className="space-y-4">
                                <h3 className="text-sm font-semibold text-slate-900">Journal d'audit du document</h3>
                                <div className="relative pl-6 space-y-6 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                                    {history.map((log) => (
                                        <div key={log.id} className="relative">
                                            <div className="absolute -left-[1.85rem] top-1 w-3 h-3 rounded-full bg-indigo-600 ring-4 ring-white" />
                                            <div className="text-xs">
                                                <div className="flex items-center gap-2">
                                                    <span className="font-semibold text-slate-800">{log.user?.name || 'Système'}</span>
                                                    <span className="text-slate-400">• {new Date(log.created_at).toLocaleString('fr-FR')}</span>
                                                </div>
                                                <p className="text-slate-600 mt-0.5">{log.description || log.action}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* New Version Modal */}
            <Modal
                isOpen={newVersionModalOpen}
                onClose={() => setNewVersionModalOpen(false)}
                title="Déposer une nouvelle version"
                maxWidth="max-w-md"
            >
                <form onSubmit={handleVersionSubmit} className="space-y-4">
                    <Input
                        id="new-version-file"
                        type="file"
                        label="Nouveau fichier"
                        required
                        onChange={(e) => versionForm.setData('file', e.target.files[0])}
                        error={versionForm.errors.file}
                    />

                    <Textarea
                        id="change-notes"
                        label="Notes de version (optionnelles)"
                        placeholder="Ex: Correction des montants et ajout de la signature"
                        rows={3}
                        value={versionForm.data.change_notes}
                        onChange={(e) => versionForm.setData('change_notes', e.target.value)}
                        error={versionForm.errors.change_notes}
                    />

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setNewVersionModalOpen(false)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={versionForm.processing} disabled={!versionForm.data.file}>
                            Enregistrer la version
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Share Modal */}
            <Modal
                isOpen={shareModalOpen}
                onClose={() => {
                    setShareModalOpen(false);
                    shareForm.clearErrors();
                }}
                title="Partager le document"
                maxWidth="max-w-md"
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        const isUser = shareForm.data.target_type === 'user';
                        const endpoint = isUser
                            ? `/documents/${doc.id}/shares/user`
                            : `/documents/${doc.id}/shares/group`;

                        const payload = isUser
                            ? {
                                user_id: shareForm.data.user_id || shareForm.data.target_id,
                                permission: shareForm.data.permission,
                                expires_at: shareForm.data.expires_at || null,
                            }
                            : {
                                group_id: shareForm.data.group_id || shareForm.data.target_id,
                                permission: shareForm.data.permission,
                                expires_at: shareForm.data.expires_at || null,
                            };

                        router.post(endpoint, payload, {
                            preserveScroll: true,
                            onSuccess: () => {
                                setShareModalOpen(false);
                                shareForm.reset();
                            },
                        });
                    }}
                    className="space-y-4"
                >
                    <Select
                        id="share-target-type"
                        label="Partager avec"
                        value={shareForm.data.target_type}
                        onChange={(e) => {
                            shareForm.setData({
                                ...shareForm.data,
                                target_type: e.target.value,
                                user_id: '',
                                group_id: '',
                                target_id: '',
                            });
                            shareForm.clearErrors();
                        }}
                        options={[
                            { value: 'user', label: 'Un collaborateur' },
                            { value: 'group', label: 'Un groupe d\'utilisateurs' },
                        ]}
                    />

                    {shareForm.data.target_type === 'user' ? (
                        availableUsers.length > 0 ? (
                            <Select
                                id="share-user-id"
                                label="Sélectionner le collaborateur"
                                required
                                value={shareForm.data.user_id}
                                onChange={(e) => {
                                    shareForm.setData('user_id', e.target.value);
                                    shareForm.setData('target_id', e.target.value);
                                }}
                                placeholder="-- Choisir un collaborateur --"
                                options={availableUsers.map((u) => ({
                                    value: String(u.id),
                                    label: `${u.first_name || ''} ${u.last_name || ''} (${u.email})`.trim(),
                                }))}
                                error={shareForm.errors.user_id}
                            />
                        ) : (
                            <div className="space-y-1">
                                <Input
                                    id="share-user-id-fallback"
                                    label="ID de l'utilisateur"
                                    type="number"
                                    required
                                    placeholder="Ex: 2"
                                    value={shareForm.data.user_id || shareForm.data.target_id}
                                    onChange={(e) => {
                                        shareForm.setData('user_id', e.target.value);
                                        shareForm.setData('target_id', e.target.value);
                                    }}
                                    error={shareForm.errors.user_id}
                                />
                                <p className="text-xs text-amber-600">Aucun autre collaborateur actif trouvé dans votre organisation.</p>
                            </div>
                        )
                    ) : (
                        availableGroups.length > 0 ? (
                            <Select
                                id="share-group-id"
                                label="Sélectionner le groupe"
                                required
                                value={shareForm.data.group_id}
                                onChange={(e) => {
                                    shareForm.setData('group_id', e.target.value);
                                    shareForm.setData('target_id', e.target.value);
                                }}
                                placeholder="-- Choisir un groupe --"
                                options={availableGroups.map((g) => ({
                                    value: String(g.id),
                                    label: g.name,
                                }))}
                                error={shareForm.errors.group_id}
                            />
                        ) : (
                            <div className="space-y-1">
                                <Input
                                    id="share-group-id-fallback"
                                    label="ID du groupe"
                                    type="number"
                                    required
                                    placeholder="Ex: 1"
                                    value={shareForm.data.group_id || shareForm.data.target_id}
                                    onChange={(e) => {
                                        shareForm.setData('group_id', e.target.value);
                                        shareForm.setData('target_id', e.target.value);
                                    }}
                                    error={shareForm.errors.group_id}
                                />
                                <p className="text-xs text-amber-600">Aucun groupe configuré dans votre organisation.</p>
                            </div>
                        )
                    )}

                    <Select
                        id="share-perm"
                        label="Permission accordée"
                        value={shareForm.data.permission}
                        onChange={(e) => shareForm.setData('permission', e.target.value)}
                        options={[
                            { value: 'view', label: 'Lecture seule (Visualisation)' },
                            { value: 'download', label: 'Téléchargement (Lecture + Export)' },
                        ]}
                        error={shareForm.errors.permission}
                    />

                    <Input
                        id="share-expires"
                        type="date"
                        label="Date d'expiration (optionnelle)"
                        value={shareForm.data.expires_at}
                        onChange={(e) => shareForm.setData('expires_at', e.target.value)}
                        error={shareForm.errors.expires_at}
                    />

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setShareModalOpen(false)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={shareForm.processing}>
                            Partager
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Workflow Action Modal */}
            <Modal
                isOpen={workflowActionModalOpen}
                onClose={() => setWorkflowActionModalOpen(false)}
                title={`Confirmer l'action : ${workflowActionType === 'approve' ? 'Approuver' : workflowActionType === 'reject' ? 'Rejeter' : 'Demander correction'}`}
                maxWidth="max-w-md"
            >
                <form onSubmit={handleWorkflowActionSubmit} className="space-y-4">
                    <Textarea
                        id="workflow-comment"
                        label="Commentaire de décision"
                        placeholder="Précisez la motivation de votre décision..."
                        rows={3}
                        required={workflowActionType !== 'approve'}
                        value={workflowForm.data.comment}
                        onChange={(e) => workflowForm.setData('comment', e.target.value)}
                        error={workflowForm.errors.comment}
                    />

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setWorkflowActionModalOpen(false)}>Annuler</Button>
                        <Button
                            type="submit"
                            variant={workflowActionType === 'approve' ? 'success' : workflowActionType === 'reject' ? 'danger' : 'warning'}
                            loading={workflowForm.processing}
                        >
                            Confirmer
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Start Workflow Modal */}
            <Modal
                isOpen={startWorkflowModalOpen}
                onClose={() => setStartWorkflowModalOpen(false)}
                title="Démarrer un circuit de validation"
                maxWidth="max-w-md"
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (!selectedWorkflowId) return;
                        router.post(`/documents/${doc.id}/workflows/${selectedWorkflowId}/start`, {}, {
                            preserveScroll: true,
                            onSuccess: () => {
                                setStartWorkflowModalOpen(false);
                            },
                        });
                    }}
                    className="space-y-4"
                >
                    <Select
                        id="start-workflow-select"
                        label="Modèle de circuit"
                        required
                        value={selectedWorkflowId}
                        onChange={(e) => setSelectedWorkflowId(e.target.value)}
                        placeholder="-- Sélectionner un modèle --"
                        options={availableWorkflows.map((w) => ({
                            value: String(w.id),
                            label: `${w.name} (${w.steps?.length || 0} étape(s))`,
                        }))}
                    />

                    {selectedWorkflowId && (
                        <div className="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs space-y-2">
                            <span className="font-semibold text-slate-700">Étapes prévues :</span>
                            {availableWorkflows.find((w) => String(w.id) === String(selectedWorkflowId))?.steps?.map((st, idx) => (
                                <div key={st.id || idx} className="flex items-center gap-2 text-slate-600">
                                    <span className="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold">
                                        {idx + 1}
                                    </span>
                                    <span>{st.name} ({st.approver_type === 'user' ? (st.approver_user?.name || 'Collaborateur') : (st.approver_group?.name || 'Groupe')})</span>
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setStartWorkflowModalOpen(false)}>Annuler</Button>
                        <Button type="submit" variant="primary" disabled={!selectedWorkflowId}>
                            Lancer le circuit
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
