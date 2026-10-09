import React, { useId, useMemo, useState } from 'react';
import { Download, Eye, FileText, Filter, Search, SearchX } from 'lucide-react';

/** Fictitious sample catalogue used only by this frontend demonstration. */
const sampleCatalogue = [
    { id: 1, name: 'Facture F-2026-0142', direction: 'finance', service: 'Comptabilité', type: 'Facture', year: '2026', date: '12/03/2026', status: 'Actif' },
    { id: 2, name: 'Facture F-2026-0098', direction: 'finance', service: 'Comptabilité', type: 'Facture', year: '2026', date: '21/02/2026', status: 'Actif' },
    { id: 3, name: 'Facture F-2025-1187', direction: 'finance', service: 'Comptabilité', type: 'Facture', year: '2025', date: '18/12/2025', status: 'Archivé' },
    { id: 4, name: 'Budget prévisionnel 2026', direction: 'finance', service: 'Contrôle de gestion', type: 'Rapport', year: '2026', date: '05/01/2026', status: 'Actif' },
    { id: 5, name: 'Contrat de travail — Agent', direction: 'rh', service: 'Administration du personnel', type: 'Contrat', year: '2026', date: '02/03/2026', status: 'Actif' },
    { id: 6, name: 'Plan de formation annuel', direction: 'rh', service: 'Formation', type: 'Rapport', year: '2025', date: '14/11/2025', status: 'Actif' },
    { id: 7, name: 'Contrat de bail — Siège', direction: 'juridique', service: 'Contrats', type: 'Contrat', year: '2025', date: '30/09/2025', status: 'Actif' },
    { id: 8, name: 'Procès-verbal AG 2026', direction: 'juridique', service: 'Affaires sociétaires', type: 'Procès-verbal', year: '2026', date: '28/02/2026', status: 'Actif' },
];

const directions = [
    { value: 'finance', label: 'Direction Financière', services: ['Comptabilité', 'Contrôle de gestion'] },
    { value: 'rh', label: 'Ressources Humaines', services: ['Administration du personnel', 'Formation'] },
    { value: 'juridique', label: 'Direction Juridique', services: ['Contrats', 'Affaires sociétaires'] },
];

const documentTypes = ['Facture', 'Contrat', 'Rapport', 'Procès-verbal'];
const years = ['2026', '2025'];

const fieldClass =
    'w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 transition focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/15';

/**
 * Frontend-only search demonstration filtering a small fictitious catalogue.
 * No request is sent to the server.
 */
export default function SearchDemo() {
    const fieldId = useId();
    const [query, setQuery] = useState('');
    const [direction, setDirection] = useState('finance');
    const [service, setService] = useState('Comptabilité');
    const [documentType, setDocumentType] = useState('Facture');
    const [year, setYear] = useState('2026');

    const availableServices = useMemo(() => {
        const selectedDirection = directions.find((item) => item.value === direction);
        return selectedDirection ? selectedDirection.services : directions.flatMap((item) => item.services);
    }, [direction]);

    const results = useMemo(() => {
        const normalizedQuery = query.trim().toLowerCase();
        return sampleCatalogue.filter(
            (document) =>
                (!direction || document.direction === direction) &&
                (!service || document.service === service) &&
                (!documentType || document.type === documentType) &&
                (!year || document.year === year) &&
                (!normalizedQuery || document.name.toLowerCase().includes(normalizedQuery)),
        );
    }, [query, direction, service, documentType, year]);

    const resultsKey = `${direction}-${service}-${documentType}-${year}-${query}`;

    const handleDirectionChange = (event) => {
        setDirection(event.target.value);
        setService('');
    };

    const resetFilters = () => {
        setQuery('');
        setDirection('');
        setService('');
        setDocumentType('');
        setYear('');
    };

    return (
        <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white">
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/80 px-5 py-4 sm:px-6">
                <p className="flex items-center gap-2 text-sm font-semibold text-slate-900">
                    <Filter className="h-4 w-4 text-blue-600" aria-hidden="true" /> Recherche documentaire
                </p>
                
            </div>

            <div className="grid lg:grid-cols-12">
                {/* Filters */}
                <form
                    className="space-y-4 border-b border-slate-100 p-5 sm:p-6 lg:col-span-4 lg:border-b-0 lg:border-r"
                    onSubmit={(event) => event.preventDefault()}
                    aria-label="Critères de recherche (démonstration)"
                >
                    <div>
                        <label htmlFor={`${fieldId}-q`} className="mb-1.5 block text-xs font-semibold text-slate-600">Mots-clés</label>
                        <div className="relative">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                            <input
                                id={`${fieldId}-q`}
                                type="search"
                                value={query}
                                onChange={(event) => setQuery(event.target.value)}
                                placeholder="Ex. : F-2026"
                                className={`${fieldClass} pl-9`}
                            />
                        </div>
                    </div>
                    <div>
                        <label htmlFor={`${fieldId}-direction`} className="mb-1.5 block text-xs font-semibold text-slate-600">Direction</label>
                        <select id={`${fieldId}-direction`} value={direction} onChange={handleDirectionChange} className={fieldClass}>
                            <option value="">Toutes les Directions</option>
                            {directions.map((item) => (
                                <option key={item.value} value={item.value}>{item.label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor={`${fieldId}-service`} className="mb-1.5 block text-xs font-semibold text-slate-600">Service</label>
                        <select id={`${fieldId}-service`} value={service} onChange={(event) => setService(event.target.value)} className={fieldClass}>
                            <option value="">Tous les Services</option>
                            {availableServices.map((item) => (
                                <option key={item} value={item}>{item}</option>
                            ))}
                        </select>
                    </div>
                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <label htmlFor={`${fieldId}-type`} className="mb-1.5 block text-xs font-semibold text-slate-600">Type documentaire</label>
                            <select id={`${fieldId}-type`} value={documentType} onChange={(event) => setDocumentType(event.target.value)} className={fieldClass}>
                                <option value="">Tous</option>
                                {documentTypes.map((item) => (
                                    <option key={item} value={item}>{item}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor={`${fieldId}-year`} className="mb-1.5 block text-xs font-semibold text-slate-600">Métadonnée : année</label>
                            <select id={`${fieldId}-year`} value={year} onChange={(event) => setYear(event.target.value)} className={fieldClass}>
                                <option value="">Toutes</option>
                                {years.map((item) => (
                                    <option key={item} value={item}>{item}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <button
                        type="button"
                        onClick={resetFilters}
                        className="w-full rounded-xl border border-slate-200 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-blue-300 hover:text-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
                    >
                        Réinitialiser les critères
                    </button>
                </form>

                {/* Results */}
                <div className="p-5 sm:p-6 lg:col-span-8">
                    <p className="mb-4 text-sm text-slate-500" aria-live="polite">
                        <span className="font-semibold text-slate-900">{results.length}</span> document{results.length > 1 ? 's' : ''} correspondant{results.length > 1 ? 's' : ''}
                    </p>

                    {results.length === 0 ? (
                        <div className="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 py-14 text-center">
                            <SearchX className="mb-3 h-8 w-8 text-slate-300" aria-hidden="true" />
                            <p className="text-sm font-medium text-slate-600">Aucun document ne correspond à ces critères.</p>
                        </div>
                    ) : (
                        <ul key={resultsKey} className="space-y-2.5">
                            {results.map((document, index) => (
                                <li
                                    key={document.id}
                                    className="lp-enter flex flex-col gap-3 rounded-2xl border border-slate-100 bg-white p-3.5 transition hover:border-blue-200 sm:flex-row sm:items-center"
                                    style={{ '--lp-delay': `${index * 70}ms` }}
                                >
                                    <div className="flex min-w-0 flex-1 items-center gap-3">
                                        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                            <FileText className="h-5 w-5" aria-hidden="true" />
                                        </span>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-semibold text-slate-900">{document.name}</p>
                                            <p className="truncate text-xs text-slate-500">
                                                {document.type} · {document.service} · {document.date}
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 sm:shrink-0">
                                        <span
                                            className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${
                                                document.status === 'Actif' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'
                                            }`}
                                        >
                                            {document.status}
                                        </span>
                                        <span className="ml-auto flex items-center gap-1 text-slate-400 sm:ml-2" title="Actions illustratives" aria-hidden="true">
                                            <span className="rounded-lg p-1.5"><Eye className="h-4 w-4" /></span>
                                            <span className="rounded-lg p-1.5"><Download className="h-4 w-4" /></span>
                                        </span>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </div>
    );
}
