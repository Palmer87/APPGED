import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard,
    Files,
    Folder,
    Star,
    Clock,
    Share2,
    Archive,
    Trash2,
    GitBranch,
    History,
    Bell,
    FolderTree,
    Tags as TagsIcon,
    FileSpreadsheet,
    User as UserIcon,
    LogOut,
    Menu,
    X,
    Search,
    ChevronDown,
    Building2,
    Shield,
    Users
} from 'lucide-react';
import Toast from '../Components/Toast';

export default function AuthenticatedLayout({ children, title }) {
    const page = usePage();
    const { auth, flash } = page.props || {};
    const pageUrl = page.url || page.props?.url || (typeof window !== 'undefined' ? window.location.pathname : '') || '';
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    const user = auth?.user;
    const organization = auth?.organization;
    const roles = auth?.roles || [];
    const permissions = auth?.permissions || [];
    const unreadCount = auth?.unread_notifications_count || 0;

    const isAdmin = roles.includes('admin') || roles.includes('super-admin');
    const isManager = isAdmin || roles.includes('manager');

    const handleSearch = (e) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            router.get('/search', { q: searchQuery.trim() });
        }
    };

    const isUrl = (prefix) => {
        if (!pageUrl || typeof pageUrl !== 'string') return false;
        return pageUrl === prefix || pageUrl.startsWith(prefix);
    };

    const hasUrl = (segment) => {
        if (!pageUrl || typeof pageUrl !== 'string') return false;
        return pageUrl.includes(segment);
    };

    const navItems = [
        {
            group: 'GÉNÉRAL',
            items: [
                { name: 'Tableau de bord', href: '/dashboard', icon: LayoutDashboard, current: isUrl('/dashboard') },
            ]
        },
        {
            group: 'DOCUMENTS',
            items: [
                { name: 'Tous les documents', href: '/documents', icon: Files, current: isUrl('/documents') && !hasUrl('/trash') && !hasUrl('/archived') },
                { name: 'Dossiers', href: '/folders', icon: Folder, current: isUrl('/folders') },
                { name: 'Favoris', href: '/favorites', icon: Star, current: isUrl('/favorites') },
                { name: 'Récents', href: '/recent', icon: Clock, current: isUrl('/recent') },
                { name: 'Partagés avec moi', href: '/shares', icon: Share2, current: isUrl('/shares') },
                { name: 'Archivés', href: '/documents/archived', icon: Archive, current: hasUrl('/documents/archived') },
                { name: 'Corbeille', href: '/documents/trash', icon: Trash2, current: hasUrl('/documents/trash') },
            ]
        },
        {
            group: 'COLLABORATION',
            items: [
                { name: 'Workflows', href: '/workflows', icon: GitBranch, current: isUrl('/workflows') || isUrl('/workflow-instances') },
                { name: 'Notifications', href: '/notifications', icon: Bell, current: isUrl('/notifications'), badge: unreadCount > 0 ? unreadCount : null },
                { name: 'Journal d\'audit', href: '/audit-logs', icon: History, current: isUrl('/audit-logs') },
            ]
        },
    ];

    if (isAdmin || isManager || permissions.includes('users.view') || permissions.includes('roles.view')) {
        const adminItems = [
            { name: 'Catégories', href: '/categories', icon: FolderTree, current: isUrl('/categories') },
            { name: 'Tags', href: '/tags', icon: TagsIcon, current: isUrl('/tags') },
            { name: 'Métadonnées', href: '/metadata', icon: FileSpreadsheet, current: isUrl('/metadata') },
        ];

        if (isAdmin || permissions.includes('users.view')) {
            adminItems.push({
                name: 'Utilisateurs',
                href: '/users',
                icon: Users,
                current: isUrl('/users'),
            });
        }

        if (isAdmin || permissions.includes('roles.view')) {
            adminItems.push({
                name: 'Rôles & Permissions',
                href: '/roles',
                icon: Shield,
                current: isUrl('/roles'),
            });
        }

        navItems.push({
            group: 'ADMINISTRATION',
            items: adminItems,
        });
    }

    const renderNavContent = () => (
        <div className="flex-1 overflow-y-auto px-4 py-6 space-y-6">
            {navItems.map((group, idx) => (
                <div key={idx}>
                    <h3 className="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                        {group.group}
                    </h3>
                    <ul className="space-y-1">
                        {group.items.map((item) => {
                            const Icon = item.icon;
                            return (
                                <li key={item.name}>
                                    <Link
                                        href={item.href}
                                        onClick={() => setSidebarOpen(false)}
                                        className={`flex items-center justify-between rounded-xl px-3 py-2 text-xs font-semibold transition ${
                                            item.current
                                                ? 'bg-indigo-50 text-indigo-700 shadow-2xs font-bold'
                                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Icon className={`w-4 h-4 shrink-0 ${item.current ? 'text-indigo-600' : 'text-slate-400'}`} />
                                            <span>{item.name}</span>
                                        </div>
                                        {item.badge && (
                                            <span className="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">
                                                {item.badge}
                                            </span>
                                        )}
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>
            ))}
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 flex flex-col font-sans">
            {/* Mobile Drawer (shown only when burger menu clicked) */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-50 lg:hidden flex">
                    <div
                        className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                        onClick={() => setSidebarOpen(false)}
                    />
                    <aside className="relative z-50 w-64 max-w-[80vw] bg-white border-r border-slate-200 shadow-2xl flex flex-col h-full">
                        <div className="flex h-16 items-center justify-between px-6 border-b border-slate-100 shrink-0">
                            <div className="flex items-center gap-2">
                                <div className="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                                    GED
                                </div>
                                <span className="font-bold text-slate-900">GEDAPP</span>
                            </div>
                            <button
                                type="button"
                                onClick={() => setSidebarOpen(false)}
                                className="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100"
                                aria-label="Fermer le menu"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>
                        {renderNavContent()}
                    </aside>
                </div>
            )}

            {/* Top Navigation Header */}
            <header className="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-slate-200 bg-white px-4 shadow-2xs sm:gap-x-6 sm:px-6 lg:px-8">
                {/* Burger for Mobile */}
                <button
                    type="button"
                    className="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg lg:hidden"
                    onClick={() => setSidebarOpen(!sidebarOpen)}
                    aria-label="Ouvrir le menu"
                >
                    <Menu className="w-5 h-5" />
                </button>

                {/* Logo & Tenant badge */}
                <div className="flex items-center gap-3">
                    <Link href="/dashboard" className="flex items-center gap-2.5 group">
                        <div className="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-100 group-hover:bg-indigo-700 transition">
                            GED
                        </div>
                        <span className="hidden sm:inline font-bold text-base tracking-tight text-slate-900">
                            GED<span className="text-indigo-600">APP</span>
                        </span>
                    </Link>

                    {organization && (
                        <span className="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            <Building2 className="w-3.5 h-3.5 text-slate-400" />
                            {organization.name}
                        </span>
                    )}
                </div>

                {/* Search Bar */}
                <div className="flex flex-1 items-center justify-center max-w-xl mx-auto">
                    <form onSubmit={handleSearch} className="w-full relative">
                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <Search className="w-4 h-4" />
                        </div>
                        <input
                            type="search"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Rechercher documents, tags, dossiers..."
                            className="w-full rounded-full border border-slate-200 bg-slate-50/70 pl-9 pr-4 py-1.5 text-sm text-slate-900 placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 transition"
                        />
                    </form>
                </div>

                {/* Right controls: Notifications & User */}
                <div className="flex items-center gap-3">
                    <Link
                        href="/notifications"
                        className="relative p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-full transition"
                        title="Centre de notifications"
                    >
                        <Bell className="w-5 h-5" />
                        {unreadCount > 0 && (
                            <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold text-white ring-2 ring-white animate-pulse">
                                {unreadCount > 9 ? '9+' : unreadCount}
                            </span>
                        )}
                    </Link>

                    {/* User dropdown */}
                    <div className="relative">
                        <button
                            type="button"
                            onClick={() => setUserMenuOpen(!userMenuOpen)}
                            className="flex items-center gap-2 p-1.5 text-sm rounded-full hover:bg-slate-100 transition focus:outline-none"
                        >
                            <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs border border-indigo-200">
                                {user?.first_name ? user.first_name[0].toUpperCase() : 'U'}
                            </div>
                            <span className="hidden lg:block text-xs font-semibold text-slate-700 max-w-[120px] truncate">
                                {user?.name || 'Utilisateur'}
                            </span>
                            <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
                        </button>

                        {userMenuOpen && (
                            <>
                                <div
                                    className="fixed inset-0 z-40"
                                    onClick={() => setUserMenuOpen(false)}
                                />
                                <div className="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-xl bg-white p-1.5 shadow-xl border border-slate-200 focus:outline-none">
                                    <div className="px-3 py-2 border-b border-slate-100 mb-1">
                                        <p className="text-sm font-semibold text-slate-900 truncate">{user?.name}</p>
                                        <p className="text-xs text-slate-500 truncate">{user?.email}</p>
                                        {roles.length > 0 && (
                                            <div className="mt-1 flex flex-wrap gap-1">
                                                {roles.map((r) => (
                                                    <span key={r} className="inline-flex items-center text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-700">
                                                        {r}
                                                    </span>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                    <Link
                                        href="/profile"
                                        onClick={() => setUserMenuOpen(false)}
                                        className="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition"
                                    >
                                        <UserIcon className="w-4 h-4" />
                                        Mon profil
                                    </Link>
                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        className="w-full flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition"
                                    >
                                        <LogOut className="w-4 h-4" />
                                        Se déconnecter
                                    </Link>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </header>

            <div className="flex flex-1 min-h-0">
                {/* Permanent Desktop Sidebar */}
                <aside className="hidden lg:flex lg:flex-col lg:w-64 lg:shrink-0 bg-white border-r border-slate-200 min-h-[calc(100vh-4rem)]">
                    {renderNavContent()}
                </aside>

                {/* Main page content */}
                <main className="flex-1 overflow-x-hidden p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>

            {/* Global Flash Toasts */}
            {flash?.success && <Toast message={flash.success} type="success" />}
            {flash?.error && <Toast message={flash.error} type="error" />}
            {flash?.message && <Toast message={flash.message} type="info" />}
        </div>
    );
}
