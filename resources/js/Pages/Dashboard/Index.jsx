import React from 'react';

export default function DashboardIndex({
    user,
    organization,
    period = '30d',
    statistics = {},
    recent_documents = [],
    favorites = [],
    workflows = { pending_my_action: [], in_progress_count: 0 },
    notifications = { unread_count: 0, recent: [] },
    recent_activity = [],
    charts = { by_type: {}, by_status: {}, timeline: [] }
}) {
    const formatNumber = (val) => new Intl.NumberFormat('fr-FR').format(val || 0);

    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 antialiased font-sans">
            {/* Header */}
            <header className="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-30 shadow-xs">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex justify-between items-center">
                    <div className="flex items-center space-x-4">
                        <div className="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-200 dark:shadow-none">
                            GED
                        </div>
                        <div>
                            <span className="text-lg font-bold text-slate-900 dark:text-white">Espace Documentaire</span>
                            <span className="hidden sm:inline-block ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300">
                                {organization?.name || 'Organisation'}
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center space-x-3">
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300">
                            🔔 {notifications?.unread_count || 0} non lue(s)
                        </span>
                        <div className="text-right hidden sm:block">
                            <p className="text-sm font-semibold text-slate-900 dark:text-white">{user?.full_name}</p>
                            <p className="text-xs text-slate-500 dark:text-slate-400 capitalize">{user?.role}</p>
                        </div>
                    </div>
                </div>
            </header>

            {/* Main Content */}
            <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
                {/* Greeting & Filters */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div>
                        <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                            Bonjour, {user?.first_name} 👋
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Voici la synthèse de votre activité documentaire et des actions en attente.
                        </p>
                    </div>
                    <div className="flex items-center space-x-1 bg-slate-100 dark:bg-slate-700 p-1 rounded-xl self-start md:self-auto text-xs font-medium">
                        {[
                            { key: '7d', label: '7 jours' },
                            { key: '30d', label: '30 jours' },
                            { key: '90d', label: '90 jours' },
                            { key: '12m', label: '12 mois' }
                        ].map((p) => (
                            <a
                                key={p.key}
                                href={`?period=${p.key}`}
                                className={`px-3 py-1.5 rounded-lg transition-colors ${
                                    period === p.key
                                        ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs'
                                        : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'
                                }`}
                            >
                                {p.label}
                            </a>
                        ))}
                    </div>
                </div>

                {/* Primary KPI Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    {/* Active Documents */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-indigo-300 transition-colors">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium text-slate-500 dark:text-slate-400">Documents actifs</span>
                            <div className="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                                📄
                            </div>
                        </div>
                        <div className="mt-4 flex items-baseline justify-between">
                            <span className="text-3xl font-bold text-slate-900 dark:text-white">
                                {formatNumber(statistics.documents_count)}
                            </span>
                            <span className="text-xs font-medium text-slate-500 dark:text-slate-400">
                                {statistics.storage_used_formatted || '0 o'}
                            </span>
                        </div>
                        <div className="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <span>Archivés : {statistics.documents_archived_count || 0}</span>
                            <span>Corbeille : {statistics.documents_trashed_count || 0}</span>
                        </div>
                    </div>

                    {/* Folders */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-emerald-300 transition-colors">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium text-slate-500 dark:text-slate-400">Dossiers accessibles</span>
                            <div className="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                                📁
                            </div>
                        </div>
                        <div className="mt-4">
                            <span className="text-3xl font-bold text-slate-900 dark:text-white">
                                {formatNumber(statistics.folders_count)}
                            </span>
                        </div>
                        <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            Arborescence selon vos droits d'accès
                        </p>
                    </div>

                    {/* Favorites */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-amber-300 transition-colors">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium text-slate-500 dark:text-slate-400">Favoris</span>
                            <div className="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg">
                                ⭐
                            </div>
                        </div>
                        <div className="mt-4">
                            <span className="text-3xl font-bold text-slate-900 dark:text-white">
                                {formatNumber(statistics.favorites_count)}
                            </span>
                        </div>
                        <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            Documents marqués prioritaires
                        </p>
                    </div>

                    {/* Pending Workflows */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-purple-300 transition-colors">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium text-slate-500 dark:text-slate-400">Action requise</span>
                            <div className={`w-10 h-10 rounded-xl flex items-center justify-center text-lg ${
                                (statistics.workflows_pending_count || 0) > 0
                                    ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400'
                                    : 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400'
                            }`}>
                                ⚡
                            </div>
                        </div>
                        <div className="mt-4 flex items-baseline justify-between">
                            <span className={`text-3xl font-bold ${
                                (statistics.workflows_pending_count || 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white'
                            }`}>
                                {formatNumber(statistics.workflows_pending_count)}
                            </span>
                            <span className="text-xs font-medium text-slate-500 dark:text-slate-400">
                                {workflows.in_progress_count || 0} en cours
                            </span>
                        </div>
                        <p className="mt-3 text-xs text-slate-500 dark:text-slate-400">
                            Workflows en attente de votre décision
                        </p>
                    </div>
                </div>

                {/* Analytical Visualizations */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Timeline */}
                    <div className="lg:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <h2 className="text-base font-semibold text-slate-900 dark:text-white">Évolution des créations de documents</h2>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mb-4">Période : {period}</p>

                        <div className="h-48 flex items-end gap-2 pt-6 pb-2 px-2">
                            {(charts.timeline || []).map((point, idx) => {
                                const maxVal = Math.max(1, ...(charts.timeline || []).map((p) => p.count));
                                const heightPercent = Math.max(8, Math.round((point.count / maxVal) * 100));
                                return (
                                    <div key={idx} className="flex-1 flex flex-col items-center gap-1 group relative">
                                        <div
                                            className="w-full bg-indigo-100 dark:bg-indigo-900/40 group-hover:bg-indigo-500 transition-colors rounded-t-md"
                                            style={{ height: `${heightPercent}%` }}
                                        />
                                        <span className="text-[10px] text-slate-400 truncate w-full text-center">{point.label}</span>
                                        <div className="absolute -top-8 hidden group-hover:block bg-slate-900 text-white text-[10px] py-1 px-2 rounded-md shadow-sm pointer-events-none z-10 whitespace-nowrap">
                                            {point.count} document(s)
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Document Types */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <h2 className="text-base font-semibold text-slate-900 dark:text-white mb-1">Répartition par type</h2>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mb-4">Formats de fichiers indexés</p>
                        <div className="space-y-3">
                            {[
                                { key: 'pdf', label: 'PDF', color: 'bg-rose-500' },
                                { key: 'images', label: 'Images', color: 'bg-blue-500' },
                                { key: 'docx', label: 'Word (DOCX)', color: 'bg-indigo-500' },
                                { key: 'xlsx', label: 'Excel (XLSX)', color: 'bg-emerald-500' },
                                { key: 'autres', label: 'Autres formats', color: 'bg-slate-400' }
                            ].map((typeMeta) => {
                                const val = charts.by_type?.[typeMeta.key] || 0;
                                const total = Math.max(1, Object.values(charts.by_type || {}).reduce((a, b) => a + b, 0));
                                const pct = Math.round((val / total) * 100);
                                return (
                                    <div key={typeMeta.key}>
                                        <div className="flex justify-between text-xs mb-1">
                                            <span className="font-medium text-slate-700 dark:text-slate-300">{typeMeta.label}</span>
                                            <span className="text-slate-500">{val} ({pct}%)</span>
                                        </div>
                                        <div className="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                            <div className={`${typeMeta.color} h-full rounded-full`} style={{ width: `${pct}%` }} />
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>

                {/* Operational Grids */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Workflows Pending */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                <span>⚡ Workflows en attente de validation</span>
                                {(workflows.pending_my_action?.length || 0) > 0 && (
                                    <span className="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                        {workflows.pending_my_action.length}
                                    </span>
                                )}
                            </h2>
                        </div>
                        {workflows.pending_my_action?.length > 0 ? (
                            <div className="divide-y divide-slate-100 dark:divide-slate-700/60">
                                {workflows.pending_my_action.map((wf) => (
                                    <div key={wf.id} className="py-3.5 flex items-center justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white truncate">{wf.document_name}</p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                Étape : <span className="font-medium text-indigo-600 dark:text-indigo-400">{wf.current_step_name}</span>
                                                {' • '} Initié par {wf.started_by || 'Système'} ({wf.started_at_human})
                                            </p>
                                        </div>
                                        <a
                                            href={`/workflow-instances/${wf.id}`}
                                            className="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/40 dark:text-indigo-300 transition-colors shrink-0"
                                        >
                                            Examiner →
                                        </a>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <div className="text-3xl mb-2">🎉</div>
                                <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">Aucune action en attente</p>
                                <p className="text-xs text-slate-400 mt-1">Vous n'avez aucun document nécessitant votre validation pour le moment.</p>
                            </div>
                        )}
                    </div>

                    {/* Recent Documents */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <h2 className="text-base font-semibold text-slate-900 dark:text-white mb-4">
                            🕒 Documents récemment consultés
                        </h2>
                        {recent_documents.length > 0 ? (
                            <div className="divide-y divide-slate-100 dark:divide-slate-700/60">
                                {recent_documents.map((doc) => (
                                    <div key={doc.id} className="py-3 flex items-center justify-between gap-4">
                                        <div className="flex items-center gap-3 min-w-0 flex-1">
                                            <div className="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-xs font-bold uppercase text-slate-600 dark:text-slate-300 shrink-0">
                                                {doc.extension || 'FIC'}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-semibold text-slate-900 dark:text-white truncate">{doc.name}</p>
                                                <p className="text-xs text-slate-400 truncate">
                                                    {doc.folder_name ? `${doc.folder_name} • ` : ''}{doc.size_formatted} • {doc.updated_at_human}
                                                </p>
                                            </div>
                                        </div>
                                        <a
                                            href={`/documents/${doc.id}/preview`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                            title="Prévisualiser"
                                        >
                                            👁️
                                        </a>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <div className="text-3xl mb-2">📄</div>
                                <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">Aucun document récent</p>
                                <p className="text-xs text-slate-400 mt-1">Vous n'avez encore consulté aucun document.</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Favorites and Activity Feed */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Favorites */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <h2 className="text-base font-semibold text-slate-900 dark:text-white mb-4">
                            ⭐ Vos favoris
                        </h2>
                        {favorites.length > 0 ? (
                            <div className="divide-y divide-slate-100 dark:divide-slate-700/60">
                                {favorites.map((fav) => (
                                    <div key={fav.id} className="py-3 flex items-center justify-between gap-4">
                                        <div className="flex items-center gap-3 min-w-0 flex-1">
                                            <div className="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xs font-bold uppercase shrink-0">
                                                {fav.extension || 'FAV'}
                                            </div>
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-semibold text-slate-900 dark:text-white truncate">{fav.name}</p>
                                                <p className="text-xs text-slate-400 truncate">
                                                    {fav.size_formatted} • Modifié {fav.updated_at_human}
                                                </p>
                                            </div>
                                        </div>
                                        <a
                                            href={`/documents/${fav.id}/preview`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                            title="Prévisualiser"
                                        >
                                            👁️
                                        </a>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <div className="text-3xl mb-2">⭐</div>
                                <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">Aucun favori</p>
                                <p className="text-xs text-slate-400 mt-1">Ajoutez vos documents importants à vos favoris pour les retrouver rapidement.</p>
                            </div>
                        )}
                    </div>

                    {/* Activity Feed */}
                    <div className="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                        <h2 className="text-base font-semibold text-slate-900 dark:text-white mb-4">
                            📋 Activité récente
                        </h2>
                        {recent_activity.length > 0 ? (
                            <div className="divide-y divide-slate-100 dark:divide-slate-700/60">
                                {recent_activity.map((act) => (
                                    <div key={act.id} className="py-2.5 flex items-start justify-between gap-3 text-xs">
                                        <div className="min-w-0 flex-1">
                                            <span className="font-semibold text-slate-800 dark:text-slate-200">{act.user_name}</span>
                                            <span className="text-slate-500 dark:text-slate-400"> — {act.action_label}</span>
                                            {act.description && act.description !== act.action && (
                                                <p className="text-[11px] text-slate-400 truncate mt-0.5">{act.description}</p>
                                            )}
                                        </div>
                                        <span className="text-slate-400 shrink-0">{act.created_at_human}</span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <div className="text-3xl mb-2">🕒</div>
                                <p className="text-sm font-semibold text-slate-700 dark:text-slate-300">Aucune activité enregistrée</p>
                                <p className="text-xs text-slate-400 mt-1">L'historique des actions s'affichera ici au fur et à mesure.</p>
                            </div>
                        )}
                    </div>
                </div>
            </main>
        </div>
    );
}
