<!DOCTYPE html>
<html lang="fr" class="h-full bg-slate-50 dark:bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tableau de bord — GED SaaS</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', sans-serif; }
    </style>
</head>
<body class="h-full text-slate-800 dark:text-slate-100 antialiased bg-slate-50 dark:bg-slate-900">
    <div class="min-h-full flex flex-col">
        <!-- Navigation Header -->
        <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 sticky top-0 z-30 shadow-2xs">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <div class="flex items-center space-x-3">
                        <!-- Mobile burger button -->
                        <button type="button" id="mobile-sidebar-toggle" class="p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg lg:hidden" aria-label="Ouvrir le menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <a href="/dashboard" class="flex items-center space-x-2.5">
                            <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold shadow-md shadow-indigo-100">
                                GED
                            </div>
                            <span class="text-base font-bold text-slate-900 dark:text-white">GED<span class="text-indigo-600">APP</span></span>
                        </a>
                        <span class="hidden md:inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600">
                            {{ $organization['name'] ?? 'Organisation' }}
                        </span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="/notifications" class="relative p-2 text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 rounded-full transition" title="Centre de notifications">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            @if(($statistics['notifications_unread_count'] ?? 0) > 0)
                                <span class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-[10px] font-bold text-white ring-2 ring-white animate-pulse">
                                    {{ ($statistics['notifications_unread_count'] ?? 0) > 9 ? '9+' : ($statistics['notifications_unread_count'] ?? 0) }}
                                </span>
                            @endif
                        </a>
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-semibold text-slate-900 dark:text-white">{{ $user['full_name'] }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 capitalize">{{ $user['role'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body with Permanent Sidebar on Desktop -->
        <div class="flex flex-1 min-h-0">
            <!-- Mobile Drawer (hidden by default) -->
            <div id="mobile-sidebar-drawer" class="hidden fixed inset-0 z-50 lg:hidden flex">
                <div id="mobile-sidebar-backdrop" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"></div>
                <aside class="relative z-50 w-64 max-w-[80vw] bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 shadow-2xl flex flex-col h-full">
                    <div class="flex h-16 items-center justify-between px-6 border-b border-slate-100 dark:border-slate-700 shrink-0">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                                GED
                            </div>
                            <span class="font-bold text-slate-900 dark:text-white">GEDAPP</span>
                        </div>
                        <button type="button" id="mobile-sidebar-close" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
                        @include('partials.dashboard-nav')
                    </div>
                </aside>
            </div>

            <!-- Permanent Desktop Sidebar -->
            <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:shrink-0 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 min-h-[calc(100vh-4rem)]">
                <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
                    @include('partials.dashboard-nav')
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-x-hidden p-4 sm:p-6 lg:p-8 space-y-8">
            <!-- Welcome Banner & Period Filter -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white">
                        Bonjour, {{ $user['first_name'] }} 👋
                    </h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                        Voici la synthèse de votre activité documentaire et des actions en attente.
                    </p>
                </div>
                <div class="flex items-center space-x-1 bg-slate-100 dark:bg-slate-700 p-1 rounded-xl self-start md:self-auto text-xs font-medium">
                    @foreach(['7d' => '7 jours', '30d' => '30 jours', '90d' => '90 jours', '12m' => '12 mois'] as $pKey => $pLabel)
                        <a href="?period={{ $pKey }}"
                           class="px-3 py-1.5 rounded-lg transition-colors {{ ($period ?? '30d') === $pKey ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900' }}">
                            {{ $pLabel }}
                        </a>
                    @endforeach
                </div>
            </div>

            <!-- Primary KPI Cards (Grid 4) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Card 1: Documents -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-indigo-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Documents actifs</span>
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-lg">
                            📄
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($statistics['documents_count']) }}</span>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $statistics['storage_used_formatted'] }}</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                        <span>Archivés : {{ $statistics['documents_archived_count'] }}</span>
                        <span>Corbeille : {{ $statistics['documents_trashed_count'] }}</span>
                    </div>
                </div>

                <!-- Card 2: Folders -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-emerald-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Dossiers accessibles</span>
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-lg">
                            📁
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($statistics['folders_count']) }}</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        Arborescence selon vos droits d'accès
                    </p>
                </div>

                <!-- Card 3: Favorites -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-amber-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Favoris</span>
                        <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-lg">
                            ⭐
                        </div>
                    </div>
                    <div class="mt-4">
                        <span class="text-3xl font-bold text-slate-900 dark:text-white">{{ number_format($statistics['favorites_count']) }}</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        Documents marqués prioritaires
                    </p>
                </div>

                <!-- Card 4: Workflows Pending -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs hover:border-purple-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Action requise</span>
                        <div class="w-10 h-10 rounded-xl {{ ($statistics['workflows_pending_count'] ?? 0) > 0 ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400' : 'bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400' }} flex items-center justify-center text-lg">
                            ⚡
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline justify-between">
                        <span class="text-3xl font-bold {{ ($statistics['workflows_pending_count'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }}">
                            {{ number_format($statistics['workflows_pending_count']) }}
                        </span>
                        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">
                            {{ $workflows['in_progress_count'] ?? 0 }} en cours
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
                        Workflows en attente de votre décision
                    </p>
                </div>
            </div>

            <!-- Charts Section (Grid 2 cols) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Chart 1: Timeline Activity (2 cols) -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Évolution des créations de documents</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Fréquence d'ajout sur la période sélectionnée ({{ $period }})</p>
                        </div>
                    </div>
                    <!-- Responsive SVG Line/Bar Chart -->
                    @php
                        $timeline = $charts['timeline'] ?? [];
                        $maxCount = max(array_merge([1], array_column($timeline, 'count')));
                    @endphp
                    @if (count($timeline) > 0)
                        <div class="h-48 flex items-end gap-2 pt-6 pb-2 px-2">
                            @foreach ($timeline as $point)
                                @php
                                    $heightPercent = max(8, round(($point['count'] / $maxCount) * 100));
                                @endphp
                                <div class="flex-1 flex flex-col items-center gap-1 group relative">
                                    <div class="w-full bg-indigo-100 dark:bg-indigo-900/40 group-hover:bg-indigo-500 dark:group-hover:bg-indigo-500 transition-colors rounded-t-md"
                                         style="height: {{ $heightPercent }}%;">
                                    </div>
                                    <span class="text-[10px] text-slate-400 truncate w-full text-center">{{ $point['label'] }}</span>
                                    <!-- Tooltip -->
                                    <div class="absolute -top-8 hidden group-hover:block bg-slate-900 text-white text-[10px] py-1 px-2 rounded-md shadow-sm pointer-events-none z-10 whitespace-nowrap">
                                        {{ $point['count'] }} document(s)
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="h-48 flex items-center justify-center text-sm text-slate-400">
                            Aucune donnée d'activité sur cette période.
                        </div>
                    @endif
                </div>

                <!-- Chart 2: Document Types Distribution (1 col) -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white mb-1">Répartition par type</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Formats de fichiers indexés</p>

                    @php
                        $types = $charts['by_type'] ?? [];
                        $totalTypes = max(1, array_sum($types));
                    @endphp
                    <div class="space-y-3">
                        @foreach([
                            'pdf' => ['label' => 'PDF', 'color' => 'bg-rose-500'],
                            'images' => ['label' => 'Images', 'color' => 'bg-blue-500'],
                            'docx' => ['label' => 'Word (DOCX)', 'color' => 'bg-indigo-500'],
                            'xlsx' => ['label' => 'Excel (XLSX)', 'color' => 'bg-emerald-500'],
                            'autres' => ['label' => 'Autres formats', 'color' => 'bg-slate-400'],
                        ] as $tKey => $meta)
                            @php
                                $val = $types[$tKey] ?? 0;
                                $pct = round(($val / $totalTypes) * 100);
                            @endphp
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $meta['label'] }}</span>
                                    <span class="text-slate-500">{{ $val }} ({{ $pct }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                                    <div class="{{ $meta['color'] }} h-full rounded-full" style="width: {{ $pct }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Operational Sections Grid (2 cols: Workflows & Recent Documents) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Section: Pending Workflows -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>⚡ Workflows en attente de validation</span>
                            @if(count($workflows['pending_my_action']) > 0)
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                    {{ count($workflows['pending_my_action']) }}
                                </span>
                            @endif
                        </h2>
                    </div>

                    @if(count($workflows['pending_my_action']) > 0)
                        <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach($workflows['pending_my_action'] as $wf)
                                <div class="py-3.5 flex items-center justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                            {{ $wf['document_name'] }}
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                            Étape : <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ $wf['current_step_name'] }}</span>
                                            • Initié par {{ $wf['started_by'] ?? 'Système' }} ({{ $wf['started_at_human'] }})
                                        </p>
                                    </div>
                                    <a href="/workflow-instances/{{ $wf['id'] }}"
                                       class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/40 dark:text-indigo-300 transition-colors shrink-0">
                                        Examiner →
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-3xl mb-2">🎉</div>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Aucune action en attente</p>
                            <p class="text-xs text-slate-400 mt-1">Vous n'avez aucun document nécessitant votre validation pour le moment.</p>
                        </div>
                    @endif
                </div>

                <!-- Section: Recent Documents -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                            🕒 Documents récemment consultés
                        </h2>
                    </div>

                    @if(count($recent_documents) > 0)
                        <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach($recent_documents as $doc)
                                <div class="py-3 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-xs font-bold uppercase text-slate-600 dark:text-slate-300 shrink-0">
                                            {{ $doc['extension'] ?: 'FIC' }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                                {{ $doc['name'] }}
                                            </p>
                                            <p class="text-xs text-slate-400 truncate">
                                                {{ $doc['folder_name'] ? $doc['folder_name'].' • ' : '' }}{{ $doc['size_formatted'] }} • {{ $doc['updated_at_human'] }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 shrink-0">
                                        <a href="/documents/{{ $doc['id'] }}/preview" target="_blank"
                                           class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                           title="Prévisualiser">
                                            👁️
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-3xl mb-2">📄</div>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Aucun document récent</p>
                            <p class="text-xs text-slate-400 mt-1">Vous n'avez encore consulté aucun document.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Bottom Grid (Favorites & Recent Activity Feed) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Section: Favorites -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                            ⭐ Vos favoris
                        </h2>
                    </div>

                    @if(count($favorites) > 0)
                        <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach($favorites as $fav)
                                <div class="py-3 flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3 min-w-0 flex-1">
                                        <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center text-xs font-bold uppercase shrink-0">
                                            {{ $fav['extension'] ?: 'FAV' }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                                {{ $fav['name'] }}
                                            </p>
                                            <p class="text-xs text-slate-400 truncate">
                                                {{ $fav['size_formatted'] }} • Modifié {{ $fav['updated_at_human'] }}
                                            </p>
                                        </div>
                                    </div>
                                    <a href="/documents/{{ $fav['id'] }}/preview" target="_blank"
                                       class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                       title="Prévisualiser">
                                        👁️
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-3xl mb-2">⭐</div>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Aucun favori</p>
                            <p class="text-xs text-slate-400 mt-1">Ajoutez vos documents importants à vos favoris pour les retrouver rapidement.</p>
                        </div>
                    @endif
                </div>

                <!-- Section: Recent Activity -->
                <div class="bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                            📋 Activité récente
                        </h2>
                    </div>

                    @if(count($recent_activity) > 0)
                        <div class="divide-y divide-slate-100 dark:divide-slate-700/60">
                            @foreach($recent_activity as $act)
                                <div class="py-2.5 flex items-start justify-between gap-3 text-xs">
                                    <div class="min-w-0 flex-1">
                                        <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $act['user_name'] }}</span>
                                        <span class="text-slate-500 dark:text-slate-400">— {{ $act['action_label'] }}</span>
                                        @if(!empty($act['description']) && $act['description'] !== $act['action'])
                                            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $act['description'] }}</p>
                                        @endif
                                    </div>
                                    <span class="text-slate-400 shrink-0">{{ $act['created_at_human'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <div class="text-3xl mb-2">🕒</div>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Aucune activité enregistrée</p>
                            <p class="text-xs text-slate-400 mt-1">L'historique des actions s'affichera ici au fur et à mesure.</p>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <script>
        const toggleBtn = document.getElementById('mobile-sidebar-toggle');
        const drawer = document.getElementById('mobile-sidebar-drawer');
        const closeBtn = document.getElementById('mobile-sidebar-close');
        const backdrop = document.getElementById('mobile-sidebar-backdrop');

        if (toggleBtn && drawer) {
            toggleBtn.addEventListener('click', () => drawer.classList.remove('hidden'));
        }
        if (closeBtn && drawer) {
            closeBtn.addEventListener('click', () => drawer.classList.add('hidden'));
        }
        if (backdrop && drawer) {
            backdrop.addEventListener('click', () => drawer.classList.add('hidden'));
        }
    </script>
</body>
</html>
