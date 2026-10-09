import React from 'react';
import {
    Building2,
    CheckCircle2,
    ChevronRight,
    FileText,
    FolderTree,
    History,
    LayoutDashboard,
    Lock,
    Search,
    ShieldCheck,
    Star,
    Tag,
    Users,
} from 'lucide-react';

const sampleDocuments = [
    { name: 'Facture_F-2026-0142.pdf', type: 'Facture fournisseur', date: '12/03/2026', tone: 'text-rose-400 bg-rose-500/10' },
    { name: 'Contrat_maintenance.pdf', type: 'Contrat', date: '08/03/2026', tone: 'text-blue-400 bg-blue-500/10' },
    { name: 'PV_comite_direction.docx', type: 'Procès-verbal', date: '04/03/2026', tone: 'text-indigo-300 bg-indigo-500/10' },
    { name: 'Releve_bancaire_fev.pdf', type: 'Relevé', date: '01/03/2026', tone: 'text-cyan-300 bg-cyan-500/10' },
];

const delay = (milliseconds) => ({ '--lp-delay': `${milliseconds}ms` });

/**
 * Illustrative composition of the APPGED workspace shown in the hero.
 * Purely decorative: all labels are fictitious sample data.
 */
export default function HeroVisual() {
    return (
        <div className="relative mx-auto w-full max-w-[640px] lg:max-w-none" aria-hidden="true">
            {/* Halo */}
            <div className="absolute -inset-10 -z-10 rounded-[3rem] bg-blue-600/10 blur-3xl lp-enter-fade" style={delay(300)} />

            {/* Main window */}
            <div className="lp-enter-pop overflow-hidden rounded-2xl border border-white/10 bg-night-soft/90 ring-1 ring-white/5 backdrop-blur" style={delay(450)}>
                {/* Window chrome */}
                <div className="flex items-center gap-2 border-b border-white/5 bg-white/[0.02] px-4 py-3">
                    <span className="h-2.5 w-2.5 rounded-full bg-rose-400/70" />
                    <span className="h-2.5 w-2.5 rounded-full bg-amber-400/70" />
                    <span className="h-2.5 w-2.5 rounded-full bg-emerald-400/70" />
                    <div className="ml-3 flex flex-1 items-center gap-2 rounded-lg bg-white/5 px-3 py-1.5 text-[11px] text-slate-400">
                        <Search className="h-3.5 w-3.5" />
                        <span className="truncate">Rechercher : « facture fournisseur 2026 »</span>
                        <span className="ml-0.5 h-3 w-px bg-cyan-300 lp-caret" />
                    </div>
                </div>

                <div className="grid grid-cols-12">
                    {/* Sidebar tree */}
                    <div className="col-span-4 hidden border-r border-white/5 p-4 sm:block">
                        <p className="mb-3 flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            <FolderTree className="h-3 w-3" /> Arborescence
                        </p>
                        <ul className="space-y-1.5 text-[11.5px]">
                            <li className="flex items-center gap-1.5 text-slate-300 lp-enter" style={delay(700)}>
                                <Building2 className="h-3.5 w-3.5 text-blue-400" /> Organisation
                            </li>
                            <li className="ml-3 flex items-center gap-1.5 text-slate-300 lp-enter" style={delay(800)}>
                                <ChevronRight className="h-3 w-3 text-slate-500" /> Direction Financière
                            </li>
                            <li className="ml-6 flex items-center gap-1.5 text-slate-300 lp-enter" style={delay(900)}>
                                <ChevronRight className="h-3 w-3 text-slate-500" /> Comptabilité
                            </li>
                            <li className="ml-9 flex items-center gap-1.5 rounded-md bg-blue-500/15 px-1.5 py-1 font-medium text-blue-200 ring-1 ring-blue-400/20 lp-enter" style={delay(1000)}>
                                <FileText className="h-3 w-3" /> Factures
                            </li>
                            <li className="ml-3 flex items-center gap-1.5 text-slate-500 lp-enter" style={delay(1100)}>
                                <ChevronRight className="h-3 w-3" /> Ressources Humaines
                            </li>
                            <li className="ml-3 flex items-center gap-1.5 text-slate-500 lp-enter" style={delay(1150)}>
                                <ChevronRight className="h-3 w-3" /> Direction Juridique
                            </li>
                        </ul>

                        <div className="mt-5 space-y-1.5 border-t border-white/5 pt-4 text-[11px] text-slate-500">
                            <p className="flex items-center gap-1.5"><LayoutDashboard className="h-3 w-3" /> Tableau de bord</p>
                            <p className="flex items-center gap-1.5"><Star className="h-3 w-3" /> Favoris</p>
                            <p className="flex items-center gap-1.5"><Users className="h-3 w-3" /> Partages</p>
                        </div>
                    </div>

                    {/* Document list */}
                    <div className="col-span-12 p-4 sm:col-span-8">
                        <div className="mb-3 flex items-center justify-between">
                            <div>
                                <p className="text-[10px] text-slate-500">Direction Financière › Comptabilité</p>
                                <p className="text-sm font-semibold text-white">Factures</p>
                            </div>
                            <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-1 text-[10px] font-medium text-emerald-300 ring-1 ring-emerald-400/20">
                                <ShieldCheck className="h-3 w-3" /> Accès contrôlé
                            </span>
                        </div>

                        <ul className="space-y-2">
                            {sampleDocuments.map((document, index) => (
                                <li
                                    key={document.name}
                                    className={`lp-enter flex items-center gap-3 rounded-xl border px-3 py-2.5 ${index === 0 ? 'border-blue-400/30 bg-blue-500/10' : 'border-white/5 bg-white/[0.02]'
                                        }`}
                                    style={delay(900 + index * 120)}
                                >
                                    <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ${document.tone}`}>
                                        <FileText className="h-4 w-4" />
                                    </span>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-[12px] font-medium text-slate-100">{document.name}</span>
                                        <span className="block truncate text-[10.5px] text-slate-500">{document.type} · {document.date}</span>
                                    </span>
                                    {index === 0 && (
                                        <span className="hidden rounded-md bg-blue-500/20 px-1.5 py-0.5 text-[10px] font-medium text-blue-200 sm:inline">v3</span>
                                    )}
                                </li>
                            ))}
                        </ul>

                        {/* Classification indicator */}
                        <div className="mt-4 rounded-xl border border-white/5 bg-white/[0.02] p-3 lp-enter" style={delay(1500)}>
                            <div className="mb-2 flex items-center justify-between text-[10.5px] text-slate-400">
                                <span>Métadonnées renseignées</span>
                                <span className="text-cyan-300">Exemple</span>
                            </div>
                            <div className="flex gap-1.5">
                                <span className="h-1.5 flex-[5] rounded-full bg-blue-500" />
                                <span className="h-1.5 flex-[3] rounded-full bg-cyan-400/70" />
                                <span className="h-1.5 flex-[2] rounded-full bg-white/10" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Floating card: metadata */}
            <div className="absolute -left-6 top-24 hidden w-52 md:block xl:-left-14">
                <div className="lp-float" style={delay(0)}>
                    <div className="lp-enter-pop rounded-2xl border border-white/10 bg-night/90 p-4 backdrop-blur-xl" style={delay(1300)}>
                        <p className="mb-2.5 flex items-center gap-1.5 text-[11px] font-semibold text-white">
                            <Tag className="h-3.5 w-3.5 text-cyan-300" /> Métadonnées
                        </p>
                        <dl className="space-y-1.5 text-[10.5px]">
                            <div className="flex justify-between gap-2"><dt className="text-slate-500">Fournisseur</dt><dd className="text-slate-200">Société Alpha</dd></div>
                            <div className="flex justify-between gap-2"><dt className="text-slate-500">Montant</dt><dd className="text-slate-200">1 250 000</dd></div>
                            <div className="flex justify-between gap-2"><dt className="text-slate-500">Échéance</dt><dd className="text-slate-200">30/04/2026</dd></div>
                        </dl>
                    </div>
                </div>
            </div>

            {/* Floating card: versions */}
            <div className="absolute -right-4 -top-8 hidden w-48 md:block xl:-right-10">
                <div className="lp-float" style={delay(1800)}>
                    <div className="lp-enter-pop rounded-2xl border border-white/10 bg-night/90 p-4 backdrop-blur-xl" style={delay(1500)}>
                        <p className="mb-2.5 flex items-center gap-1.5 text-[11px] font-semibold text-white">
                            <History className="h-3.5 w-3.5 text-indigo-300" /> Versions
                        </p>
                        <ol className="relative space-y-2 border-l border-white/10 pl-3 text-[10.5px]">
                            <li className="relative text-slate-200"><span className="absolute -left-[17px] top-1 h-2 w-2 rounded-full bg-cyan-400" />v3 · version courante</li>
                            <li className="relative text-slate-400"><span className="absolute -left-[17px] top-1 h-2 w-2 rounded-full bg-slate-600" />v2 · révisée</li>
                            <li className="relative text-slate-500"><span className="absolute -left-[17px] top-1 h-2 w-2 rounded-full bg-slate-700" />v1 · import initial</li>
                        </ol>
                    </div>
                </div>
            </div>

            {/* Floating card: security + collaboration */}
            <div className="absolute -bottom-8 right-6 hidden w-60 sm:block xl:-right-6">
                <div className="lp-float" style={delay(3200)}>
                    <div className="lp-enter-pop flex items-center gap-3 rounded-2xl border border-white/10 bg-night/90 p-3.5 backdrop-blur-xl" style={delay(1700)}>
                        <span className="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-300">
                            <span className="absolute inset-0 rounded-xl bg-emerald-400/20 lp-pulse-ring" />
                            <Lock className="h-4 w-4" />
                        </span>
                        <div className="min-w-0 flex-1">
                            <p className="flex items-center gap-1 text-[11px] font-semibold text-white">
                                <CheckCircle2 className="h-3.5 w-3.5 text-emerald-400" /> Permission vérifiée
                            </p>
                            <p className="truncate text-[10px] text-slate-500">Rôle : Comptable · lecture</p>
                        </div>
                        <div className="flex -space-x-2">
                            <img
                                src="/images/landing/avatar-aminata.jpg"
                                alt="Aminata Koné"
                                className="h-6 w-6 rounded-full object-cover ring-2 ring-night"
                            />
                            <img
                                src="/images/landing/avatar-jean.jpg"
                                alt="Jean Mensah"
                                className="h-6 w-6 rounded-full object-cover ring-2 ring-night"
                            />
                            <img
                                src="/images/landing/avatar-sophie.jpg"
                                alt="Sophie Diallo"
                                className="h-6 w-6 rounded-full object-cover ring-2 ring-night"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
