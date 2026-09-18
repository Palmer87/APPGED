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
    Check
} from 'lucide-react';

export default function DocumentShow({
    document: doc,
    activeWorkflow,
    isFavorite,
    history = [],
    metadataDefinitions = [],
    permissions = {},
    previewUrl,
}) {
    const [activeTab, setActiveTab] = useState('preview');
    const [newVersionModalOpen, setNewVersionModalOpen] = useState(false);
    const [shareModalOpen, setShareModalOpen] = useState(false);
    const [workflowActionModalOpen, setWorkflowActionModalOpen] = useState(false);
    const [workflowActionType, setWorkflowActionType] = useState('approve'); // approve | reject | correction
    const [replyToCommentId, setReplyToCommentId] = useState(null);
    const [copiedText, setCopiedText] = useState(false);
    const [isRetryingOcr, setIsRetryingOcr] = useState(false);

    // New Version Form
    const versionForm = useForm({
        file: null,
        change_notes: '',
    });

    // Share Form
    const shareForm = useForm({
        target_type: 'user', // user | group
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
                                <div className="mt-1.5 flex items-center gap-3 text-xs text-slate-500 flex-wrap">
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
                            <div>
                                {isPreviewable ? (
                                    <div className="rounded-xl overflow-hidden border border-slate-200 bg-slate-900/5 min-h-[550px] flex items-center justify-center">
                                        {doc.extension.toLowerCase() === 'pdf' ? (
                                            <iframe
                                                src={`${previewUrl}#toolbar=0`}
                                                className="w-full h-[700px] border-0"
                                                title={`Aperçu de ${doc.name}`}
                                            />
                                        ) : (
                                            <img
                                                src={previewUrl}
                                                alt={doc.name}
                                                className="max-h-[650px] max-w-full object-contain mx-auto p-4 rounded-lg"
                                            />
                                        )}
                                    </div>
                                ) : (
                                    <div className="flex flex-col items-center justify-center p-16 text-center border-2 border-dashed border-slate-200 rounded-xl bg-slate-50">
                                        <div className="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 mb-3">
                                            <FileIcon extension={doc.extension} mimeType={doc.mime_type} className="w-8 h-8" />
                                        </div>
                                        <h3 className="text-base font-semibold text-slate-800">Aperçu direct indisponible</h3>
                                        <p className="text-xs text-slate-500 max-w-sm mt-1 mb-5">
                                            Le format <span className="font-semibold uppercase">{doc.extension}</span> ne peut pas être prévisualisé directement dans le navigateur. Téléchargez le fichier pour le consulter.
                                        </p>
                                        <a href={`/documents/${doc.id}/download`}>
                                            <Button variant="primary" size="md">
                                                <Download className="w-4 h-4" />
                                                Télécharger le document
                                            </Button>
                                        </a>
                                    </div>
                                )}
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

                        {/* 3. Metadata Tab */}
                        {activeTab === 'metadata' && (
                            <div className="space-y-4">
                                <h3 className="text-sm font-semibold text-slate-900">Métadonnées personnalisées</h3>
                                {metadataDefinitions.length > 0 ? (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                        {metadataDefinitions.map((def) => {
                                            const val = doc.metadata_values?.find(m => m.metadata_definition_id === def.id);
                                            return (
                                                <div key={def.id} className="p-3.5 rounded-xl border border-slate-200 bg-slate-50/50">
                                                    <span className="text-xs font-semibold text-slate-500 block uppercase tracking-wider">
                                                        {def.name}
                                                    </span>
                                                    <span className="text-sm font-medium text-slate-800 mt-1 block">
                                                        {val ? String(val.value) : <em className="text-slate-400">Non renseigné</em>}
                                                    </span>
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <p className="text-xs text-slate-500 italic">Aucune définition de métadonnée configurée pour votre organisation.</p>
                                )}
                            </div>
                        )}

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
                                <h3 className="text-sm font-semibold text-slate-900">Circuit de validation</h3>
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

                                        {/* Action buttons if in progress */}
                                        {activeWorkflow.status === 'in_progress' && (
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
                                        )}
                                    </div>
                                ) : (
                                    <p className="text-xs text-slate-500 italic">Aucun workflow actif sur ce document.</p>
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
                onClose={() => setShareModalOpen(false)}
                title="Partager le document"
                maxWidth="max-w-md"
            >
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        const endpoint = shareForm.data.target_type === 'user'
                            ? `/documents/${doc.id}/shares/user`
                            : `/documents/${doc.id}/shares/group`;

                        shareForm.post(endpoint, {
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
                        onChange={(e) => shareForm.setData('target_type', e.target.value)}
                        options={[
                            { value: 'user', label: 'Un utilisateur' },
                            { value: 'group', label: 'Un groupe' },
                        ]}
                    />

                    <Input
                        id="share-target-id"
                        label={shareForm.data.target_type === 'user' ? "ID de l'utilisateur" : "ID du groupe"}
                        type="number"
                        required
                        value={shareForm.data.target_id}
                        onChange={(e) => shareForm.setData('target_id', e.target.value)}
                    />

                    <Select
                        id="share-perm"
                        label="Permission accordée"
                        value={shareForm.data.permission}
                        onChange={(e) => shareForm.setData('permission', e.target.value)}
                        options={[
                            { value: 'view', label: 'Lecture seule' },
                            { value: 'download', label: 'Téléchargement' },
                        ]}
                    />

                    <Input
                        id="share-expires"
                        type="date"
                        label="Date d'expiration (optionnelle)"
                        value={shareForm.data.expires_at}
                        onChange={(e) => shareForm.setData('expires_at', e.target.value)}
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
        </AuthenticatedLayout>
    );
}
