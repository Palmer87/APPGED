import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import {
    Folder,
    Users,
    Building2,
    BarChart3,
    PieChart,
    Upload,
    Search,
    FilePlus,
    UserPlus,
    ArrowRight,
    HardDrive,
    CheckCircle2,
    Clock,
    FileText,
    FileSpreadsheet,
    Share2,
    MessageSquare,
    ChevronDown,
    ExternalLink,
    AlertCircle,
    Check,
    Eye,
    Download,
    MoreHorizontal,
    Sparkles,
    X
} from 'lucide-react';

export default function DashboardIndex({
    user = {},
    organization = {},
    workspace = {},
    period = '30d',
    statistics = {},
    recent_documents = [],
    favorites = [],
    workflows = { pending_my_action: [], in_progress_count: 0 },
    notifications = { unread_count: 0, recent: [] },
    recent_activity = [],
    charts = {},
    tasks: initialTasks = [],
    billing = null,
    flash: propsFlash = {}
}) {
    const pageProps = usePage()?.props || {};
    const flash = (propsFlash && Object.keys(propsFlash).length > 0) ? propsFlash : (pageProps.flash || {});
    const [dismissWelcome, setDismissWelcome] = useState(false);

    const formatNumber = (val) => new Intl.NumberFormat('fr-FR').format(val || 0);

    // Live Clock & Formatted Date
    const [currentTime, setCurrentTime] = useState('');
    const [currentDateFormatted, setCurrentDateFormatted] = useState('');

    useEffect(() => {
        const updateTime = () => {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            setCurrentTime(`${hours}:${minutes}`);

            const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            const formatted = now.toLocaleDateString('fr-FR', options);
            setCurrentDateFormatted(formatted.charAt(0).toUpperCase() + formatted.slice(1));
        };

        updateTime();
        const timer = setInterval(updateTime, 30000);
        return () => clearInterval(timer);
    }, []);

    // Filter period dropdown state for charts
    const [activityPeriodOpen, setActivityPeriodOpen] = useState(false);
    const [activityPeriod, setActivityPeriod] = useState('Cette année');
    const [directionPeriodOpen, setDirectionPeriodOpen] = useState(false);
    const [directionPeriod, setDirectionPeriod] = useState('Cette année');

    const handleSelectActivityPeriod = (pKey, pLabel) => {
        setActivityPeriod(pLabel);
        setActivityPeriodOpen(false);
        router.get('/dashboard', { period: pKey }, { preserveScroll: true, preserveState: true });
    };

    // Interactive tasks state
    const defaultTasks = [
        { id: 1, title: 'Valider 3 documents en attente', urgent: true, completed: false, link: '/workflows' },
        { id: 2, title: 'Classer les documents RH', urgent: false, completed: false, link: '/documents' },
        { id: 3, title: 'Mettre à jour les métadonnées', urgent: false, completed: false, link: '/metadata' },
        { id: 4, title: 'Former l\'équipe comptabilité', urgent: false, completed: false, link: '/departments' },
        { id: 5, title: 'Vérifier l\'espace de stockage', urgent: false, completed: false, link: '/settings' },
    ];
    const [taskList, setTaskList] = useState(initialTasks && initialTasks.length > 0 ? initialTasks : defaultTasks);

    const toggleTask = (taskId) => {
        setTaskList((prev) =>
            prev.map((t) => (t.id === taskId ? { ...t, completed: !t.completed } : t))
        );
    };

    // 12-Month Bar Chart data (Documents ajoutés & Documents consultés)
    const monthlyData = (charts.monthly_activity && charts.monthly_activity.length > 0) ? charts.monthly_activity : [
        { month: 'Jan', added: 38, viewed: 65 },
        { month: 'Fév', added: 52, viewed: 88 },
        { month: 'Mar', added: 45, viewed: 74 },
        { month: 'Avr', added: 60, viewed: 95 },
        { month: 'Mai', added: 72, viewed: 110 },
        { month: 'Juin', added: 85, viewed: 125 },
        { month: 'Juil', added: 68, viewed: 98 },
        { month: 'Août', added: 55, viewed: 82 },
        { month: 'Sept', added: 95, viewed: 140 },
        { month: 'Oct', added: 110, viewed: 155 },
        { month: 'Nov', added: 125, viewed: 170 },
        { month: 'Déc', added: 140, viewed: 195 },
    ];

    // Direction distribution donut chart data
    const directionData = (charts.by_direction && charts.by_direction.length > 0) ? charts.by_direction : [
        { name: 'Comptabilité', percentage: 32, color: '#2563eb', count: 399 },
        { name: 'Ressources Humaines', percentage: 22, color: '#8b5cf6', count: 275 },
        { name: 'Commercial', percentage: 18, color: '#06b6d4', count: 225 },
        { name: 'Juridique', percentage: 12, color: '#f97316', count: 150 },
        { name: 'Administration', percentage: 10, color: '#eab308', count: 124 },
        { name: 'Autres', percentage: 6, color: '#64748b', count: 75 },
    ];

    // Calculate Donut Segments
    let cumulativePercent = 0;
    const donutSegments = directionData.map((slice) => {
        const start = cumulativePercent;
        cumulativePercent += slice.percentage;
        return {
            ...slice,
            start,
            end: cumulativePercent,
        };
    });

    // Documents list fallback to mockup if empty
    const displayDocuments = (recent_documents && recent_documents.length > 0) ? recent_documents : [
        {
            id: 1,
            name: 'FAC-2024-001.pdf',
            type_name: 'Facture client',
            department_name: 'Comptabilité',
            created_at_formatted: '24/09/2024 09:42',
            uploader_name: 'Koffi Abalo',
            extension: 'pdf',
        },
        {
            id: 2,
            name: 'Contrat_Diakite.pdf',
            type_name: 'Contrat de travail',
            department_name: 'Ressources Humaines',
            created_at_formatted: '23/09/2024 16:21',
            uploader_name: 'Aminata Koné',
            extension: 'docx',
        },
        {
            id: 3,
            name: 'Devis-2024-058.pdf',
            type_name: 'Devis',
            department_name: 'Commercial',
            created_at_formatted: '22/09/2024 11:15',
            uploader_name: 'Jean Traoré',
            extension: 'xlsx',
        },
        {
            id: 4,
            name: 'Statuts_Société.pdf',
            type_name: 'Document légal',
            department_name: 'Juridique',
            created_at_formatted: '20/09/2024 14:33',
            uploader_name: 'Sophie Diallo',
            extension: 'pdf',
        },
        {
            id: 5,
            name: 'Note de service.pdf',
            type_name: 'Note de service',
            department_name: 'Administration',
            created_at_formatted: '19/09/2024 08:12',
            uploader_name: 'Koffi Abalo',
            extension: 'pdf',
        }
    ];

    // Activity feed fallback to mockup if empty
    const displayActivity = (recent_activity && recent_activity.length > 0) ? recent_activity.slice(0, 5) : [
        {
            id: 1,
            author: 'Koffi Abalo',
            text: 'a importé un document',
            target: 'FAC-2024-001.pdf',
            time: 'Il y a 5 minutes',
            icon: Upload,
            color: 'bg-blue-600 text-white'
        },
        {
            id: 2,
            author: 'Aminata Koné',
            text: 'a modifié un document',
            target: 'Contrat_Diakite.pdf',
            time: 'Il y a 23 minutes',
            icon: FileText,
            color: 'bg-amber-500 text-white'
        },
        {
            id: 3,
            author: 'Jean Traoré',
            text: 'a partagé un document',
            target: 'Devis-2024-058.pdf',
            time: 'Il y a 1 heure',
            icon: Share2,
            color: 'bg-emerald-500 text-white'
        },
        {
            id: 4,
            author: 'Sophie Diallo',
            text: 'a commenté',
            target: 'Statuts_Société.pdf',
            time: 'Il y a 2 heures',
            icon: MessageSquare,
            color: 'bg-purple-500 text-white'
        },
        {
            id: 5,
            author: 'Nouveau type créé',
            text: '',
            target: 'Attestation',
            time: 'Il y a 3 heures',
            icon: FilePlus,
            color: 'bg-indigo-600 text-white'
        }
    ];

    // Helper for file format badge
    const renderFormatBadge = (ext, name = '') => {
        const lower = (ext || name.split('.').pop() || 'pdf').toLowerCase();
        if (lower.includes('xls') || lower.includes('csv') || lower.includes('sheet')) {
            return (
                <div className="w-7 h-7 rounded-lg bg-emerald-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0 shadow-2xs">
                    X
                </div>
            );
        }
        if (lower.includes('doc') || lower.includes('word')) {
            return (
                <div className="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0 shadow-2xs">
                    W
                </div>
            );
        }
        if (lower.includes('png') || lower.includes('jpg') || lower.includes('jpeg') || lower.includes('webp')) {
            return (
                <div className="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-[10px] font-bold text-white shrink-0 shadow-2xs">
                    IMG
                </div>
            );
        }
        return (
            <div className="w-7 h-7 rounded-lg bg-rose-600 flex items-center justify-center text-[9px] font-bold text-white shrink-0 shadow-2xs tracking-tighter">
                PDF
            </div>
        );
    };

    const userName = user.first_name || (user.name ? user.name.split(' ')[0] : 'Koffi');

    return (
        <AuthenticatedLayout title="Tableau de bord">
            <Head title="Tableau de bord - GEDAPP" />

            <div className="space-y-6">
                {/* Contextual Space Banner (Architecture Organisationnelle V2 - Section 16) */}
                <div className="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-2xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-md shadow-blue-500/20 shrink-0">
                            {user.first_name ? user.first_name[0] : (user.name ? user.name[0] : 'U')}
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                    Bonjour {user.first_name || user.name || 'Collaborateur'} 👋
                                </h1>
                            </div>
                            <div className="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500">
                                <span>Votre espace :</span>
                                {workspace.direction_name || workspace.service_name ? (
                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 font-semibold border border-blue-200/60">
                                        {workspace.direction_name && <span>{workspace.direction_name}</span>}
                                        {workspace.direction_name && workspace.service_name && <span>→</span>}
                                        {workspace.service_name && <span>{workspace.service_name}</span>}
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 font-medium">
                                        {organization?.name || 'Organisation'}
                                    </span>
                                )}
                                {workspace.frequent_document_types && workspace.frequent_document_types.length > 0 && (
                                    <span className="hidden lg:inline-flex items-center gap-1.5 text-slate-400 pl-2 border-l border-slate-200">
                                        Types fréquents : {workspace.frequent_document_types.map(t => t.name).join(', ')}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-2 sm:gap-4 shrink-0">
                        <div className="px-3 py-2 rounded-2xl bg-slate-50 border border-slate-100 text-center">
                            <span className="text-[10px] text-slate-400 font-medium block">Accessibles</span>
                            <strong className="text-sm font-bold text-slate-800">{statistics?.total_documents ?? 0}</strong>
                        </div>
                        <div className="px-3 py-2 rounded-2xl bg-slate-50 border border-slate-100 text-center">
                            <span className="text-[10px] text-slate-400 font-medium block">Récents</span>
                            <strong className="text-sm font-bold text-blue-600">{recent_documents?.length ?? 0}</strong>
                        </div>
                        <div className="px-3 py-2 rounded-2xl bg-amber-50 border border-amber-100 text-center">
                            <span className="text-[10px] text-amber-700 font-medium block">À traiter</span>
                            <strong className="text-sm font-bold text-amber-800">{workflows?.pending_my_action?.length ?? 0}</strong>
                        </div>
                    </div>
                </div>

                {/* Welcome Onboarding Banner */}
                {!dismissWelcome && flash?.welcome_onboarding && (
                    <div className="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden animate-in fade-in duration-300">
                        <button
                            type="button"
                            onClick={() => setDismissWelcome(true)}
                            className="absolute top-4 right-4 p-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-slate-300 hover:text-white transition cursor-pointer"
                            aria-label="Fermer"
                        >
                            <X className="w-4 h-4" />
                        </button>
                        <div className="absolute top-0 right-0 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl pointer-events-none" />
                        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                            <div className="space-y-3 w-full">
                                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-200 text-xs font-semibold">
                                    <Sparkles className="w-3.5 h-3.5 text-blue-300" />
                                    <span>Nouvel espace initialisé</span>
                                </div>
                                <h2 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
                                    Bienvenue sur GEDAPP 👋
                                </h2>
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2">
                                    <div className="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10">
                                        <span className="text-[11px] text-slate-300 block">Votre organisation :</span>
                                        <strong className="text-sm font-bold text-white truncate block">
                                            {flash.welcome_onboarding.organization_name || organization?.name}
                                        </strong>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10">
                                        <span className="text-[11px] text-slate-300 block">Administrateur :</span>
                                        <strong className="text-sm font-bold text-white truncate block">
                                            {flash.welcome_onboarding.admin_name || user?.name}
                                        </strong>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10">
                                        <span className="text-[11px] text-slate-300 block">Plan :</span>
                                        <strong className="text-sm font-bold text-white truncate block">
                                            {flash.welcome_onboarding.plan_name}
                                        </strong>
                                    </div>
                                    <div className="bg-white/10 backdrop-blur-md rounded-2xl p-3 border border-white/10">
                                        <span className="text-[11px] text-slate-300 block">Essai :</span>
                                        <strong className="text-sm font-bold text-emerald-300 truncate block">
                                            {flash.welcome_onboarding.trial_days || 14} jours restants
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* Trial Alert Banner if currently trialing */}
                {billing?.is_trial && billing.trial_days_remaining !== undefined && (
                    <div className={`p-4 rounded-2xl border flex items-center justify-between gap-4 ${
                        billing.trial_days_remaining <= 3
                            ? 'bg-amber-50 border-amber-200 text-amber-900'
                            : 'bg-blue-50/80 border-blue-200/80 text-blue-900'
                    }`}>
                        <div className="flex items-center gap-3">
                            <Clock className={`w-5 h-5 shrink-0 ${
                                billing.trial_days_remaining <= 3 ? 'text-amber-600' : 'text-blue-600'
                            }`} />
                            <div className="text-xs">
                                <span className="font-bold">
                                    {billing.trial_days_remaining > 0
                                        ? `Votre période d'essai se termine dans ${billing.trial_days_remaining} jour(s).`
                                        : "Votre période d'essai gratuit a expiré."}
                                </span>
                                <span className="opacity-80 ml-1.5 hidden sm:inline">
                                    Choisissez votre abonnement pour débloquer toutes les fonctionnalités sans interruption.
                                </span>
                            </div>
                        </div>
                        <Link
                            href="/subscription/choose"
                            className="shrink-0 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white shadow-xs transition"
                        >
                            Changer de plan
                        </Link>
                    </div>
                )}

                {/* 2-Column Responsive Layout: Main Area (Col 1) & Right Sidebar (Col 2) */}
                <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                    
                    {/* LEFT / CENTER CONTENT AREA (8 Cols on XL) */}
                    <div className="xl:col-span-8 flex flex-col gap-6">
                          {/* 4. FOUR QUICK ACTION CARDS (Placées sous les graphiques, selon la maquette) */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            {/* Action 1: Importer un document */}
                            <Link
                                href="/documents/create"
                                className="bg-white rounded-2xl p-4 transition flex items-center gap-3.5 group"
                            >
                                <div className="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center group-hover:scale-105 transition-transform">
                                    <Upload className="w-5 h-5" />
                                </div>
                                <div className="min-w-0">
                                    <h3 className="text-xs sm:text-sm font-bold text-slate-900">
                                        Importer un document
                                    </h3>
                                    <p className="text-[11px] text-slate-400 truncate mt-0.5">
                                        Ajouter un nouveau document
                                    </p>
                                </div>
                            </Link>

                            {/* Action 2: Rechercher */}
                            <Link
                                href="/search"
                                className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-md hover:border-sky-300 transition flex items-center gap-3.5 group"
                            >
                                <div className="w-11 h-11 rounded-xl bg-sky-500 text-white flex items-center justify-center group-hover:scale-105 transition-transform">
                                    <Search className="w-5 h-5" />
                                </div>
                                <div className="min-w-0">
                                    <h3 className="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-sky-600 transition-colors">
                                        Rechercher
                                    </h3>
                                    <p className="text-[11px] text-slate-400 truncate mt-0.5">
                                        Trouver un document
                                    </p>
                                </div>
                            </Link>

                            {/* Action 3: Nouveau type */}
                            <Link
                                href="/document-types"
                                className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs hover:shadow-md hover:border-purple-300 transition flex items-center gap-3.5 group"
                            >
                                <div className="w-11 h-11 rounded-xl bg-purple-600 text-white flex items-center justify-center -purple-500/20 group-hover:scale-105 transition-transform">
                                    <FilePlus className="w-5 h-5" />
                                </div>
                                <div className="min-w-0">
                                    <h3 className="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-purple-600 transition-colors">
                                        Nouveau type
                                    </h3>
                                    <p className="text-[11px] text-slate-400 truncate mt-0.5">
                                        Créer un type documentaire
                                    </p>
                                </div>
                            </Link>

                            {/* Action 4: Gérer les utilisateurs */}
                            <Link
                                href="/users"
                                className="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-2xs transition flex items-center gap-3.5 group"
                            >
                                <div className="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center group-hover:scale-105 transition-transform">
                                    <Users className="w-5 h-5" />
                                </div>
                                <div className="min-w-0">
                                    <h3 className="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-emerald-600 transition-colors">
                                        Gérer les utilisateurs
                                    </h3>
                                    <p className="text-[11px] text-slate-400 truncate mt-0.5">
                                        Ajouter ou modifier des utilisateurs
                                    </p>
                                </div>
                            </Link>
                        </div>

                        {/* 2. FOUR KPI CARDS */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            {/* Card 1: Documents */}
                            <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:border-blue-200 transition">
                                <div className="flex items-center gap-4">
                                    <div className="w-12 h-12 rounded-2xl bg-blue-100/70 text-blue-600 flex items-center justify-center shrink-0">
                                        <Folder className="w-6 h-6 fill-blue-600/30" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-2xl font-black text-slate-950 tracking-tight">
                                            {formatNumber(statistics.documents_count || 1248)}
                                        </div>
                                        <div className="text-xs font-semibold text-slate-500">
                                            Documents
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 text-right">
                                    <span className="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                        ↗ {statistics.documents_trend || '+12%'} <span className="text-[10px] text-slate-400 font-normal">ce mois</span>
                                    </span>
                                </div>
                            </div>

                            {/* Card 2: Utilisateurs actifs */}
                            <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:border-emerald-200 transition">
                                <div className="flex items-center gap-4">
                                    <div className="w-12 h-12 rounded-2xl bg-emerald-100/70 text-emerald-600 flex items-center justify-center shrink-0">
                                        <Users className="w-6 h-6" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-2xl font-black text-slate-950 tracking-tight">
                                            {formatNumber(statistics.active_users_count || 24)}
                                        </div>
                                        <div className="text-xs font-semibold text-slate-500">
                                            Utilisateurs actifs
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 text-right">
                                    <span className="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                        ↗ +8% <span className="text-[10px] text-slate-400 font-normal">ce mois</span>
                                    </span>
                                </div>
                            </div>

                            {/* Card 3: Types documentaires */}
                            <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:border-purple-200 transition">
                                <div className="flex items-center gap-4">
                                    <div className="w-12 h-12 rounded-2xl bg-purple-100/70 text-purple-600 flex items-center justify-center shrink-0">
                                        <Folder className="w-6 h-6 fill-purple-600/30" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-2xl font-black text-slate-950 tracking-tight">
                                            {formatNumber(statistics.document_types_count || 12)}
                                        </div>
                                        <div className="text-xs font-semibold text-slate-500">
                                            Types documentaires
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 text-right">
                                    <span className="text-[11px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                        ↗ +2 <span className="text-[10px] text-slate-400 font-normal">ce mois</span>
                                    </span>
                                </div>
                            </div>

                            {/* Card 4: Directions */}
                            <div className="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-2xs hover:border-amber-200 transition">
                                <div className="flex items-center gap-4">
                                    <div className="w-12 h-12 rounded-2xl bg-amber-100/70 text-amber-600 flex items-center justify-center shrink-0">
                                        <Building2 className="w-6 h-6" />
                                    </div>
                                    <div className="min-w-0">
                                        <div className="text-2xl font-black text-slate-950 tracking-tight">
                                            {formatNumber(statistics.departments_count || 5)}
                                        </div>
                                        <div className="text-xs font-semibold text-slate-500">
                                            Directions
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-3 text-right">
                                    <span className="text-[11px] font-medium text-slate-400 bg-slate-50 px-2 py-0.5 rounded-full inline-block">
                                        Aucune variation
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* 3. TWO ANALYTICAL CHARTS (Bar Chart + Donut Chart) */}
                        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                            
                            {/* Chart A: Activité des documents (7 Cols) */}
                            <div className="lg:col-span-7 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-2xs">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="flex items-center gap-2">
                                        <BarChart3 className="w-5 h-5 text-blue-600" />
                                        <h2 className="text-sm sm:text-base font-bold text-slate-950">
                                            Activité des documents
                                        </h2>
                                    </div>
                                    <div className="relative">
                                        <button
                                            type="button"
                                            onClick={() => setActivityPeriodOpen(!activityPeriodOpen)}
                                            className="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold text-slate-600 border border-slate-200 hover:bg-slate-50 transition"
                                        >
                                            <span>{activityPeriod}</span>
                                            <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
                                        </button>
                                        {activityPeriodOpen && (
                                            <>
                                                <div className="fixed inset-0 z-40" onClick={() => setActivityPeriodOpen(false)} />
                                                <div className="absolute right-0 z-50 mt-1 w-40 rounded-xl bg-white p-1.5 shadow-xl border border-slate-100">
                                                    {[
                                                        { key: '12m', label: 'Cette année' },
                                                        { key: '90d', label: '90 jours' },
                                                        { key: '30d', label: '30 jours' },
                                                        { key: '7d', label: '7 jours' },
                                                    ].map((item) => (
                                                        <button
                                                            key={item.key}
                                                            type="button"
                                                            onClick={() => handleSelectActivityPeriod(item.key, item.label)}
                                                            className="w-full text-left px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition"
                                                        >
                                                            {item.label}
                                                        </button>
                                                    ))}
                                                </div>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {/* Legend */}
                                <div className="flex items-center justify-end gap-4 text-xs font-medium text-slate-500 mb-6">
                                    <div className="flex items-center gap-1.5">
                                        <span className="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                                        <span>Documents ajoutés</span>
                                    </div>
                                    <div className="flex items-center gap-1.5">
                                        <span className="w-2.5 h-2.5 rounded-full bg-indigo-300"></span>
                                        <span>Documents consultés</span>
                                    </div>
                                </div>

                                {/* Dual-Bar Chart Representation */}
                                <div className="relative h-56 flex items-end justify-between gap-1.5 pt-4 pb-2 px-1">
                                    {/* Horizontal Guidelines */}
                                    <div className="absolute inset-0 flex flex-col justify-between pointer-events-none text-[10px] text-slate-400">
                                        <div className="border-b border-slate-100 flex items-center justify-between pb-1"><span>200</span></div>
                                        <div className="border-b border-slate-100 flex items-center justify-between pb-1"><span>150</span></div>
                                        <div className="border-b border-slate-100 flex items-center justify-between pb-1"><span>100</span></div>
                                        <div className="border-b border-slate-100 flex items-center justify-between pb-1"><span>50</span></div>
                                        <div className="border-b border-slate-200 flex items-center justify-between pb-1"><span>0</span></div>
                                    </div>

                                    {/* Bars */}
                                    {monthlyData.map((item, idx) => {
                                        const maxScale = 200;
                                        const addedHeight = Math.min(100, Math.max(10, ((item.added || 0) / maxScale) * 100));
                                        const viewedHeight = Math.min(100, Math.max(10, ((item.viewed || 0) / maxScale) * 100));

                                        return (
                                            <div key={idx} className="flex-1 flex flex-col items-center h-full justify-end z-10 group relative">
                                                <div className="flex items-end gap-1 w-full justify-center h-full pb-1">
                                                    {/* Added (Blue) */}
                                                    <div
                                                        className="w-2 sm:w-2.5 bg-blue-600 rounded-t-sm group-hover:bg-blue-700 transition-all duration-300"
                                                        style={{ height: `${addedHeight}%` }}
                                                    />
                                                    {/* Viewed (Indigo/Purple) */}
                                                    <div
                                                        className="w-2 sm:w-2.5 bg-indigo-300 rounded-t-sm group-hover:bg-indigo-400 transition-all duration-300"
                                                        style={{ height: `${viewedHeight}%` }}
                                                    />
                                                </div>
                                                <span className="text-[10px] font-medium text-slate-500 mt-2 truncate">
                                                    {item.month}
                                                </span>
                                                {/* Tooltip */}
                                                <div className="absolute -top-10 hidden group-hover:block bg-slate-900 text-white text-[10px] py-1 px-2 rounded-md shadow-md pointer-events-none z-20 whitespace-nowrap">
                                                    {item.month}: {item.added} ajout(s), {item.viewed} vue(s)
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Chart B: Répartition par direction (5 Cols) */}
                            <div className="lg:col-span-5 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                                <div>
                                    <div className="flex items-center justify-between mb-4">
                                        <div className="flex items-center gap-2">
                                            <PieChart className="w-5 h-5 text-blue-600" />
                                            <h2 className="text-sm sm:text-base font-bold text-slate-950">
                                                Répartition par direction
                                            </h2>
                                        </div>
                                        <div className="relative">
                                            <button
                                                type="button"
                                                onClick={() => setDirectionPeriodOpen(!directionPeriodOpen)}
                                                className="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold text-slate-600 border border-slate-200 hover:bg-slate-50 transition"
                                            >
                                                <span>{directionPeriod}</span>
                                                <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
                                            </button>
                                            {directionPeriodOpen && (
                                                <>
                                                    <div className="fixed inset-0 z-40" onClick={() => setDirectionPeriodOpen(false)} />
                                                    <div className="absolute right-0 z-50 mt-1 w-36 rounded-xl bg-white p-1.5 shadow-xl border border-slate-100">
                                                        {['Cette année', 'Ce mois', 'Global'].map((opt) => (
                                                            <button
                                                                key={opt}
                                                                type="button"
                                                                onClick={() => {
                                                                    setDirectionPeriod(opt);
                                                                    setDirectionPeriodOpen(false);
                                                                }}
                                                                className="w-full text-left px-3 py-1.5 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition"
                                                            >
                                                                {opt}
                                                            </button>
                                                        ))}
                                                    </div>
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    {/* Donut Chart and Legend */}
                                    <div className="flex flex-col sm:flex-row items-center gap-6 mt-4">
                                        {/* SVG Donut */}
                                        <div className="relative w-36 h-36 shrink-0 flex items-center justify-center">
                                            <svg className="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                                {donutSegments.map((seg, i) => {
                                                    const radius = 38;
                                                    const circumference = 2 * Math.PI * radius;
                                                    const strokeDasharray = `${(seg.percentage * circumference) / 100} ${circumference}`;
                                                    const strokeDashoffset = -((seg.start * circumference) / 100);

                                                    return (
                                                        <circle
                                                            key={i}
                                                            cx="50"
                                                            cy="50"
                                                            r={radius}
                                                            fill="transparent"
                                                            stroke={seg.color}
                                                            strokeWidth="14"
                                                            strokeDasharray={strokeDasharray}
                                                            strokeDashoffset={strokeDashoffset}
                                                            className="transition-all duration-500 hover:opacity-85"
                                                        />
                                                    );
                                                })}
                                            </svg>
                                            {/* Center Label */}
                                            <div className="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                                                <span className="text-base font-black text-slate-950 leading-none">
                                                    {formatNumber(statistics.documents_count || 1248)}
                                                </span>
                                                <span className="text-[10px] text-slate-500 font-medium mt-0.5">
                                                    documents
                                                </span>
                                            </div>
                                        </div>

                                        {/* Legend List */}
                                        <div className="flex-1 space-y-2 w-full">
                                            {directionData.map((item, idx) => (
                                                <div key={idx} className="flex items-center justify-between text-xs">
                                                    <div className="flex items-center gap-2 min-w-0">
                                                        <span
                                                            className="w-2.5 h-2.5 rounded-full shrink-0"
                                                            style={{ backgroundColor: item.color }}
                                                        />
                                                        <span className="text-slate-700 font-medium truncate max-w-[130px]" title={item.name}>
                                                            {item.name}
                                                        </span>
                                                    </div>
                                                    <span className="font-bold text-slate-900 ml-2">
                                                        {item.percentage}%
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                      
                        {/* 5. DOCUMENTS RÉCENTS TABLE */}
                        <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-2xs overflow-hidden">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-base font-bold text-slate-950">
                                    Documents récents
                                </h2>
                                <Link
                                    href="/documents"
                                    className="text-xs font-semibold text-blue-600 hover:text-blue-700 transition"
                                >
                                    Voir tout
                                </Link>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-slate-100 text-slate-400 font-semibold uppercase text-[10px] tracking-wider">
                                            <th className="py-3 px-3">Nom du document</th>
                                            <th className="py-3 px-3">Type</th>
                                            <th className="py-3 px-3">Direction</th>
                                            <th className="py-3 px-3">Date d'ajout</th>
                                            <th className="py-3 px-3">Ajouté par</th>
                                            <th className="py-3 px-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {displayDocuments.map((doc) => (
                                            <tr key={doc.id} className="hover:bg-slate-50/70 transition-colors group">
                                                <td className="py-3 px-3">
                                                    <div className="flex items-center gap-2.5">
                                                        {renderFormatBadge(doc.extension, doc.name)}
                                                        <Link
                                                            href={`/documents/${doc.id}`}
                                                            className="font-semibold text-slate-900 group-hover:text-blue-600 transition-colors truncate max-w-xs block"
                                                        >
                                                            {doc.name}
                                                        </Link>
                                                    </div>
                                                </td>
                                                <td className="py-3 px-3 text-slate-600 font-medium">
                                                    {doc.type_name || 'Facture client'}
                                                </td>
                                                <td className="py-3 px-3 text-slate-600 font-medium">
                                                    {doc.department_name || 'Comptabilité'}
                                                </td>
                                                <td className="py-3 px-3 text-slate-500 font-normal">
                                                    {doc.created_at_formatted || '24/09/2024 09:42'}
                                                </td>
                                                <td className="py-3 px-3 text-slate-700 font-medium">
                                                    {doc.uploader_name || 'Koffi Abalo'}
                                                </td>
                                                <td className="py-3 px-3 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        <Link
                                                            href={`/documents/${doc.id}/preview`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="p-1 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-slate-100 transition"
                                                            title="Aperçu"
                                                        >
                                                            <Eye className="w-3.5 h-3.5" />
                                                        </Link>
                                                        <a
                                                            href={`/documents/${doc.id}/download`}
                                                            className="p-1 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-slate-100 transition"
                                                            title="Télécharger"
                                                        >
                                                            <Download className="w-3.5 h-3.5" />
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    {/* RIGHT SIDEBAR WIDGETS AREA (4 Cols on XL) */}
                    <div className="xl:col-span-4 flex flex-col gap-6">

                        {/* Widget 1: Date & Digital Clock Card */}
                        <div className="rounded-3xl bg-gradient-to-br from-blue-50/90 via-indigo-50/50 to-white border border-blue-100/80 p-6 shadow-2xs relative overflow-hidden">
                            <div className="text-right">
                                <span className="text-xs font-semibold text-slate-600">
                                    {currentDateFormatted || 'Mardi 24 septembre 2024'}
                                </span>
                            </div>

                            <div className="text-right mt-2">
                                <span className="text-5xl font-black tracking-tight text-slate-950 font-sans">
                                    {currentTime || '10:24'}
                                </span>
                            </div>

                            <div className="mt-4 pt-4 border-t border-blue-200/50">
                                <p className="text-xs text-slate-500 italic text-center font-medium">
                                    « Des documents bien gérés, une entreprise plus sereine. »
                                </p>
                            </div>
                        </div>

                        {/* Widget: Abonnement & Utilisation */}
                        <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-2xs">
                            <div className="flex items-center justify-between mb-3">
                                <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    Plan actuel
                                </span>
                                <span className="text-xs font-black text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-100">
                                    {billing?.plan_name || 'Essentiel'}
                                </span>
                            </div>

                            <div className="space-y-3 pt-1">
                                {/* Users */}
                                <div>
                                    <div className="flex items-center justify-between text-xs mb-1">
                                        <span className="text-slate-600 font-medium">Utilisateurs</span>
                                        <span className="font-bold text-slate-900">
                                            {billing?.usage?.metrics?.users?.formatted || '0 / 5'}
                                        </span>
                                    </div>
                                    <div className="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div
                                            className="h-1.5 rounded-full bg-blue-600"
                                            style={{ width: `${Math.min(100, billing?.usage?.metrics?.users?.percentage || 0)}%` }}
                                        />
                                    </div>
                                </div>

                                {/* Storage */}
                                <div>
                                    <div className="flex items-center justify-between text-xs mb-1">
                                        <span className="text-slate-600 font-medium">Stockage</span>
                                        <span className="font-bold text-slate-900">
                                            {billing?.usage?.metrics?.storage?.formatted || '0 Go / 20 Go'}
                                        </span>
                                    </div>
                                    <div className="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div
                                            className={`h-1.5 rounded-full ${
                                                (billing?.usage?.metrics?.storage?.percentage || 0) >= 80 ? 'bg-amber-500' : 'bg-blue-600'
                                            }`}
                                            style={{ width: `${Math.min(100, billing?.usage?.metrics?.storage?.percentage || 0)}%` }}
                                        />
                                    </div>
                                </div>

                                {/* OCR */}
                                <div>
                                    <div className="flex items-center justify-between text-xs mb-1">
                                        <span className="text-slate-600 font-medium">Pages OCR (ce mois)</span>
                                        <span className="font-bold text-slate-900">
                                            {billing?.usage?.metrics?.ocr?.formatted || '0 / 100'}
                                        </span>
                                    </div>
                                    <div className="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                        <div
                                            className="h-1.5 rounded-full bg-indigo-600"
                                            style={{ width: `${Math.min(100, billing?.usage?.metrics?.ocr?.percentage || 0)}%` }}
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Approaching limit warning */}
                            {(billing?.usage?.metrics?.storage?.percentage || 0) >= 80 && (
                                <div className="mt-4 p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                                    <p className="font-bold">
                                        Votre stockage atteint {billing.usage.metrics.storage.percentage}% de votre limite.
                                    </p>
                                    <Link
                                        href="/subscription/choose"
                                        className="mt-2 inline-flex items-center gap-1 font-bold text-amber-700 hover:text-amber-800 underline"
                                    >
                                        Augmenter mon stockage →
                                    </Link>
                                </div>
                            )}

                            <div className="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                                <Link
                                    href="/settings/subscription"
                                    className="text-slate-500 hover:text-slate-900 font-semibold"
                                >
                                    Détails complets
                                </Link>
                                <Link
                                    href="/subscription/choose"
                                    className="text-blue-600 hover:text-blue-700 font-bold"
                                >
                                    Changer de plan →
                                </Link>
                            </div>
                        </div>

                        {/* Widget 2: Activité récente */}
                        <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-2xs">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-base font-bold text-slate-950">
                                    Activité récente
                                </h2>
                                <Link
                                    href="/audit-logs"
                                    className="text-xs font-semibold text-blue-600 hover:text-blue-700 transition"
                                >
                                    Voir tout
                                </Link>
                            </div>

                            <div className="space-y-4">
                                {displayActivity.map((act) => {
                                    const Icon = act.icon || Upload;
                                    return (
                                        <div key={act.id} className="flex items-start gap-3">
                                            <div className={`w-8 h-8 rounded-full ${act.color || 'bg-blue-600 text-white'} flex items-center justify-center shrink-0 shadow-2xs`}>
                                                <Icon className="w-4 h-4" />
                                            </div>
                                            <div className="min-w-0 flex-1 text-xs">
                                                <p className="text-slate-800 leading-snug">
                                                    <span className="font-bold text-slate-950">{act.author || act.user_name}</span>{' '}
                                                    <span className="text-slate-600">{act.text || act.action_label}</span>{' '}
                                                    <span className="font-semibold text-blue-600">{act.target || act.description}</span>
                                                </p>
                                                <span className="text-[10px] text-slate-400 mt-0.5 block">
                                                    {act.time || act.created_at_human}
                                                </span>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Widget 3: Mes tâches */}
                        <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-2xs">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-base font-bold text-slate-950">
                                    Mes tâches
                                </h2>
                                <Link
                                    href="/workflows"
                                    className="text-xs font-semibold text-blue-600 hover:text-blue-700 transition"
                                >
                                    Voir tout
                                </Link>
                            </div>

                            <div className="space-y-3">
                                {taskList.map((task) => (
                                    <div
                                        key={task.id}
                                        onClick={() => toggleTask(task.id)}
                                        className="flex items-center justify-between gap-3 p-1.5 rounded-xl hover:bg-slate-50 transition cursor-pointer select-none group"
                                    >
                                        <div className="flex items-center gap-3 min-w-0">
                                            <div className={`w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors ${
                                                task.completed
                                                    ? 'bg-blue-600 border-blue-600 text-white'
                                                    : 'border-slate-300 group-hover:border-blue-500'
                                            }`}>
                                                {task.completed && <Check className="w-2.5 h-2.5 stroke-[3]" />}
                                            </div>
                                            <span className={`text-xs font-medium truncate ${
                                                task.completed ? 'line-through text-slate-400' : 'text-slate-700'
                                            }`}>
                                                {task.title}
                                            </span>
                                        </div>

                                        {task.urgent && (
                                            <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-600 shrink-0">
                                                Urgent
                                            </span>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Widget 4: Espace de stockage */}
                        <div className="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-2xs">
                            <div className="flex items-center justify-between mb-3">
                                <div className="flex items-center gap-2">
                                    <HardDrive className="w-4 h-4 text-blue-600" />
                                    <h3 className="text-sm font-bold text-slate-950">
                                        Espace de stockage
                                    </h3>
                                </div>
                                <span className="text-xs font-bold text-slate-700">
                                    {statistics.storage_percent || 64}% utilisé
                                </span>
                            </div>

                            {/* Storage Gauge Bar */}
                            <div className="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden my-3">
                                <div
                                    className="bg-blue-600 h-full rounded-full transition-all duration-500"
                                    style={{ width: `${Math.max(2, Math.min(100, statistics.storage_percent || 64))}%` }}
                                />
                            </div>

                            <div className="flex items-center justify-between mt-3 text-xs">
                                <span className="text-slate-500 font-medium">
                                    {statistics.storage_used_formatted || '128 Go'} utilisés sur {statistics.storage_limit_formatted || '200 Go'}
                                </span>
                                <Link
                                    href="/settings"
                                    className="inline-flex items-center gap-1 font-semibold text-slate-700 hover:text-blue-600 transition border border-slate-200 px-2.5 py-1 rounded-xl"
                                >
                                    <span>Voir le détail</span>
                                    <ArrowRight className="w-3 h-3" />
                                </Link>
                            </div>
                        </div>

                    </div>

                </div>

                {/* BOTTOM FOOTER */}
                <footer className="pt-6 pb-2 border-t border-slate-200/70 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                    <div className="flex items-center gap-2">
                        <div className="w-5 h-5 rounded-md bg-blue-600 flex items-center justify-center text-white shrink-0">
                            <svg className="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5">
                                <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z" fill="currentColor"/>
                            </svg>
                        </div>
                        <span className="font-bold text-slate-800">GEDAPP</span>
                        <span className="text-slate-400">|</span>
                        <span>Une solution de gestion documentaire sécurisée et performante</span>
                    </div>

                    <div className="flex items-center gap-4 text-slate-500 font-medium">
                        <a href="#" className="hover:text-blue-600 transition">Aide</a>
                        <a href="#" className="hover:text-blue-600 transition">Confidentialité</a>
                        <a href="#" className="hover:text-blue-600 transition">Conditions</a>
                        <span>© 2024 GEDAPP. Tous droits réservés.</span>
                    </div>
                </footer>
            </div>
        </AuthenticatedLayout>
    );
}
