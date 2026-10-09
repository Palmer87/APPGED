import React, { useRef, useState } from 'react';
import {
    CalendarDays,
    CheckCircle2,
    Clock3,
    Download,
    Eye,
    FileText,
    FolderOpen,
    History,
    LayoutDashboard,
    MessageSquare,
    Search,
    Share2,
    Star,
    Tag,
    Upload,
    UserRound,
} from 'lucide-react';
import { IllustrativeBadge } from './Reveal';

const tabs = [
    { id: 'dashboard', label: 'Tableau de bord', icon: LayoutDashboard },
    { id: 'preview', label: 'Aperçu document', icon: Eye },
    { id: 'search', label: 'Recherche métier', icon: Search },
    { id: 'metadata', label: 'Métadonnées', icon: Tag },
    { id: 'versions', label: 'Versions', icon: History },
];

function MockShell({ title, breadcrumb, children }) {
    return (
        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div className="flex items-center gap-2 border-b border-slate-100 bg-slate-50 px-4 py-3">
                <span className="h-2.5 w-2.5 rounded-full bg-rose-300" />
                <span className="h-2.5 w-2.5 rounded-full bg-amber-300" />
                <span className="h-2.5 w-2.5 rounded-full bg-emerald-300" />
                <span className="ml-3 truncate text-xs text-slate-400">{breadcrumb}</span>
            </div>
            <div className="p-5 sm:p-6">
                <p className="mb-5 text-base font-semibold text-slate-900">{title}</p>
                {children}
            </div>
        </div>
    );
}

function DashboardPanel() {
    const tiles = [
        { label: 'Documents récents', icon: Clock3, tone: 'bg-blue-600' },
        { label: 'Favoris', icon: Star, tone: 'bg-amber-500' },
        { label: 'Partagés avec moi', icon: Share2, tone: 'bg-indigo-600' },
        { label: 'Import', icon: Upload, tone: 'bg-cyan-600' },
    ];
    return (
        <MockShell title="Bonjour, voici votre espace documentaire" breadcrumb="Tableau de bord">
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {tiles.map((tile, index) => (
                    <div key={tile.label} className="lp-enter rounded-xl border border-slate-100 p-4" style={{ '--lp-delay': `${index * 80}ms` }}>
                        <span className={`mb-3 flex h-9 w-9 items-center justify-center rounded-lg text-white ${tile.tone}`}>
                            <tile.icon className="h-4 w-4" />
                        </span>
                        <p className="text-xs font-medium text-slate-600">{tile.label}</p>
                        <div className="mt-2 h-1.5 w-2/3 rounded-full bg-slate-100" />
                    </div>
                ))}
            </div>
            <div className="mt-5 grid gap-4 sm:grid-cols-5">
                <div className="rounded-xl border border-slate-100 p-4 sm:col-span-3">
                    <p className="mb-3 text-xs font-semibold text-slate-500">Activité récente</p>
                    <ul className="space-y-2.5">
                        {['Contrat de bail — Siège', 'Facture F-2026-0142', 'Plan de formation annuel'].map((name, index) => (
                            <li key={name} className="lp-enter flex items-center gap-3 text-sm" style={{ '--lp-delay': `${200 + index * 90}ms` }}>
                                <FileText className="h-4 w-4 text-blue-500" />
                                <span className="flex-1 truncate text-slate-700">{name}</span>
                                <span className="text-xs text-slate-400">consulté</span>
                            </li>
                        ))}
                    </ul>
                </div>
                <div className="rounded-xl border border-slate-100 p-4 sm:col-span-2">
                    <p className="mb-3 text-xs font-semibold text-slate-500">Structure</p>
                    <ul className="space-y-2 text-sm text-slate-700">
                        {['Direction Financière', 'Ressources Humaines', 'Direction Juridique'].map((name) => (
                            <li key={name} className="flex items-center gap-2"><FolderOpen className="h-4 w-4 text-indigo-500" />{name}</li>
                        ))}
                    </ul>
                </div>
            </div>
        </MockShell>
    );
}

function PreviewPanel() {
    return (
        <MockShell title="Contrat de bail — Siège.pdf" breadcrumb="Direction Juridique › Contrats › Contrat">
            <div className="grid gap-5 sm:grid-cols-5">
                <div className="lp-enter rounded-xl bg-slate-100/80 p-4 sm:col-span-3">
                    <div className="mx-auto aspect-[3/4] max-w-[270px] rounded-lg bg-white p-5 border border-slate-200/80 flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between border-b border-slate-100 pb-3 mb-3">
                                <div>
                                    <p className="text-[10px] font-bold uppercase tracking-wider text-slate-900">Bail Commercial</p>
                                    <p className="text-[9px] text-slate-400">Réf: CONTRAT-2026-PARIS</p>
                                </div>
                                <span className="rounded bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 border border-emerald-200">Signé</span>
                            </div>

                            <p className="text-[9.5px] font-medium text-slate-700 mb-2 leading-relaxed">
                                ENTRE LES SOUSSIGNÉS : Direction Générale et Société Immobilière.
                            </p>

                            <div className="space-y-1.5">
                                <div className="h-1.5 w-full rounded bg-slate-100" />
                                <div className="h-1.5 w-11/12 rounded bg-slate-100" />
                                <div className="h-1.5 w-4/5 rounded bg-slate-100" />
                                <div className="h-1.5 w-full rounded bg-slate-100" />
                                <div className="h-1.5 w-3/4 rounded bg-slate-100" />
                            </div>

                            <div className="mt-4 rounded-md bg-blue-50/70 border border-blue-100 p-2 text-[9px] text-blue-800">
                                <span className="font-semibold">Traitement OCR actif :</span> Texte intégral indexé et interrogeable.
                            </div>
                        </div>

                        <div className="pt-3 border-t border-slate-100 flex items-center justify-between text-[8.5px] text-slate-400">
                            <span>Page 1 sur 14</span>
                            <span className="font-mono text-slate-500">SHA256: 4e8b9...</span>
                        </div>
                    </div>
                </div>
                <div className="space-y-3 sm:col-span-2">
                    {[
                        { icon: Download, label: 'Télécharger l’original' },
                        { icon: Share2, label: 'Partager avec la Direction' },
                        { icon: MessageSquare, label: 'Consulter les annotations' },
                        { icon: Star, label: 'Ajouter aux favoris' },
                    ].map((action, index) => (
                        <div key={action.label} className="lp-enter flex items-center gap-3 rounded-xl border border-slate-100 bg-white px-4 py-3 text-sm text-slate-700 hover:border-blue-200 transition-colors" style={{ '--lp-delay': `${index * 80}ms` }}>
                            <action.icon className="h-4 w-4 text-blue-600" />
                            {action.label}
                        </div>
                    ))}
                </div>
            </div>
        </MockShell>
    );
}

function SearchPanel() {
    return (
        <MockShell title="Recherche par critères métier" breadcrumb="Recherche">
            <div className="grid gap-3 sm:grid-cols-4">
                {[
                    ['Direction', 'Direction Financière'],
                    ['Service', 'Comptabilité'],
                    ['Type', 'Facture'],
                    ['Fournisseur', 'Société Alpha'],
                ].map(([label, value], index) => (
                    <div key={label} className="lp-enter rounded-xl border border-slate-200 px-3 py-2.5" style={{ '--lp-delay': `${index * 70}ms` }}>
                        <p className="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                        <p className="truncate text-sm text-slate-800">{value}</p>
                    </div>
                ))}
            </div>
            <ul className="mt-5 space-y-2">
                {['Facture F-2026-0142', 'Facture F-2026-0098', 'Facture F-2026-0031'].map((name, index) => (
                    <li key={name} className="lp-enter flex items-center gap-3 rounded-xl border border-slate-100 px-4 py-3 text-sm" style={{ '--lp-delay': `${300 + index * 90}ms` }}>
                        <FileText className="h-4 w-4 text-blue-600" />
                        <span className="flex-1 truncate font-medium text-slate-800">{name}</span>
                        <span className="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Actif</span>
                    </li>
                ))}
            </ul>
        </MockShell>
    );
}

function MetadataPanel() {
    const fields = [
        ['Fournisseur', 'Société Alpha', UserRound],
        ['Numéro de facture', 'F-2026-0142', Tag],
        ['Date d’échéance', '30/04/2026', CalendarDays],
        ['Montant TTC', '1 250 000 FCFA', FileText],
    ];
    return (
        <MockShell title="Type documentaire : Facture fournisseur" breadcrumb="Direction Financière › Comptabilité › Facture">
            <div className="grid gap-3 sm:grid-cols-2">
                {fields.map(([label, value, Icon], index) => (
                    <div key={label} className="lp-enter rounded-xl border border-slate-200 p-4" style={{ '--lp-delay': `${index * 80}ms` }}>
                        <p className="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-500"><Icon className="h-3.5 w-3.5 text-indigo-500" />{label}</p>
                        <p className="text-sm font-medium text-slate-900">{value}</p>
                    </div>
                ))}
            </div>
            <p className="mt-4 flex items-center gap-2 text-xs text-slate-500">
                <CheckCircle2 className="h-4 w-4 text-emerald-500" /> Champs définis par votre administrateur pour ce type documentaire.
            </p>
        </MockShell>
    );
}

function VersionsPanel() {
    const versions = [
        { number: 'v3', label: 'Version courante', author: 'A. Koné', date: '12/03/2026', isCurrent: true },
        { number: 'v2', label: 'Mise à jour des montants', author: 'J. Mensah', date: '10/03/2026' },
        { number: 'v1', label: 'Import initial', author: 'A. Koné', date: '04/03/2026' },
    ];
    return (
        <MockShell title="Historique des versions" breadcrumb="Facture F-2026-0142 › Versions">
            <ol className="relative space-y-4 border-l-2 border-slate-100 pl-6">
                {versions.map((version, index) => (
                    <li key={version.number} className="lp-enter relative" style={{ '--lp-delay': `${index * 110}ms` }}>
                        <span className={`absolute -left-[33px] top-3 h-4 w-4 rounded-full ring-4 ring-white ${version.isCurrent ? 'bg-blue-600' : 'bg-slate-300'}`} />
                        <div className={`flex flex-wrap items-center gap-3 rounded-xl border p-4 ${version.isCurrent ? 'border-blue-200 bg-blue-50/50' : 'border-slate-100'}`}>
                            <span className={`rounded-lg px-2 py-1 text-xs font-bold ${version.isCurrent ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600'}`}>{version.number}</span>
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-semibold text-slate-900">{version.label}</p>
                                <p className="text-xs text-slate-500">{version.author} · {version.date}</p>
                            </div>
                        </div>
                    </li>
                ))}
            </ol>
        </MockShell>
    );
}

const panels = {
    dashboard: DashboardPanel,
    preview: PreviewPanel,
    search: SearchPanel,
    metadata: MetadataPanel,
    versions: VersionsPanel,
};

/**
 * WAI-ARIA tabs (arrow keys, Home/End) switching between product mockups.
 */
export default function ProductShowcase() {
    const [activeTabId, setActiveTabId] = useState('dashboard');
    const tabRefs = useRef({});
    const ActivePanel = panels[activeTabId];

    const focusTab = (index) => {
        const tab = tabs[(index + tabs.length) % tabs.length];
        setActiveTabId(tab.id);
        tabRefs.current[tab.id]?.focus();
    };

    const handleKeyDown = (event, index) => {
        const keyActions = {
            ArrowRight: () => focusTab(index + 1),
            ArrowDown: () => focusTab(index + 1),
            ArrowLeft: () => focusTab(index - 1),
            ArrowUp: () => focusTab(index - 1),
            Home: () => focusTab(0),
            End: () => focusTab(tabs.length - 1),
        };
        if (keyActions[event.key]) {
            event.preventDefault();
            keyActions[event.key]();
        }
    };

    return (
        <div className="grid gap-8 lg:grid-cols-12 lg:gap-10">
            <div className="lg:col-span-4">
                <div role="tablist" aria-label="Démonstration du produit" aria-orientation="vertical" className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2 lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0">
                    {tabs.map((tab, index) => {
                        const isActive = tab.id === activeTabId;
                        return (
                            <button
                                key={tab.id}
                                ref={(element) => { tabRefs.current[tab.id] = element; }}
                                id={`demo-tab-${tab.id}`}
                                type="button"
                                role="tab"
                                aria-selected={isActive}
                                aria-controls={`demo-panel-${tab.id}`}
                                tabIndex={isActive ? 0 : -1}
                                onClick={() => setActiveTabId(tab.id)}
                                onKeyDown={(event) => handleKeyDown(event, index)}
                                className={`group flex shrink-0 items-center gap-3 rounded-2xl border px-4 py-3 text-left text-sm font-semibold transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 lg:px-5 lg:py-4 ${
                                    isActive
                                        ? 'border-blue-200 bg-white text-slate-900'
                                        : 'border-transparent text-slate-500 hover:bg-white/70 hover:text-slate-800'
                                }`}
                            >
                                <span
                                    className={`flex h-9 w-9 items-center justify-center rounded-xl transition-colors ${
                                        isActive ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:text-blue-600'
                                    }`}
                                >
                                    <tab.icon className="h-4 w-4" aria-hidden="true" />
                                </span>
                                <span className="whitespace-nowrap">{tab.label}</span>
                            </button>
                        );
                    })}
                </div>
                <div className="mt-6 hidden lg:block">
                    <IllustrativeBadge />
                </div>
            </div>

            <div className="lg:col-span-8">
                <div
                    key={activeTabId}
                    id={`demo-panel-${activeTabId}`}
                    role="tabpanel"
                    aria-labelledby={`demo-tab-${activeTabId}`}
                    tabIndex={0}
                    className="lp-enter-pop rounded-3xl focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                >
                    <ActivePanel />
                </div>
                <div className="mt-4 lg:hidden">
                    <IllustrativeBadge />
                </div>
            </div>
        </div>
    );
}
