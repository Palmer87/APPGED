import React, { useState, useMemo } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import FileIcon from '../../Components/FileIcon';
import Modal from '../../Components/Modal';
import Badge from '../../Components/Badge';
import EmptyState from '../../Components/EmptyState';
import ConfirmDialog from '../../Components/ConfirmDialog';
import {
    Folder as FolderIcon,
    FolderPlus,
    FolderTree,
    Upload,
    ArrowLeft,
    ChevronRight,
    ChevronDown,
    Search,
    LayoutGrid,
    List as ListIcon,
    MoreVertical,
    Edit2,
    Move,
    Trash2,
    Eye,
    Download,
    FileText,
    Share2,
    HardDrive,
    Home,
    CornerDownRight,
    X,
    Check,
    Building2,
    FileStack,
    Sliders,
    Plus
} from 'lucide-react';

export default function FoldersIndex({
    currentFolder = null,
    breadcrumbs = [],
    parentFolder = null,
    subfolders = [],
    documents = [],
    tree = [],
    filters = {},
    can = {},
    documentType = null,
    metadataDefinitions = [],
}) {
    // UI state
    const [viewMode, setViewMode] = useState(() => {
        return (typeof window !== 'undefined' && localStorage.getItem('ged_folder_view_mode')) || 'grid';
    });
    const [sidebarOpen, setSidebarOpen] = useState(true);
    const [searchQuery, setSearchQuery] = useState(filters.search || '');
    const [activeDropdown, setActiveDropdown] = useState(null); // { type: 'folder'|'document', id: number }
    const [draggedItem, setDraggedItem] = useState(null); // { type: 'folder'|'document', id: number, name: string }
    const [dragOverFolderId, setDragOverFolderId] = useState(null);

    // Modals
    const [createFolderModalOpen, setCreateFolderModalOpen] = useState(false);
    const [editFolder, setEditFolder] = useState(null);
    const [deleteFolder, setDeleteFolder] = useState(null);
    const [moveFolder, setMoveFolder] = useState(null);
    const [moveDocument, setMoveDocument] = useState(null);
    const [uploadModalOpen, setUploadModalOpen] = useState(false);
    const [deleteDoc, setDeleteDoc] = useState(null);

    // Toggle view mode
    const handleViewModeChange = (mode) => {
        setViewMode(mode);
        if (typeof window !== 'undefined') {
            localStorage.setItem('ged_folder_view_mode', mode);
        }
    };

    // Forms
    const createForm = useForm({
        name: '',
        description: '',
        parent_id: currentFolder?.id || '',
        folder_type: currentFolder?.folder_type === 'department' ? 'document_type' : 'standard',
    });

    const editForm = useForm({
        name: '',
        description: '',
        parent_id: '',
    });

    const moveFolderForm = useForm({
        name: '',
        parent_id: '',
    });

    const moveDocForm = useForm({
        folder_id: '',
    });

    const uploadForm = useForm({
        file: null,
        name: '',
        description: '',
        folder_id: currentFolder?.id || '',
        metadata: {},
    });

    const handleMetadataChange = (keyOrId, value) => {
        uploadForm.setData('metadata', {
            ...uploadForm.data.metadata,
            [keyOrId]: value,
        });
    };

    // Subfolder & Document filtering by search
    const filteredSubfolders = useMemo(() => {
        if (!searchQuery.trim()) return subfolders;
        const q = searchQuery.toLowerCase();
        return subfolders.filter((f) => f.name.toLowerCase().includes(q));
    }, [subfolders, searchQuery]);

    const filteredDocuments = useMemo(() => {
        if (!searchQuery.trim()) return documents;
        const q = searchQuery.toLowerCase();
        return documents.filter(
            (d) => d.name.toLowerCase().includes(q) || (d.file_name && d.file_name.toLowerCase().includes(q))
        );
    }, [documents, searchQuery]);

    // Tree building for sidebar & move selectors
    const nestedTree = useMemo(() => {
        const map = {};
        const roots = [];

        tree.forEach((item) => {
            map[item.id] = { ...item, children: [] };
        });

        tree.forEach((item) => {
            if (item.parent_id && map[item.parent_id]) {
                map[item.parent_id].children.push(map[item.id]);
            } else if (!item.parent_id) {
                roots.push(map[item.id]);
            }
        });

        return roots;
    }, [tree]);

    // Helpers to prevent cyclic moves (cannot move folder into self or descendant)
    const getDescendantIds = (folderId, allTree) => {
        const descendants = new Set();
        const findChildren = (id) => {
            allTree.forEach((item) => {
                if (item.parent_id === id) {
                    descendants.add(item.id);
                    findChildren(item.id);
                }
            });
        };
        findChildren(folderId);
        return descendants;
    };

    const invalidDestinationFolderIds = useMemo(() => {
        if (!moveFolder) return new Set();
        const invalid = getDescendantIds(moveFolder.id, tree);
        invalid.add(moveFolder.id);
        return invalid;
    }, [moveFolder, tree]);

    // Form handlers
    const handleCreateFolder = (e) => {
        e.preventDefault();
        createForm.setData('parent_id', currentFolder?.id || '');
        createForm.post('/folders', {
            preserveScroll: true,
            onSuccess: () => {
                setCreateFolderModalOpen(false);
                createForm.reset();
            },
        });
    };

    const handleEditFolder = (e) => {
        e.preventDefault();
        if (!editFolder) return;
        editForm.put(`/folders/${editFolder.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setEditFolder(null);
                editForm.reset();
            },
        });
    };

    const handleMoveFolderSubmit = (e) => {
        e.preventDefault();
        if (!moveFolder) return;
        moveFolderForm.put(`/folders/${moveFolder.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setMoveFolder(null);
                moveFolderForm.reset();
            },
        });
    };

    const handleMoveDocumentSubmit = (e) => {
        e.preventDefault();
        if (!moveDocument) return;
        moveDocForm.post(`/documents/${moveDocument.id}/move`, {
            preserveScroll: true,
            onSuccess: () => {
                setMoveDocument(null);
                moveDocForm.reset();
            },
        });
    };

    const handleDeleteFolder = () => {
        if (!deleteFolder) return;
        router.delete(`/folders/${deleteFolder.id}`, {
            preserveScroll: true,
            onFinish: () => setDeleteFolder(null),
        });
    };

    const handleDeleteDocument = () => {
        if (!deleteDoc) return;
        router.delete(`/documents/${deleteDoc.id}`, {
            preserveScroll: true,
            onFinish: () => setDeleteDoc(null),
        });
    };

    const handleUploadSubmit = (e) => {
        e.preventDefault();
        uploadForm.setData('folder_id', currentFolder?.id || '');
        uploadForm.post('/documents', {
            preserveScroll: true,
            onSuccess: () => {
                setUploadModalOpen(false);
                uploadForm.reset();
            },
        });
    };

    // Open Edit modal
    const openEditModal = (folder) => {
        setEditFolder(folder);
        editForm.setData({
            name: folder.name,
            description: folder.description || '',
            parent_id: folder.parent_id || '',
        });
        setActiveDropdown(null);
    };

    // Open Move Folder modal
    const openMoveFolderModal = (folder) => {
        setMoveFolder(folder);
        moveFolderForm.setData({
            name: folder.name,
            parent_id: folder.parent_id || '',
        });
        setActiveDropdown(null);
    };

    // Open Move Document modal
    const openMoveDocumentModal = (doc) => {
        setMoveDocument(doc);
        moveDocForm.setData({
            folder_id: doc.folder_id || '',
        });
        setActiveDropdown(null);
    };

    // Drag & drop handlers
    const handleDragStart = (e, item, type) => {
        setDraggedItem({ ...item, type });
        e.dataTransfer.setData('text/plain', JSON.stringify({ id: item.id, type }));
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOverFolder = (e, targetFolderId) => {
        e.preventDefault();
        if (draggedItem) {
            // Cannot drop onto self or descendant if dragging a folder
            if (draggedItem.type === 'folder' && (draggedItem.id === targetFolderId || invalidDestinationFolderIds.has(targetFolderId))) {
                return;
            }
            e.dataTransfer.dropEffect = 'move';
            setDragOverFolderId(targetFolderId);
        }
    };

    const handleDragLeaveFolder = (e, targetFolderId) => {
        if (dragOverFolderId === targetFolderId) {
            setDragOverFolderId(null);
        }
    };

    const handleDropOnFolder = (e, targetFolder) => {
        e.preventDefault();
        setDragOverFolderId(null);
        if (!draggedItem) return;

        if (draggedItem.type === 'document') {
            router.post(`/documents/${draggedItem.id}/move`, {
                folder_id: targetFolder.id,
            }, {
                preserveScroll: true,
            });
        } else if (draggedItem.type === 'folder') {
            if (draggedItem.id !== targetFolder.id && !invalidDestinationFolderIds.has(targetFolder.id)) {
                router.put(`/folders/${draggedItem.id}`, {
                    name: draggedItem.name,
                    parent_id: targetFolder.id,
                }, {
                    preserveScroll: true,
                });
            }
        }
        setDraggedItem(null);
    };

    // Recursive Tree Node Renderer for sidebar
    const TreeNode = ({ node, level = 0 }) => {
        const [expanded, setExpanded] = useState(() => {
            // Auto expand if current folder is a descendant
            return (
                currentFolder?.id === node.id ||
                (currentFolder?.path && currentFolder.path.includes(node.name))
            );
        });

        const isCurrent = currentFolder?.id === node.id;
        const hasChildren = node.children && node.children.length > 0;

        return (
            <div className="select-none">
                <div
                    className={`group flex items-center justify-between px-2 py-1.5 rounded-lg text-xs font-medium transition ${
                        isCurrent
                            ? 'bg-indigo-50 text-indigo-700 font-bold'
                            : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
                    }`}
                    style={{ paddingLeft: `${Math.max(8, level * 16 + 8)}px` }}
                >
                    <Link
                        href={`/folders/${node.id}`}
                        className="flex items-center gap-2 flex-1 min-w-0"
                    >
                        <FolderIcon
                            className={`w-4 h-4 shrink-0 transition ${
                                isCurrent ? 'text-indigo-600 fill-indigo-200' : 'text-amber-500 fill-amber-100 group-hover:scale-105'
                            }`}
                        />
                        <span className="truncate">{node.name}</span>
                    </Link>

                    {hasChildren && (
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                setExpanded(!expanded);
                            }}
                            className="p-1 text-slate-400 hover:text-slate-700 rounded"
                        >
                            {expanded ? (
                                <ChevronDown className="w-3.5 h-3.5" />
                            ) : (
                                <ChevronRight className="w-3.5 h-3.5" />
                            )}
                        </button>
                    )}
                </div>

                {hasChildren && expanded && (
                    <div className="space-y-0.5">
                        {node.children.map((child) => (
                            <TreeNode key={child.id} node={child} level={level + 1} />
                        ))}
                    </div>
                )}
            </div>
        );
    };

    const isRoot = !currentFolder;

    return (
        <AuthenticatedLayout>
            <Head title={currentFolder ? `${currentFolder.name} - Explorateur` : 'Explorateur de dossiers'} />

            <div className="space-y-4">
                {/* 1. Top Windows Explorer / Finder Toolbar */}
                <div className="bg-white p-3 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                        {/* Navigation & Dynamic Breadcrumb */}
                        <div className="flex items-center gap-2 min-w-0 flex-1">
                            {/* Back Button */}
                            {parentFolder ? (
                                <Link
                                    href={parentFolder.href}
                                    title={`Retour à ${parentFolder.name}`}
                                    className="p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition shrink-0 flex items-center justify-center shadow-2xs"
                                >
                                    <ArrowLeft className="w-4 h-4" />
                                </Link>
                            ) : (
                                <button
                                    disabled
                                    className="p-2 rounded-xl border border-slate-100 text-slate-300 cursor-not-allowed shrink-0 flex items-center justify-center"
                                >
                                    <ArrowLeft className="w-4 h-4" />
                                </button>
                            )}

                            {/* Breadcrumb Path (Scrollable on small screens) */}
                            <nav className="flex items-center gap-1 overflow-x-auto py-1 px-1 bg-slate-50/80 rounded-xl border border-slate-200/60 flex-1 min-w-0 text-xs text-slate-600 scrollbar-none">
                                {breadcrumbs.map((crumb, idx) => {
                                    const isLast = idx === breadcrumbs.length - 1;
                                    return (
                                        <React.Fragment key={idx}>
                                            {idx > 0 && (
                                                <ChevronRight className="w-3.5 h-3.5 text-slate-300 shrink-0" />
                                            )}
                                            {isLast ? (
                                                <span className="px-2 py-1 font-bold text-slate-900 bg-white rounded-lg border border-slate-200 shadow-2xs shrink-0 flex items-center gap-1.5">
                                                    {idx === 0 ? <Home className="w-3.5 h-3.5 text-indigo-600" /> : <FolderIcon className="w-3.5 h-3.5 text-amber-500 fill-amber-100" />}
                                                    <span className="truncate max-w-[160px]">{crumb.name}</span>
                                                </span>
                                            ) : (
                                                <Link
                                                    href={crumb.href}
                                                    className="px-2 py-1 font-medium text-slate-600 hover:text-indigo-600 hover:bg-white/80 rounded-lg transition shrink-0 flex items-center gap-1.5"
                                                >
                                                    {idx === 0 ? <Home className="w-3.5 h-3.5" /> : <FolderIcon className="w-3.5 h-3.5 text-slate-400" />}
                                                    <span className="truncate max-w-[120px]">{crumb.name}</span>
                                                </Link>
                                            )}
                                        </React.Fragment>
                                    );
                                })}
                            </nav>
                        </div>

                        {/* Actions, Search, View Mode */}
                        <div className="flex flex-wrap items-center gap-2">
                            {/* In-folder Search */}
                            <div className="relative flex-1 sm:w-60">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" />
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    placeholder="Filtrer ce dossier..."
                                    className="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white text-slate-700 placeholder-slate-400"
                                />
                                {searchQuery && (
                                    <button
                                        type="button"
                                        onClick={() => setSearchQuery('')}
                                        className="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        <X className="w-3.5 h-3.5" />
                                    </button>
                                )}
                            </div>

                            {/* View Mode Toggle */}
                            <div className="flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200">
                                <button
                                    type="button"
                                    onClick={() => handleViewModeChange('grid')}
                                    title="Vue Grille"
                                    className={`p-1.5 rounded-lg transition ${
                                        viewMode === 'grid'
                                            ? 'bg-white text-indigo-600 shadow-2xs font-semibold'
                                            : 'text-slate-500 hover:text-slate-900'
                                    }`}
                                >
                                    <LayoutGrid className="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    onClick={() => handleViewModeChange('list')}
                                    title="Vue Liste"
                                    className={`p-1.5 rounded-lg transition ${
                                        viewMode === 'list'
                                            ? 'bg-white text-indigo-600 shadow-2xs font-semibold'
                                            : 'text-slate-500 hover:text-slate-900'
                                    }`}
                                >
                                    <ListIcon className="w-4 h-4" />
                                </button>
                            </div>

                            {/* Toggle Tree Sidebar */}
                            <button
                                type="button"
                                onClick={() => setSidebarOpen(!sidebarOpen)}
                                title={sidebarOpen ? 'Masquer l\'arborescence' : 'Afficher l\'arborescence'}
                                className={`hidden lg:flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl border transition ${
                                    sidebarOpen
                                        ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                                        : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                }`}
                            >
                                <FolderTree className="w-3.5 h-3.5" />
                                <span>Arbre</span>
                            </button>

                            {/* Actions: New Folder & Upload */}
                            {can.create_folder && (
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    onClick={() => setCreateFolderModalOpen(true)}
                                    className="shadow-2xs"
                                >
                                    <FolderPlus className="w-4 h-4 text-amber-500" />
                                    <span>Nouveau dossier</span>
                                </Button>
                            )}

                            {can.upload_document && (
                                <Button
                                    variant="primary"
                                    size="sm"
                                    onClick={() => setUploadModalOpen(true)}
                                    className="shadow-2xs"
                                >
                                    <Upload className="w-4 h-4" />
                                    <span>Ajouter un document</span>
                                </Button>
                            )}
                        </div>
                    </div>
                </div>

                {/* 2. Main Explorer Layout (Split Sidebar + Content) */}
                <div className="flex gap-4 items-start">
                    {/* Collapsible Left Tree Sidebar */}
                    {sidebarOpen && (
                        <div className="hidden lg:block w-64 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-3 shrink-0 self-stretch overflow-y-auto max-h-[calc(100vh-220px)] space-y-2">
                            <div className="flex items-center justify-between px-2 py-1 text-xs font-bold uppercase tracking-wider text-slate-400">
                                <span>Arborescence</span>
                                <span className="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded font-mono text-slate-500">
                                    {tree.length}
                                </span>
                            </div>

                            {/* Root "Mon Espace" Node */}
                            <Link
                                href="/folders"
                                className={`flex items-center gap-2 px-2 py-1.5 rounded-lg text-xs font-medium transition ${
                                    isRoot
                                        ? 'bg-indigo-50 text-indigo-700 font-bold'
                                        : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900'
                                }`}
                            >
                                <HardDrive className={`w-4 h-4 ${isRoot ? 'text-indigo-600' : 'text-slate-400'}`} />
                                <span>Espace principal</span>
                            </Link>

                            {/* Nested Tree Nodes */}
                            <div className="space-y-0.5 pt-1 border-t border-slate-100">
                                {nestedTree.map((rootNode) => (
                                    <TreeNode key={rootNode.id} node={rootNode} level={0} />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Main Explorer Content Panel */}
                    <div className="flex-1 min-w-0 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 md:p-6 space-y-6">
                        {/* Direction Banner */}
                        {currentFolder?.folder_type === 'department' && (
                            <div className="rounded-2xl bg-gradient-to-r from-indigo-900 via-indigo-800 to-slate-900 p-5 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div className="flex items-start gap-4">
                                    <div className="p-3 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20 shrink-0">
                                        <Building2 className="w-6 h-6 text-indigo-200" />
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2 mb-1">
                                            <Badge variant="primary" className="bg-white/20 text-white border-white/30 text-[10px] uppercase tracking-wider font-bold">
                                                Direction
                                            </Badge>
                                            <span className="text-xs text-indigo-200">Organisation</span>
                                        </div>
                                        <h1 className="text-lg font-bold text-white">{currentFolder.name}</h1>
                                        {currentFolder.description && (
                                            <p className="text-xs text-indigo-200 mt-1 max-w-xl">{currentFolder.description}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 shrink-0">
                                    {can.create_document_type && (
                                        <Button
                                            variant="secondary"
                                            size="sm"
                                            className="bg-white/10 hover:bg-white/20 text-white border-white/20"
                                            onClick={() => {
                                                createForm.setData({
                                                    name: '',
                                                    description: '',
                                                    parent_id: currentFolder.id,
                                                    folder_type: 'document_type',
                                                });
                                                setCreateFolderModalOpen(true);
                                            }}
                                        >
                                            <Plus className="w-4 h-4 mr-1 text-indigo-200" />
                                            <span>Nouveau type documentaire</span>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Document Type Banner */}
                        {currentFolder?.folder_type === 'document_type' && (
                            <div className="rounded-2xl bg-gradient-to-r from-purple-900 via-purple-800 to-slate-900 p-5 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <div className="flex items-start gap-4">
                                    <div className="p-3 bg-white/10 backdrop-blur-sm rounded-xl border border-white/20 shrink-0">
                                        <FileStack className="w-6 h-6 text-purple-200" />
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2 mb-1">
                                            <Badge variant="purple" className="bg-white/20 text-white border-white/30 text-[10px] uppercase tracking-wider font-bold">
                                                Type documentaire
                                            </Badge>
                                            {parentFolder && (
                                                <span className="text-xs text-purple-200">
                                                    Direction : <strong className="text-white">{parentFolder.name}</strong>
                                                </span>
                                            )}
                                        </div>
                                        <h1 className="text-lg font-bold text-white">{currentFolder.name}</h1>
                                        {currentFolder.description && (
                                            <p className="text-xs text-purple-200 mt-1 max-w-xl">{currentFolder.description}</p>
                                        )}
                                        <div className="flex items-center gap-3 mt-3 text-xs text-purple-200">
                                            <span className="bg-white/10 px-2.5 py-1 rounded-lg border border-white/15">
                                                {filteredDocuments.length} document{filteredDocuments.length > 1 ? 's' : ''}
                                            </span>
                                            <span className="bg-white/10 px-2.5 py-1 rounded-lg border border-white/15">
                                                {metadataDefinitions.length} métadonnée{metadataDefinitions.length > 1 ? 's' : ''} configurée{metadataDefinitions.length > 1 ? 's' : ''}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 shrink-0">
                                    <Link
                                        href={`/document-types/${currentFolder.id}/metadata`}
                                        className="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white/10 hover:bg-white/20 text-white border border-white/20 transition shadow-sm"
                                    >
                                        <Sliders className="w-4 h-4 text-purple-200" />
                                        <span>Gérer les métadonnées</span>
                                    </Link>
                                    {can.upload_document && (
                                        <Button
                                            variant="primary"
                                            size="sm"
                                            className="bg-purple-600 hover:bg-purple-500 border-purple-400"
                                            onClick={() => setUploadModalOpen(true)}
                                        >
                                            <Upload className="w-4 h-4 mr-1" />
                                            <span>Ajouter un document</span>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Empty Folder State */}
                        {filteredSubfolders.length === 0 && filteredDocuments.length === 0 ? (
                            <EmptyState
                                icon={FolderIcon}
                                title={searchQuery ? 'Aucun résultat trouvé' : 'Dossier vide'}
                                description={
                                    searchQuery
                                        ? `Aucun élément ne correspond à "${searchQuery}" dans ce dossier.`
                                        : 'Ce dossier ne contient aucun sous-dossier ni document pour le moment.'
                                }
                                action={
                                    searchQuery ? (
                                        <Button variant="secondary" size="sm" onClick={() => setSearchQuery('')}>
                                            Effacer la recherche
                                        </Button>
                                    ) : (
                                        <div className="flex gap-2">
                                            {can.create_folder && (
                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    onClick={() => setCreateFolderModalOpen(true)}
                                                >
                                                    <FolderPlus className="w-4 h-4 text-amber-500" />
                                                    Créer un sous-dossier
                                                </Button>
                                            )}
                                            {can.upload_document && (
                                                <Button
                                                    variant="primary"
                                                    size="sm"
                                                    onClick={() => setUploadModalOpen(true)}
                                                >
                                                    <Upload className="w-4 h-4" />
                                                    Importer un fichier
                                                </Button>
                                            )}
                                        </div>
                                    )
                                }
                            />
                        ) : (
                            <>
                                {/* SECTION 1: SOUS-DOSSIERS (AFFICHÉS EN PREMIER) */}
                                {filteredSubfolders.length > 0 && (
                                    <div className="space-y-3">
                                        <div className="flex items-center justify-between pb-1 border-b border-slate-100">
                                            <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                                <FolderIcon className="w-3.5 h-3.5 text-amber-500" />
                                                <span>Dossiers ({filteredSubfolders.length})</span>
                                            </h2>
                                            <span className="text-[11px] text-slate-400">
                                                Glissez des fichiers dessus pour les ranger
                                            </span>
                                        </div>

                                        {viewMode === 'grid' ? (
                                            /* Grid Mode Folders */
                                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                                                {filteredSubfolders.map((folder) => {
                                                    const isOver = dragOverFolderId === folder.id;
                                                    return (
                                                        <div
                                                            key={folder.id}
                                                            draggable
                                                            onDragStart={(e) => handleDragStart(e, folder, 'folder')}
                                                            onDragOver={(e) => handleDragOverFolder(e, folder.id)}
                                                            onDragLeave={(e) => handleDragLeaveFolder(e, folder.id)}
                                                            onDrop={(e) => handleDropOnFolder(e, folder)}
                                                            className={`group relative rounded-xl border p-3.5 transition flex flex-col justify-between select-none ${
                                                                isOver
                                                                    ? 'bg-indigo-50 border-indigo-400 scale-[1.02] shadow-md ring-2 ring-indigo-300'
                                                                    : 'bg-slate-50/60 border-slate-200/80 hover:bg-white hover:border-indigo-300 hover:shadow-xs'
                                                            }`}
                                                        >
                                                            <div className="flex items-start justify-between gap-2">
                                                                <Link
                                                                    href={`/folders/${folder.id}`}
                                                                    className="flex items-center gap-3 flex-1 min-w-0"
                                                                >
                                                                    {folder.folder_type === 'department' ? (
                                                                        <div className="p-2.5 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-200 group-hover:scale-105 transition shadow-2xs shrink-0">
                                                                            <Building2 className="w-5 h-5" />
                                                                        </div>
                                                                    ) : folder.folder_type === 'document_type' ? (
                                                                        <div className="p-2.5 rounded-xl bg-purple-50 text-purple-600 border border-purple-200 group-hover:scale-105 transition shadow-2xs shrink-0">
                                                                            <FileStack className="w-5 h-5" />
                                                                        </div>
                                                                    ) : (
                                                                        <div className="p-2.5 rounded-xl bg-amber-100 text-amber-600 border border-amber-200 group-hover:scale-105 transition shadow-2xs shrink-0">
                                                                            <FolderIcon className="w-5 h-5 fill-amber-500" />
                                                                        </div>
                                                                    )}
                                                                    <div className="min-w-0">
                                                                        <div className="flex items-center gap-1.5">
                                                                            <h3 className="text-xs font-bold text-slate-900 truncate group-hover:text-indigo-600 transition">
                                                                                {folder.name}
                                                                            </h3>
                                                                            {folder.folder_type === 'department' && (
                                                                                <Badge variant="info" size="sm" className="text-[9px] py-0 px-1.5">
                                                                                    Direction
                                                                                </Badge>
                                                                            )}
                                                                            {folder.folder_type === 'document_type' && (
                                                                                <Badge variant="purple" size="sm" className="text-[9px] py-0 px-1.5">
                                                                                    Type doc.
                                                                                </Badge>
                                                                            )}
                                                                        </div>
                                                                        <span className="text-[11px] text-slate-400">
                                                                            {folder.items_count} élément{folder.items_count > 1 ? 's' : ''}
                                                                        </span>
                                                                    </div>
                                                                </Link>

                                                                {/* Context Menu Button */}
                                                                <div className="relative">
                                                                    <button
                                                                        type="button"
                                                                        onClick={() =>
                                                                            setActiveDropdown(
                                                                                activeDropdown?.id === folder.id && activeDropdown?.type === 'folder'
                                                                                    ? null
                                                                                    : { type: 'folder', id: folder.id }
                                                                            )
                                                                        }
                                                                        className="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                                                                    >
                                                                        <MoreVertical className="w-4 h-4" />
                                                                    </button>

                                                                    {activeDropdown?.type === 'folder' && activeDropdown?.id === folder.id && (
                                                                        <div className="absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-30 text-xs">
                                                                            <Link
                                                                                href={`/folders/${folder.id}`}
                                                                                className="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition"
                                                                            >
                                                                                <Eye className="w-3.5 h-3.5" />
                                                                                <span>Ouvrir</span>
                                                                            </Link>
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => openEditModal(folder)}
                                                                                className="w-full flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 transition text-left"
                                                                            >
                                                                                <Edit2 className="w-3.5 h-3.5 text-slate-400" />
                                                                                <span>Renommer</span>
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => openMoveFolderModal(folder)}
                                                                                className="w-full flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 transition text-left"
                                                                            >
                                                                                <Move className="w-3.5 h-3.5 text-slate-400" />
                                                                                <span>Déplacer...</span>
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => {
                                                                                    setDeleteFolder(folder);
                                                                                    setActiveDropdown(null);
                                                                                }}
                                                                                className="w-full flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 transition text-left border-t border-slate-100"
                                                                            >
                                                                                <Trash2 className="w-3.5 h-3.5" />
                                                                                <span>Supprimer</span>
                                                                            </button>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        ) : (
                                            /* List Mode Folders */
                                            <div className="overflow-x-auto rounded-xl border border-slate-200">
                                                <table className="w-full text-left border-collapse text-xs">
                                                    <thead>
                                                        <tr className="bg-slate-50/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-200">
                                                            <th className="py-2.5 px-4">Nom</th>
                                                            <th className="py-2.5 px-4">Type</th>
                                                            <th className="py-2.5 px-4">Éléments</th>
                                                            <th className="py-2.5 px-4">Dernière modification</th>
                                                            <th className="py-2.5 px-4 text-right">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-slate-100">
                                                        {filteredSubfolders.map((folder) => {
                                                            const isOver = dragOverFolderId === folder.id;
                                                            return (
                                                                <tr
                                                                    key={folder.id}
                                                                    draggable
                                                                    onDragStart={(e) => handleDragStart(e, folder, 'folder')}
                                                                    onDragOver={(e) => handleDragOverFolder(e, folder.id)}
                                                                    onDragLeave={(e) => handleDragLeaveFolder(e, folder.id)}
                                                                    onDrop={(e) => handleDropOnFolder(e, folder)}
                                                                    className={`group transition ${
                                                                        isOver ? 'bg-indigo-50 font-bold' : 'hover:bg-slate-50/80'
                                                                    }`}
                                                                >
                                                                    <td className="py-2.5 px-4">
                                                                        <Link
                                                                            href={`/folders/${folder.id}`}
                                                                            className="flex items-center gap-2.5 text-slate-900 font-semibold group-hover:text-indigo-600"
                                                                        >
                                                                            {folder.folder_type === 'department' ? (
                                                                                <Building2 className="w-4 h-4 text-indigo-600 shrink-0" />
                                                                            ) : folder.folder_type === 'document_type' ? (
                                                                                <FileStack className="w-4 h-4 text-purple-600 shrink-0" />
                                                                            ) : (
                                                                                <FolderIcon className="w-4 h-4 text-amber-500 fill-amber-100 shrink-0" />
                                                                            )}
                                                                            <span>{folder.name}</span>
                                                                        </Link>
                                                                    </td>
                                                                    <td className="py-2.5 px-4 text-slate-500">
                                                                        {folder.folder_type === 'department' ? (
                                                                            <Badge variant="info" size="sm">Direction</Badge>
                                                                        ) : folder.folder_type === 'document_type' ? (
                                                                            <Badge variant="purple" size="sm">Type documentaire</Badge>
                                                                        ) : (
                                                                            <span>Dossier</span>
                                                                        )}
                                                                    </td>
                                                                    <td className="py-2.5 px-4 text-slate-500">
                                                                        {folder.items_count} élément{folder.items_count > 1 ? 's' : ''}
                                                                    </td>
                                                                    <td className="py-2.5 px-4 text-slate-400 text-[11px]">
                                                                        {folder.updated_at
                                                                            ? new Date(folder.updated_at).toLocaleDateString('fr-FR')
                                                                            : '—'}
                                                                    </td>
                                                                    <td className="py-2.5 px-4 text-right">
                                                                        <div className="flex items-center justify-end gap-1">
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => openEditModal(folder)}
                                                                                title="Renommer"
                                                                                className="p-1 rounded text-slate-400 hover:text-slate-700"
                                                                            >
                                                                                <Edit2 className="w-3.5 h-3.5" />
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => openMoveFolderModal(folder)}
                                                                                title="Déplacer"
                                                                                className="p-1 rounded text-slate-400 hover:text-slate-700"
                                                                            >
                                                                                <Move className="w-3.5 h-3.5" />
                                                                            </button>
                                                                            <button
                                                                                type="button"
                                                                                onClick={() => setDeleteFolder(folder)}
                                                                                title="Supprimer"
                                                                                className="p-1 rounded text-slate-400 hover:text-rose-600"
                                                                            >
                                                                                <Trash2 className="w-3.5 h-3.5" />
                                                                            </button>
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            );
                                                        })}
                                                    </tbody>
                                                </table>
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* SECTION 2: DOCUMENTS (FICHIERS DANS CE DOSSIER) */}
                                {filteredDocuments.length > 0 && (
                                    <div className="space-y-3 pt-2">
                                        <div className="flex items-center justify-between pb-1 border-b border-slate-100">
                                            <h2 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                                                <FileText className="w-3.5 h-3.5 text-indigo-600" />
                                                <span>Documents ({filteredDocuments.length})</span>
                                            </h2>
                                            <span className="text-[11px] text-slate-400">
                                                Glissez un document dans un dossier pour le déplacer
                                            </span>
                                        </div>

                                        {viewMode === 'grid' ? (
                                            /* Grid Mode Documents */
                                            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3">
                                                {filteredDocuments.map((doc) => (
                                                    <div
                                                        key={doc.id}
                                                        draggable
                                                        onDragStart={(e) => handleDragStart(e, doc, 'document')}
                                                        className="group relative rounded-xl border border-slate-200/80 bg-white p-3.5 shadow-2xs hover:shadow-md hover:border-indigo-300 transition flex flex-col justify-between select-none"
                                                    >
                                                        <div className="flex items-start justify-between gap-2 mb-2">
                                                            <Link
                                                                href={`/documents/${doc.id}`}
                                                                className="flex items-start gap-3 flex-1 min-w-0"
                                                            >
                                                                <div className="p-2 rounded-xl bg-slate-50 border border-slate-100 group-hover:scale-105 transition shrink-0">
                                                                    <FileIcon
                                                                        mimeType={doc.mime_type}
                                                                        extension={doc.extension}
                                                                        className="w-6 h-6"
                                                                    />
                                                                </div>
                                                                <div className="min-w-0">
                                                                    <h4 className="text-xs font-semibold text-slate-900 truncate group-hover:text-indigo-600 transition">
                                                                        {doc.name}
                                                                    </h4>
                                                                    <div className="flex items-center gap-2 mt-0.5 text-[11px] text-slate-400">
                                                                        <span>{doc.size_human}</span>
                                                                        {doc.category && (
                                                                            <span className="truncate max-w-[80px]">
                                                                                • {doc.category}
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </Link>

                                                            {/* Context Dropdown */}
                                                            <div className="relative">
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        setActiveDropdown(
                                                                            activeDropdown?.id === doc.id && activeDropdown?.type === 'doc'
                                                                                ? null
                                                                                : { type: 'doc', id: doc.id }
                                                                        )
                                                                    }
                                                                    className="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition"
                                                                >
                                                                    <MoreVertical className="w-4 h-4" />
                                                                </button>

                                                                {activeDropdown?.type === 'doc' && activeDropdown?.id === doc.id && (
                                                                    <div className="absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-slate-200 py-1 z-30 text-xs">
                                                                        <Link
                                                                            href={`/documents/${doc.id}`}
                                                                            className="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition"
                                                                        >
                                                                            <Eye className="w-3.5 h-3.5" />
                                                                            <span>Consulter</span>
                                                                        </Link>
                                                                        <a
                                                                            href={`/documents/${doc.id}/download`}
                                                                            className="flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 transition"
                                                                        >
                                                                            <Download className="w-3.5 h-3.5 text-slate-400" />
                                                                            <span>Télécharger</span>
                                                                        </a>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => openMoveDocumentModal(doc)}
                                                                            className="w-full flex items-center gap-2 px-3 py-2 text-slate-700 hover:bg-slate-50 transition text-left"
                                                                        >
                                                                            <Move className="w-3.5 h-3.5 text-slate-400" />
                                                                            <span>Déplacer...</span>
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => {
                                                                                setDeleteDoc(doc);
                                                                                setActiveDropdown(null);
                                                                            }}
                                                                            className="w-full flex items-center gap-2 px-3 py-2 text-rose-600 hover:bg-rose-50 transition text-left border-t border-slate-100"
                                                                        >
                                                                            <Trash2 className="w-3.5 h-3.5" />
                                                                            <span>Supprimer</span>
                                                                        </button>
                                                                    </div>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        ) : (
                                            /* List Mode Documents */
                                            <div className="overflow-x-auto rounded-xl border border-slate-200">
                                                <table className="w-full text-left border-collapse text-xs">
                                                    <thead>
                                                        <tr className="bg-slate-50/80 text-[11px] font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-200">
                                                            <th className="py-2.5 px-4">Nom</th>
                                                            <th className="py-2.5 px-4">Type</th>
                                                            <th className="py-2.5 px-4">Taille</th>
                                                            <th className="py-2.5 px-4">Catégorie</th>
                                                            <th className="py-2.5 px-4">Date</th>
                                                            <th className="py-2.5 px-4 text-right">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-slate-100">
                                                        {filteredDocuments.map((doc) => (
                                                            <tr
                                                                key={doc.id}
                                                                draggable
                                                                onDragStart={(e) => handleDragStart(e, doc, 'document')}
                                                                className="hover:bg-slate-50/80 transition group"
                                                            >
                                                                <td className="py-2.5 px-4">
                                                                    <Link
                                                                        href={`/documents/${doc.id}`}
                                                                        className="flex items-center gap-2.5 text-slate-900 font-semibold group-hover:text-indigo-600"
                                                                    >
                                                                        <FileIcon
                                                                            mimeType={doc.mime_type}
                                                                            extension={doc.extension}
                                                                            className="w-4 h-4 shrink-0"
                                                                        />
                                                                        <span className="truncate max-w-xs">{doc.name}</span>
                                                                    </Link>
                                                                </td>
                                                                <td className="py-2.5 px-4 text-slate-500 uppercase font-mono text-[11px]">
                                                                    {doc.extension || 'FILE'}
                                                                </td>
                                                                <td className="py-2.5 px-4 text-slate-500">{doc.size_human}</td>
                                                                <td className="py-2.5 px-4 text-slate-500">
                                                                    {doc.category || '—'}
                                                                </td>
                                                                <td className="py-2.5 px-4 text-slate-400 text-[11px]">
                                                                    {doc.updated_at
                                                                        ? new Date(doc.updated_at).toLocaleDateString('fr-FR')
                                                                        : '—'}
                                                                </td>
                                                                <td className="py-2.5 px-4 text-right">
                                                                    <div className="flex items-center justify-end gap-1">
                                                                        <Link
                                                                            href={`/documents/${doc.id}`}
                                                                            title="Consulter"
                                                                            className="p-1 rounded text-slate-400 hover:text-indigo-600"
                                                                        >
                                                                            <Eye className="w-3.5 h-3.5" />
                                                                        </Link>
                                                                        <a
                                                                            href={`/documents/${doc.id}/download`}
                                                                            title="Télécharger"
                                                                            className="p-1 rounded text-slate-400 hover:text-slate-700"
                                                                        >
                                                                            <Download className="w-3.5 h-3.5" />
                                                                        </a>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => openMoveDocumentModal(doc)}
                                                                            title="Déplacer"
                                                                            className="p-1 rounded text-slate-400 hover:text-slate-700"
                                                                        >
                                                                            <Move className="w-3.5 h-3.5" />
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            onClick={() => setDeleteDoc(doc)}
                                                                            title="Supprimer"
                                                                            className="p-1 rounded text-slate-400 hover:text-rose-600"
                                                                        >
                                                                            <Trash2 className="w-3.5 h-3.5" />
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* 3. MODALE: Créer un dossier */}
            <Modal
                isOpen={createFolderModalOpen}
                onClose={() => setCreateFolderModalOpen(false)}
                title={currentFolder ? `Nouveau sous-dossier dans "${currentFolder.name}"` : 'Nouveau dossier racine'}
            >
                <form onSubmit={handleCreateFolder} className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Type d'élément
                        </label>
                        <select
                            value={createForm.data.folder_type || 'standard'}
                            onChange={(e) => createForm.setData('folder_type', e.target.value)}
                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white"
                        >
                            <option value="standard">Dossier standard</option>
                            {(!currentFolder || isRoot) && can.create_department && (
                                <option value="department">Direction (Niveau 1)</option>
                            )}
                            {(currentFolder?.folder_type === 'department' || can.create_document_type) && (
                                <option value="document_type">Type documentaire</option>
                            )}
                        </select>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Nom du dossier <span className="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            required
                            value={createForm.data.name}
                            onChange={(e) => createForm.setData('name', e.target.value)}
                            placeholder="Ex: Factures 2026, RH, Contrats..."
                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                        {createForm.errors.name && (
                            <p className="text-[11px] text-rose-500 mt-1">{createForm.errors.name}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Description
                        </label>
                        <textarea
                            rows={3}
                            value={createForm.data.description}
                            onChange={(e) => createForm.setData('description', e.target.value)}
                            placeholder="Description facultative du contenu..."
                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            variant="secondary"
                            type="button"
                            size="md"
                            onClick={() => setCreateFolderModalOpen(false)}
                        >
                            Annuler
                        </Button>
                        <Button variant="primary" size="md" type="submit" loading={createForm.processing}>
                            Créer le dossier
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* 4. MODALE: Renommer / Modifier un dossier */}
            <Modal
                isOpen={Boolean(editFolder)}
                onClose={() => setEditFolder(null)}
                title="Modifier le dossier"
            >
                <form onSubmit={handleEditFolder} className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Nom du dossier <span className="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            required
                            value={editForm.data.name}
                            onChange={(e) => editForm.setData('name', e.target.value)}
                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                        {editForm.errors.name && (
                            <p className="text-[11px] text-rose-500 mt-1">{editForm.errors.name}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Description
                        </label>
                        <textarea
                            rows={3}
                            value={editForm.data.description}
                            onChange={(e) => editForm.setData('description', e.target.value)}
                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            variant="secondary"
                            type="button"
                            size="md"
                            onClick={() => setEditFolder(null)}
                        >
                            Annuler
                        </Button>
                        <Button variant="primary" size="md" type="submit" loading={editForm.processing}>
                            Enregistrer
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* 5. MODALE: Déplacer un dossier */}
            <Modal
                isOpen={Boolean(moveFolder)}
                onClose={() => setMoveFolder(null)}
                title={`Déplacer le dossier "${moveFolder?.name}"`}
            >
                <form onSubmit={handleMoveFolderSubmit} className="space-y-4">
                    <p className="text-xs text-slate-500">
                        Choisissez le dossier de destination. Le dossier ne peut pas être déplacé dans lui-même ou l'un de ses descendants.
                    </p>

                    <div className="max-h-60 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1 bg-slate-50/50">
                        {/* Root option */}
                        <label
                            className={`flex items-center gap-2 p-2 rounded-lg cursor-pointer text-xs transition ${
                                moveFolderForm.data.parent_id === '' || moveFolderForm.data.parent_id === null
                                    ? 'bg-indigo-50 text-indigo-900 font-bold border border-indigo-200'
                                    : 'hover:bg-white text-slate-700'
                            }`}
                        >
                            <input
                                type="radio"
                                name="destination_folder"
                                value=""
                                checked={moveFolderForm.data.parent_id === '' || moveFolderForm.data.parent_id === null}
                                onChange={() => moveFolderForm.setData('parent_id', '')}
                                className="text-indigo-600 focus:ring-indigo-500"
                            />
                            <HardDrive className="w-4 h-4 text-slate-500" />
                            <span>Racine (Mon espace)</span>
                        </label>

                        {/* List available folders excluding invalid ones */}
                        {tree
                            .filter((item) => !invalidDestinationFolderIds.has(item.id))
                            .map((item) => {
                                const isSelected = (moveFolderForm.data.parent_id || '') === item.id;
                                return (
                                    <label
                                        key={item.id}
                                        className={`flex items-center gap-2 p-2 rounded-lg cursor-pointer text-xs transition ${
                                            isSelected
                                                ? 'bg-indigo-50 text-indigo-900 font-bold border border-indigo-200'
                                                : 'hover:bg-white text-slate-700'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="destination_folder"
                                            value={item.id}
                                            checked={isSelected}
                                            onChange={() => moveFolderForm.setData('parent_id', item.id)}
                                            className="text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <FolderIcon className="w-4 h-4 text-amber-500 fill-amber-100" />
                                        <span>{item.name}</span>
                                    </label>
                                );
                            })}
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            variant="secondary"
                            type="button"
                            size="md"
                            onClick={() => setMoveFolder(null)}
                        >
                            Annuler
                        </Button>
                        <Button variant="primary" size="md" type="submit" loading={moveFolderForm.processing}>
                            Déplacer ici
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* 6. MODALE: Déplacer un document */}
            <Modal
                isOpen={Boolean(moveDocument)}
                onClose={() => setMoveDocument(null)}
                title={`Déplacer le document "${moveDocument?.name}"`}
            >
                <form onSubmit={handleMoveDocumentSubmit} className="space-y-4">
                    <p className="text-xs text-slate-500">
                        Sélectionnez le dossier de destination pour ce document :
                    </p>

                    <div className="max-h-60 overflow-y-auto border border-slate-200 rounded-xl p-2 space-y-1 bg-slate-50/50">
                        {/* Root option */}
                        <label
                            className={`flex items-center gap-2 p-2 rounded-lg cursor-pointer text-xs transition ${
                                !moveDocForm.data.folder_id
                                    ? 'bg-indigo-50 text-indigo-900 font-bold border border-indigo-200'
                                    : 'hover:bg-white text-slate-700'
                            }`}
                        >
                            <input
                                type="radio"
                                name="doc_destination_folder"
                                value=""
                                checked={!moveDocForm.data.folder_id}
                                onChange={() => moveDocForm.setData('folder_id', '')}
                                className="text-indigo-600 focus:ring-indigo-500"
                            />
                            <HardDrive className="w-4 h-4 text-slate-500" />
                            <span>Racine (Mon espace)</span>
                        </label>

                        {tree.map((item) => {
                            const isSelected = (moveDocForm.data.folder_id || '') === item.id;
                            return (
                                <label
                                    key={item.id}
                                    className={`flex items-center gap-2 p-2 rounded-lg cursor-pointer text-xs transition ${
                                        isSelected
                                            ? 'bg-indigo-50 text-indigo-900 font-bold border border-indigo-200'
                                            : 'hover:bg-white text-slate-700'
                                    }`}
                                >
                                    <input
                                        type="radio"
                                        name="doc_destination_folder"
                                        value={item.id}
                                        checked={isSelected}
                                        onChange={() => moveDocForm.setData('folder_id', item.id)}
                                        className="text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <FolderIcon className="w-4 h-4 text-amber-500 fill-amber-100" />
                                    <span>{item.name}</span>
                                </label>
                            );
                        })}
                    </div>

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            variant="secondary"
                            type="button"
                            size="md"
                            onClick={() => setMoveDocument(null)}
                        >
                            Annuler
                        </Button>
                        <Button variant="primary" size="md" type="submit" loading={moveDocForm.processing}>
                            Déplacer ici
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* 7. MODALE: Téléverser un document directement dans ce dossier */}
            <Modal
                isOpen={uploadModalOpen}
                onClose={() => setUploadModalOpen(false)}
                title={currentFolder ? `Ajouter un document dans "${currentFolder.name}"` : 'Ajouter un document'}
            >
                <form onSubmit={handleUploadSubmit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Fichier à importer <span className="text-rose-500">*</span>
                        </label>
                        <input
                            type="file"
                            required
                            onChange={(e) => {
                                const file = e.target.files[0];
                                uploadForm.setData('file', file);
                                if (file && !uploadForm.data.name) {
                                    uploadForm.setData('name', file.name.replace(/\.[^/.]+$/, ''));
                                }
                            }}
                            className="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                        />
                        {uploadForm.errors.file && (
                            <p className="text-[11px] text-rose-500 mt-1">{uploadForm.errors.file}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Titre du document
                        </label>
                        <input
                            type="text"
                            value={uploadForm.data.name}
                            onChange={(e) => uploadForm.setData('name', e.target.value)}
                            placeholder="Nom personnalisé ou nom du fichier par défaut"
                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Description (facultative)
                        </label>
                        <textarea
                            rows={2}
                            value={uploadForm.data.description}
                            onChange={(e) => uploadForm.setData('description', e.target.value)}
                            className="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                        />
                    </div>

                    {metadataDefinitions && metadataDefinitions.length > 0 && (
                        <div className="pt-3 border-t border-slate-200 space-y-3">
                            <div className="flex items-center justify-between">
                                <span className="text-xs font-bold uppercase tracking-wider text-slate-500">
                                    Métadonnées associées
                                </span>
                                <span className="text-[11px] text-slate-400">
                                    {currentFolder?.name}
                                </span>
                            </div>
                            <div className="space-y-2.5 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                {metadataDefinitions.map((def) => (
                                    <div key={def.id}>
                                        <label className="block text-xs font-medium text-slate-700 mb-1">
                                            {def.name}
                                            {def.is_required && <span className="text-rose-500 ml-0.5">*</span>}
                                        </label>
                                        {def.type === 'boolean' ? (
                                            <select
                                                value={uploadForm.data.metadata[def.id] ?? ''}
                                                onChange={(e) => handleMetadataChange(def.id, e.target.value)}
                                                className="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                            >
                                                <option value="">-- Non spécifié --</option>
                                                <option value="1">Oui</option>
                                                <option value="0">Non</option>
                                            </select>
                                        ) : def.type === 'date' ? (
                                            <input
                                                type="date"
                                                required={def.is_required}
                                                value={uploadForm.data.metadata[def.id] ?? ''}
                                                onChange={(e) => handleMetadataChange(def.id, e.target.value)}
                                                className="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                            />
                                        ) : (def.type === 'integer' || def.type === 'float') ? (
                                            <input
                                                type="number"
                                                step={def.type === 'float' ? '0.01' : '1'}
                                                required={def.is_required}
                                                value={uploadForm.data.metadata[def.id] ?? ''}
                                                onChange={(e) => handleMetadataChange(def.id, e.target.value)}
                                                className="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                            />
                                        ) : (
                                            <input
                                                type="text"
                                                required={def.is_required}
                                                placeholder={def.description || `Entrez ${def.name.toLowerCase()}`}
                                                value={uploadForm.data.metadata[def.id] ?? ''}
                                                onChange={(e) => handleMetadataChange(def.id, e.target.value)}
                                                className="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500"
                                            />
                                        )}
                                        {def.description && (
                                            <p className="text-[10px] text-slate-400 mt-0.5">{def.description}</p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="flex justify-end gap-2 pt-2">
                        <Button
                            variant="secondary"
                            type="button"
                            size="md"
                            onClick={() => setUploadModalOpen(false)}
                        >
                            Annuler
                        </Button>
                        <Button variant="primary" size="md" type="submit" loading={uploadForm.processing}>
                            Importer
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* 8. Dialogues de confirmation */}
            <ConfirmDialog
                isOpen={Boolean(deleteFolder)}
                onClose={() => setDeleteFolder(null)}
                onConfirm={handleDeleteFolder}
                title="Supprimer le dossier"
                message={`Êtes-vous sûr de vouloir supprimer le dossier "${deleteFolder?.name}" ? Ses documents seront déplacés vers la corbeille.`}
                confirmLabel="Supprimer"
                variant="danger"
            />

            <ConfirmDialog
                isOpen={Boolean(deleteDoc)}
                onClose={() => setDeleteDoc(null)}
                onConfirm={handleDeleteDocument}
                title="Supprimer le document"
                message={`Êtes-vous sûr de vouloir déplacer "${deleteDoc?.name}" vers la corbeille ?`}
                confirmLabel="Mettre à la corbeille"
                variant="danger"
            />
        </AuthenticatedLayout>
    );
}
