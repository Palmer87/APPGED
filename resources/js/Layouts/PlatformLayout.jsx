import React, { useState } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard,
    Building2,
    CreditCard,
    FileStack,
    Clock,
    DollarSign,
    Receipt,
    BarChart3,
    Users,
    LifeBuoy,
    ShieldAlert,
    Settings,
    LogOut,
    Menu,
    X,
    Search,
    ChevronRight,
    Crown,
    CheckCircle2,
    AlertCircle
} from 'lucide-react';

export default function PlatformLayout({ children, title }) {
    const { platform_auth, flash, url } = usePage().props;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const user = platform_auth?.user || { name: 'Admin Platform', role: 'platform_owner' };
    const currentUrl = url || (typeof window !== 'undefined' ? window.location.pathname : '');

    const isCurrent = (path) => {
        if (path === '/platform') {
            return currentUrl === '/platform' || currentUrl === '/platform/';
        }
        return currentUrl.startsWith(path);
    };

    const navItems = [
        { name: 'Vue globale SaaS', href: '/platform', icon: LayoutDashboard, current: isCurrent('/platform') && currentUrl.split('?')[0] === '/platform' },
        { name: 'Organisations', href: '/platform/organizations', icon: Building2, current: isCurrent('/platform/organizations') },
        { name: 'Abonnements', href: '/platform/subscriptions', icon: CreditCard, current: isCurrent('/platform/subscriptions') },
        { name: 'Plans tarifaires', href: '/platform/plans', icon: FileStack, current: isCurrent('/platform/plans') },
        { name: 'Périodes d\'essai', href: '/platform/trials', icon: Clock, current: isCurrent('/platform/trials') },
        { name: 'Paiements', href: '/platform/payments', icon: DollarSign, current: isCurrent('/platform/payments') },
        { name: 'Factures', href: '/platform/invoices', icon: Receipt, current: isCurrent('/platform/invoices') },
        { name: 'Quotas & Usage', href: '/platform/usage', icon: BarChart3, current: isCurrent('/platform/usage') },
        { name: 'Utilisateurs clients', href: '/platform/users', icon: Users, current: isCurrent('/platform/users') },
        { name: 'Support & Tickets', href: '/platform/support', icon: LifeBuoy, current: isCurrent('/platform/support') },
        { name: 'Audit plateforme', href: '/platform/audit', icon: ShieldAlert, current: isCurrent('/platform/audit') },
        { name: 'Paramètres', href: '/platform/settings', icon: Settings, current: isCurrent('/platform/settings') },
    ];

    const handleLogout = () => {
        router.post('/platform/logout');
    };

    const getRoleBadge = (role) => {
        switch (role) {
            case 'platform_owner':
                return { label: 'Propriétaire SaaS', bg: 'bg-amber-500/10 text-amber-400 border-amber-500/30' };
            case 'platform_admin':
                return { label: 'Admin Plateforme', bg: 'bg-blue-500/10 text-blue-400 border-blue-500/30' };
            case 'platform_billing':
                return { label: 'Facturation', bg: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' };
            case 'platform_support':
                return { label: 'Support Client', bg: 'bg-purple-500/10 text-purple-400 border-purple-500/30' };
            default:
                return { label: 'Opérateur', bg: 'bg-slate-500/10 text-slate-400 border-slate-500/30' };
        }
    };

    const roleBadge = getRoleBadge(user.role);

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100 flex font-sans antialiased selection:bg-indigo-500 selection:text-white">
            {/* Mobile Sidebar Backdrop */}
            {sidebarOpen && (
                <div
                    className="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs lg:hidden"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar Column */}
            <aside
                className={`fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 border-r border-slate-800/80 flex flex-col transition-transform duration-200 lg:static lg:translate-x-0 ${
                    sidebarOpen ? 'translate-x-0' : '-translate-x-full'
                }`}
            >
                {/* Brand / Logo Area */}
                <div className="h-20 flex items-center justify-between px-6 border-b border-slate-800/80">
                    <Link href="/platform" className="flex items-center gap-3 group">
                        <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-600/30 group-hover:scale-105 transition-transform">
                            <Crown className="w-5 h-5" />
                        </div>
                        <div>
                            <div className="flex items-center gap-1.5">
                                <span className="text-lg font-black tracking-tight text-white">GED<span className="text-indigo-400">APP</span></span>
                                <span className="px-1.5 py-0.5 rounded text-[10px] font-extrabold uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                                    CORE
                                </span>
                            </div>
                            <span className="text-[10px] text-slate-400 font-medium block">
                                Platform Administration
                            </span>
                        </div>
                    </Link>
                    <button
                        type="button"
                        onClick={() => setSidebarOpen(false)}
                        className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 lg:hidden"
                    >
                        <X className="w-5 h-5" />
                    </button>
                </div>

                {/* Platform User Profile Snippet */}
                <div className="p-4 mx-4 mt-4 rounded-2xl bg-slate-800/40 border border-slate-800/80 flex items-center gap-3">
                    <div className="w-10 h-10 rounded-xl bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-bold text-sm shrink-0">
                        {user.name ? user.name[0].toUpperCase() : 'P'}
                    </div>
                    <div className="min-w-0 flex-1">
                        <p className="text-xs font-bold text-white truncate">{user.name}</p>
                        <span className={`inline-flex items-center px-2 py-0.5 mt-0.5 rounded-full text-[10px] font-semibold border ${roleBadge.bg}`}>
                            {roleBadge.label}
                        </span>
                    </div>
                </div>

                {/* Nav Links */}
                <nav className="flex-1 overflow-y-auto px-4 py-4 space-y-1 scrollbar-thin scrollbar-thumb-slate-800">
                    <p className="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-2">
                        GESTION DU SAAS
                    </p>
                    {navItems.map((item) => {
                        const Icon = item.icon;
                        const active = item.current;
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                onClick={() => setSidebarOpen(false)}
                                className={`flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all duration-150 ${
                                    active
                                        ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white shadow-md shadow-indigo-600/30'
                                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'
                                }`}
                            >
                                <div className="flex items-center gap-3">
                                    <Icon className={`w-4 h-4 shrink-0 ${active ? 'text-white' : 'text-slate-400'}`} />
                                    <span>{item.name}</span>
                                </div>
                                {active && <ChevronRight className="w-3.5 h-3.5 text-indigo-200" />}
                            </Link>
                        );
                    })}
                </nav>

                {/* Logout Button */}
                <div className="p-4 border-t border-slate-800/80">
                    <button
                        type="button"
                        onClick={handleLogout}
                        className="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-xl text-xs font-semibold text-rose-400 hover:text-white hover:bg-rose-600/20 border border-rose-500/20 transition-colors cursor-pointer"
                    >
                        <LogOut className="w-4 h-4" />
                        <span>Déconnexion Plateforme</span>
                    </button>
                </div>
            </aside>

            {/* Main Application Area */}
            <div className="flex-1 flex flex-col min-w-0 min-h-screen bg-slate-950">
                {/* Header */}
                <header className="h-20 shrink-0 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md sticky top-0 z-30 px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <button
                            type="button"
                            onClick={() => setSidebarOpen(true)}
                            className="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 lg:hidden"
                        >
                            <Menu className="w-5 h-5" />
                        </button>
                        <h1 className="text-base sm:text-lg font-bold text-white tracking-tight">
                            {title || 'Console Propriétaire SaaS'}
                        </h1>
                    </div>

                    <div className="flex items-center gap-3">
                        {/* Tenant Switch link */}
                        <a
                            href="/dashboard"
                            target="_blank"
                            rel="noreferrer"
                            className="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-slate-800 transition"
                        >
                            <span>Espace Client Démo</span>
                            <ChevronRight className="w-3 h-3" />
                        </a>

                        <div className="h-8 w-px bg-slate-800 hidden sm:block" />

                        <div className="flex items-center gap-2">
                            <div className="w-8 h-8 rounded-lg bg-indigo-500/20 border border-indigo-500/40 text-indigo-300 font-bold text-xs flex items-center justify-center">
                                {user.name ? user.name[0] : 'P'}
                            </div>
                            <span className="text-xs font-semibold text-slate-300 hidden md:inline">
                                {user.email}
                            </span>
                        </div>
                    </div>
                </header>

                {/* Flash Messages */}
                {flash?.success && (
                    <div className="m-4 sm:m-6 mb-0 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 flex items-center gap-3 animate-in fade-in">
                        <CheckCircle2 className="w-5 h-5 shrink-0 text-emerald-400" />
                        <span className="text-xs font-medium">{flash.success}</span>
                    </div>
                )}
                {flash?.error && (
                    <div className="m-4 sm:m-6 mb-0 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 flex items-center gap-3 animate-in fade-in">
                        <AlertCircle className="w-5 h-5 shrink-0 text-rose-400" />
                        <span className="text-xs font-medium">{flash.error}</span>
                    </div>
                )}

                {/* Content Body */}
                <main className="flex-1 p-4 sm:p-6 lg:p-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
