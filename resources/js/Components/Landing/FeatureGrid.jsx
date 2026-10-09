import React from 'react';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, FileSearch, FolderTree, History, KeyRound, ScanText, Search, Share2, UploadCloud } from 'lucide-react';
import Reveal from './Reveal';

/** Tiny decorative visuals, one per card, animated on card hover only. */
const visuals = {
    tree: (
        <div className="space-y-1.5 text-[10px] text-slate-500">
            <div className="flex items-center gap-1.5"><span className="h-1.5 w-1.5 rounded-sm bg-blue-500" />Direction</div>
            <div className="ml-3 flex items-center gap-1.5 transition-transform duration-500 group-hover:translate-x-1"><span className="h-1.5 w-1.5 rounded-sm bg-indigo-500" />Service</div>
            <div className="ml-6 flex items-center gap-1.5 transition-transform duration-500 group-hover:translate-x-2"><span className="h-1.5 w-1.5 rounded-sm bg-cyan-500" />Type documentaire</div>
        </div>
    ),
    tags: (
        <div className="flex flex-wrap gap-1.5">
            {['Fournisseur', 'Échéance', 'Montant'].map((tag, index) => (
                <span key={tag} className="rounded-md bg-cyan-50 px-2 py-0.5 text-[10px] font-medium text-cyan-700 transition-transform duration-500 group-hover:-translate-y-0.5" style={{ transitionDelay: `${index * 60}ms` }}>{tag}</span>
            ))}
        </div>
    ),
    search: (
        <div className="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-[10px] text-slate-500">
            <Search className="h-3 w-3 text-blue-500" />
            <span className="flex-1 truncate">Facture · 2026</span>
            <span className="h-3 w-px bg-blue-500 opacity-0 transition-opacity group-hover:opacity-100 lp-caret" />
        </div>
    ),
    versions: (
        <div className="flex items-center gap-1.5">
            {['v1', 'v2', 'v3'].map((version, index) => (
                <React.Fragment key={version}>
                    <span className={`rounded-md px-1.5 py-0.5 text-[10px] font-bold transition-colors duration-500 ${index === 2 ? 'bg-slate-100 text-slate-500 group-hover:bg-blue-600 group-hover:text-white' : 'bg-slate-100 text-slate-500'}`}>{version}</span>
                    {index < 2 && <span className="h-px w-4 bg-slate-200" />}
                </React.Fragment>
            ))}
        </div>
    ),
    share: (
        <div className="flex -space-x-2">
            {['bg-blue-600', 'bg-indigo-600', 'bg-cyan-600'].map((tone, index) => (
                <span key={tone} className={`h-6 w-6 rounded-full ring-2 ring-white transition-transform duration-500 ${tone} ${index === 2 ? 'group-hover:translate-x-1.5' : index === 1 ? 'group-hover:translate-x-0.5' : ''}`} />
            ))}
        </div>
    ),
    permissions: (
        <div className="flex gap-1.5 text-[10px] font-medium">
            <span className="rounded-md bg-emerald-50 px-2 py-0.5 text-emerald-700">Lecture</span>
            <span className="rounded-md bg-emerald-50 px-2 py-0.5 text-emerald-700">Écriture</span>
            <span className="rounded-md bg-slate-100 px-2 py-0.5 text-slate-400 line-through decoration-slate-300">Suppression</span>
        </div>
    ),
    audit: (
        <div className="space-y-1 font-mono text-[9.5px] text-slate-500">
            <div className="truncate"><span className="text-blue-600">consultation</span> · document</div>
            <div className="truncate transition-opacity duration-500 group-hover:opacity-100 sm:opacity-60"><span className="text-indigo-600">nouvelle version</span> · v3</div>
        </div>
    ),
    ocr: (
        <div className="relative h-9 w-full overflow-hidden rounded-md border border-amber-200/60 bg-amber-50/40">
            <img
                src="/images/landing/ocr-intelligence.jpg"
                alt="Illustration numérisation OCR"
                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
                width="160"
                height="36"
            />
            <div className="absolute bottom-0 inset-x-0 bg-slate-950/85 px-1.5 py-0.5">
                <span className="text-[9px] font-semibold text-amber-200">Recherche plein texte</span>
            </div>
        </div>
    ),
};

const features = [
    { icon: FolderTree, title: 'Organisation documentaire', description: 'Structurez vos documents par Direction, Service et type documentaire, à l’image de votre organisation.', visual: 'tree', tone: 'bg-blue-600' },
    { icon: UploadCloud, title: 'Import et métadonnées', description: 'Importez vos fichiers et décrivez-les avec les métadonnées propres à chaque type documentaire.', visual: 'tags', tone: 'bg-cyan-600' },
    { icon: FileSearch, title: 'Recherche documentaire', description: 'Retrouvez un document en combinant mots-clés, structure, type et valeurs de métadonnées.', visual: 'search', tone: 'bg-indigo-600' },
    { icon: History, title: 'Versions et historique', description: 'Chaque nouvelle version est conservée : consultez l’historique sans perdre les états précédents.', visual: 'versions', tone: 'bg-violet-600' },
    { icon: Share2, title: 'Partage et collaboration', description: 'Partagez des documents avec vos collègues, commentez et suivez les échanges.', visual: 'share', tone: 'bg-sky-600' },
    { icon: KeyRound, title: 'Permissions et accès', description: 'Rôles, permissions et périmètres d’accès déterminent qui peut voir ou modifier chaque document.', visual: 'permissions', tone: 'bg-emerald-600' },
    { icon: ScanText, title: 'Audit et traçabilité', description: 'Les actions importantes sont journalisées pour savoir qui a fait quoi, et quand.', visual: 'audit', tone: 'bg-slate-700' },
    { icon: Search, title: 'OCR et recherche plein texte', description: 'Le texte des documents traités par OCR devient interrogeable par la recherche, selon le quota de votre offre.', visual: 'ocr', tone: 'bg-amber-600' },
];

/**
 * Grid of interactive feature cards linking to the detailed features page.
 */
export default function FeatureGrid() {
    return (
        <ul className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {features.map((feature, index) => (
                <Reveal as="li" key={feature.title} delay={(index % 4) * 90}>
                    <Link
                        href="/fonctionnalites"
                        className="group relative flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 transition-all duration-300 hover:-translate-y-1 hover:border-blue-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                    >
                        <span className={`flex h-11 w-11 items-center justify-center rounded-2xl text-white transition-transform duration-300 group-hover:-rotate-6 group-hover:scale-105 ${feature.tone}`}>
                            <feature.icon className="h-5 w-5" aria-hidden="true" />
                        </span>
                        <h3 className="mt-5 flex items-center gap-1.5 text-base font-semibold text-slate-950">
                            {feature.title}
                            <ArrowUpRight className="h-4 w-4 -translate-x-1 text-blue-600 opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100" aria-hidden="true" />
                        </h3>
                        <p className="mt-2 flex-1 text-sm leading-relaxed text-ink">{feature.description}</p>
                        <div className="mt-5 rounded-2xl border border-slate-100 bg-slate-50/80 p-3" aria-hidden="true">
                            {visuals[feature.visual]}
                        </div>
                    </Link>
                </Reveal>
            ))}
        </ul>
    );
}
