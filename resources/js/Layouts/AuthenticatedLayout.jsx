import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard,
    PlusCircle,
    Search,
    FileText,
    Star,
    Share2,
    Clock,
    GitBranch,
    Trash2,
    Building2,
    FileStack,
    Users,
    Shield,
    Settings,
    Bell,
    Sun,
    Moon,
    Menu,
    X,
    ChevronRight,
    ChevronDown,
    Crown,
    User as UserIcon,
    LogOut,
    ArrowRight
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
    const unreadCount = auth?.unread_notifications_count ?? 3;

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

    const navItems = [
        { name: 'Tableau de bord', href: '/dashboard', icon: LayoutDashboard, current: isUrl('/dashboard') },
        { name: 'Importer un document', href: '/documents/create', icon: PlusCircle, current: isUrl('/documents/create') },
        { name: 'Rechercher', href: '/search', icon: Search, current: isUrl('/search') },
        { name: 'Mes documents', href: '/documents', icon: FileText, current: isUrl('/documents') && !pageUrl.includes('/create') && !pageUrl.includes('/trash') && !pageUrl.includes('/archived') },
        { name: 'Favoris', href: '/favorites', icon: Star, current: isUrl('/favorites') },
        { name: 'Partages', href: '/shares', icon: Share2, current: isUrl('/shares') },
        { name: 'Documents récents', href: '/recent', icon: Clock, current: isUrl('/recent') },
        { name: 'Workflows', href: '/workflows', icon: GitBranch, current: isUrl('/workflows'), badge: 3 },
        { name: 'Corbeille', href: '/documents/trash', icon: Trash2, current: pageUrl.includes('/trash') },
    ];

    const adminItems = [
        { name: 'Directions', href: '/departments', icon: Building2, current: isUrl('/departments') },
        { name: 'Types documentaires', href: '/document-types', icon: FileStack, current: isUrl('/document-types') },
        { name: 'Utilisateurs', href: '/users', icon: Users, current: isUrl('/users') },
        { name: 'Rôles & permissions', href: '/roles', icon: Shield, current: isUrl('/roles') },
        { name: 'Paramètres', href: '/settings', icon: Settings, current: isUrl('/settings') },
    ];

    const renderSidebarContent = () => (
        <div className="flex flex-col h-full bg-[#0B132B] text-slate-300">
            {/* Logo Brand Header */}
            <div className="h-20 flex items-center px-6 gap-3 shrink-0 border-b border-slate-800/60">
                <Link href="/dashboard" className="flex items-center gap-3 group">
                    <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 shrink-0 group-hover:scale-105 transition-transform">
                        {/* Cloud + Lock Icon */}
                        <svg className="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z" fill="currentColor" fillOpacity="0.2"/>
                            <rect x="10" y="11" width="4" height="4" rx="1" fill="white" stroke="none" />
                            <path d="M11 11V9.5a1 1 0 0 1 2 0V11" stroke="white" strokeWidth="1.5" strokeLinecap="round"/>
                        </svg>
                    </div>
                    <div className="flex flex-col">
                        <span className="text-xl font-black tracking-tight text-white leading-none">
                            GED<span className="text-blue-500">APP</span>
                        </span>
                        <span className="text-[10px] text-slate-400 font-medium tracking-tight mt-1 leading-none">
                            Vos documents, plus loin
                        </span>
                    </div>
                </Link>
            </div>

            {/* Scrollable Nav Items */}
            <div className="flex-1 overflow-y-auto px-4 py-5 space-y-6 scrollbar-thin scrollbar-thumb-slate-800">
                {/* Main Navigation */}
                <ul className="space-y-1.5">
                    {navItems.map((item) => {
                        const Icon = item.icon;
                        const active = item.current;
                        return (
                            <li key={item.name}>
                                <Link
                                    href={item.href}
                                    onClick={() => setSidebarOpen(false)}
                                    className={`flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 ${
                                        active
                                            ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                                            : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                                    }`}
                                >
                                    <div className="flex items-center gap-3">
                                        <Icon className={`w-4 h-4 shrink-0 ${active ? 'text-white' : 'text-slate-400'}`} />
                                        <span>{item.name}</span>
                                    </div>
                                    {item.badge && (
                                        <span className="w-5 h-5 flex items-center justify-center rounded-full text-[10px] font-bold bg-rose-500 text-white shrink-0">
                                            {item.badge}
                                        </span>
                                    )}
                                </Link>
                            </li>
                        );
                    })}
                </ul>

                {/* Administration Section */}
                <div>
                    <h3 className="px-3.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                        ADMINISTRATION
                    </h3>
                    <ul className="space-y-1.5">
                        {adminItems.map((item) => {
                            const Icon = item.icon;
                            const active = item.current;
                            return (
                                <li key={item.name}>
                                    <Link
                                        href={item.href}
                                        onClick={() => setSidebarOpen(false)}
                                        className={`flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 ${
                                            active
                                                ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30'
                                                : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                                        }`}
                                    >
                                        <div className="flex items-center gap-3">
                                            <Icon className={`w-4 h-4 shrink-0 ${active ? 'text-white' : 'text-slate-400'}`} />
                                            <span>{item.name}</span>
                                        </div>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                </div>

                {/* Upgrade Plan Card */}
                <div className="rounded-2xl bg-gradient-to-b from-[#132238] to-[#0e192a] border border-blue-900/40 p-4 relative overflow-hidden">
                    <div className="flex items-start gap-3">
                        <div className="w-8 h-8 rounded-lg bg-amber-400/10 text-amber-400 flex items-center justify-center shrink-0">
                            <Crown className="w-4 h-4" />
                        </div>
                        <div className="min-w-0">
                            <h4 className="text-xs font-bold text-white leading-tight">
                                Passez à un plan supérieur
                            </h4>
                            <p className="text-[11px] text-slate-400 mt-1 leading-snug">
                                Plus de stockage, plus de possibilités.
                            </p>
                        </div>
                    </div>
                    <Link
                        href="#tarifs"
                        className="mt-3.5 w-full flex items-center justify-center gap-1.5 py-2 px-3 bg-blue-600/30 hover:bg-blue-600 text-blue-300 hover:text-white rounded-xl text-xs font-semibold transition-colors border border-blue-500/30"
                    >
                        <span>Voir nos offres</span>
                        <ArrowRight className="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>

            {/* Bottom Tenant / User Account Switcher */}
            <div className="p-4 border-t border-slate-800/60 shrink-0">
                <div className="flex items-center justify-between p-2 rounded-xl hover:bg-slate-800/50 transition cursor-pointer">
                    <div className="flex items-center gap-3 min-w-0">
                        <div className="w-8 h-8 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center border border-blue-400/40 shrink-0">
                            {(organization?.name ? organization.name[0] : 'G').toUpperCase()}
                        </div>
                        <div className="min-w-0">
                            <p className="text-xs font-bold text-white truncate">
                                {organization?.name || 'GEDAPP'}
                            </p>
                            <p className="text-[10px] text-slate-400 truncate">
                                Espace professionnel
                            </p>
                        </div>
                    </div>
                    <ChevronRight className="w-4 h-4 text-slate-400 shrink-0" />
                </div>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-[#F8FAFC] text-slate-900 flex font-sans antialiased">
            {/* Mobile Drawer */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-50 lg:hidden flex">
                    <div
                        className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                        onClick={() => setSidebarOpen(false)}
                    />
                    <aside className="relative z-50 w-64 max-w-[80vw] flex flex-col h-full shadow-2xl">
                        {renderSidebarContent()}
                    </aside>
                </div>
            )}

            {/* Permanent Desktop Sidebar */}
            <aside className="hidden lg:flex lg:flex-col lg:w-64 lg:shrink-0 h-screen sticky top-0 z-40">
                {renderSidebarContent()}
            </aside>

            {/* Main Application Column */}
            <div className="flex-1 flex flex-col min-w-0 min-h-screen">
                {/* Top Navigation Header */}
                <header className="sticky top-0 z-30 flex h-20 shrink-0 items-center justify-between gap-4 border-b border-slate-200/80 bg-white/90 backdrop-blur-md px-4 sm:px-6 lg:px-8">
                    {/* Burger for Mobile */}
                    <button
                        type="button"
                        className="p-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-xl lg:hidden"
                        onClick={() => setSidebarOpen(!sidebarOpen)}
                        aria-label="Ouvrir le menu"
                    >
                        <Menu className="w-5 h-5" />
                    </button>

                    {/* Central Global Search Bar with Ctrl+K */}
                    <div className="flex-1 max-w-xl">
                        <form onSubmit={handleSearch} className="relative">
                            <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <Search className="w-4 h-4" />
                            </div>
                            <input
                                type="search"
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Rechercher un document, un client, une référence..."
                                className="w-full rounded-2xl border border-slate-200 bg-slate-50/80 pl-10 pr-16 py-2.5 text-xs sm:text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 transition"
                            />
                            <div className="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <span className="hidden sm:inline-flex items-center text-[10px] font-semibold text-slate-400 bg-white border border-slate-200 px-1.5 py-0.5 rounded-md shadow-2xs">
                                    Ctrl + K
                                </span>
                            </div>
                        </form>
                    </div>

                    {/* Right User Controls */}
                    <div className="flex items-center gap-2 sm:gap-4 shrink-0">
                        {/* Light / Dark Mode Toggle */}
                        <button
                            type="button"
                            className="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                            title="Basculer le thème"
                        >
                            <Sun className="w-4 h-4" />
                        </button>

                        {/* Notifications Bell with Counter */}
                        <Link
                            href="/notifications"
                            className="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                            title="Notifications"
                        >
                            <Bell className="w-4 h-4" />
                            {unreadCount > 0 && (
                                <span className="absolute top-1.5 right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white ring-2 ring-white">
                                    {unreadCount}
                                </span>
                            )}
                        </Link>

                        {/* User Profile Pill */}
                        <div className="relative pl-2 sm:pl-3 border-l border-slate-200">
                            <button
                                type="button"
                                onClick={() => setUserMenuOpen(!userMenuOpen)}
                                className="flex items-center gap-3 p-1 rounded-xl hover:bg-slate-100 transition focus:outline-none"
                            >
                                {user?.avatar ? (
                                    <img
                                        src={user.avatar}
                                        alt={user?.name || 'Utilisateur'}
                                        className="w-9 h-9 rounded-full object-cover border border-slate-200 shadow-2xs shrink-0"
                                    />
                                ) : (
                                    <div className="w-9 h-9 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shadow-xs shrink-0">
                                        {(user?.first_name ? user.first_name[0] : (user?.name ? user.name[0] : 'U')).toUpperCase()}
                                    </div>
                                )}
                                <div className="hidden sm:flex flex-col text-left">
                                    <span className="text-xs font-bold text-slate-900 leading-tight">
                                        {user?.first_name ? `${user.first_name} ${user.last_name || ''}`.trim() : (user?.name || 'Utilisateur')}
                                    </span>
                                    <span className="text-[11px] text-slate-500 font-medium leading-tight">
                                        {roles[0] ? roles[0].charAt(0).toUpperCase() + roles[0].slice(1) : (user?.job_title || 'Utilisateur')}
                                    </span>
                                </div>
                            </button>

                            {/* Dropdown Menu */}
                            {userMenuOpen && (
                                <>
                                    <div
                                        className="fixed inset-0 z-40"
                                        onClick={() => setUserMenuOpen(false)}
                                    />
                                    <div className="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-2xl bg-white p-2 shadow-xl border border-slate-100 focus:outline-none">
                                        <div className="px-3 py-2 border-b border-slate-100 mb-1">
                                            <p className="text-xs font-bold text-slate-900 truncate">
                                                {user?.name || 'Koffi Abalo'}
                                            </p>
                                            <p className="text-[11px] text-slate-500 truncate">{user?.email}</p>
                                        </div>
                                        <Link
                                            href="/profile"
                                            onClick={() => setUserMenuOpen(false)}
                                            className="flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 hover:text-blue-600 transition"
                                        >
                                            <UserIcon className="w-4 h-4" />
                                            Mon profil
                                        </Link>
                                        <Link
                                            href="/logout"
                                            method="post"
                                            as="button"
                                            className="w-full flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition"
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

                {/* Main Content Render */}
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
