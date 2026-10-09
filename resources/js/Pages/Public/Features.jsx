import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Folder,
    Search,
    ShieldCheck,
    Cpu,
    GitCommit,
    Share2,
    CheckSquare,
    History,
    Bell,
    Trash2,
    Archive,
    Sliders,
    Users,
    ArrowRight,
    Check,
    Lock,
    Sparkles,
    Building2,
    Layers,
    FileText
} from 'lucide-react';

export default function Features() {
    const features = [
        {
            icon: Folder,
            color: 'bg-blue-600',
            title: 'Organisation Multi-Tenant & Structure Métier',
            description: 'Structurez vos archives selon la hiérarchie réelle de votre entreprise : Organisation → Direction → Service → Type documentaire.',
            badge: 'Structure'
        },
        {
            icon: Search,
            color: 'bg-indigo-600',
            title: 'Moteur de Recherche Avancé & Filtres',
            description: 'Retrouvez n\'importe quel document instantanément grâce à la recherche plein texte, le filtrage par métadonnées, dates et services.',
            badge: 'Recherche'
        },
        {
            icon: Cpu,
            color: 'bg-violet-600',
            title: 'OCR Tesseract Automatique',
            description: 'Numérisez vos factures, contrats et courriers scannés. Le texte est extrait automatiquement et indexé sans intervention humaine.',
            badge: 'Intelligence'
        },
        {
            icon: GitCommit,
            color: 'bg-emerald-600',
            title: 'Versioning Documentaire & Historique',
            description: 'Gardez un historique inviolable de chaque modification. Comparez, téléchargez ou restaurez une révision antérieure en un clic.',
            badge: 'Versioning'
        },
        {
            icon: Share2,
            color: 'bg-amber-600',
            title: 'Partage Sécurisé & Périmètres d\'Accès',
            description: 'Partagez des documents en interne avec des permissions précises (lecture, écriture, téléchargement) et des liens temporaires protégés.',
            badge: 'Collaboration'
        },
        {
            icon: CheckSquare,
            color: 'bg-sky-600',
            title: 'Circuits de Validation & Workflows',
            description: 'Automatisez la circulation et la signature de vos documents administratifs avec des circuits d\'approbation étape par étape.',
            badge: 'Automatisation'
        },
        {
            icon: History,
            color: 'bg-rose-600',
            title: 'Journal d\'Audit & Traçabilité Complète',
            description: 'Chaque consultation, modification, téléchargement ou suppression est horodatée avec l\'adresse IP et l\'auteur de l\'action.',
            badge: 'Conformité'
        },
        {
            icon: Bell,
            color: 'bg-amber-500',
            title: 'Notifications & Alertes en Temps Réel',
            description: 'Soyez notifié dès qu\'un document requiert votre signature, qu\'une échéance approche ou qu\'un dossier partagé est mis à jour.',
            badge: 'Productivité'
        },
        {
            icon: Trash2,
            color: 'bg-slate-700',
            title: 'Corbeille Sécurisée & Restauration',
            description: 'Évitez les pertes de données accidentelles avec une corbeille protégée permettant la restauration immédiate par les administrateurs.',
            badge: 'Sécurité'
        },
        {
            icon: Archive,
            color: 'bg-teal-600',
            title: 'Archivage Légal & Rétention',
            description: 'Conservez vos documents critiques dans un état figé et inaltérable selon vos règles de conservation juridique.',
            badge: 'Pérénité'
        },
        {
            icon: Sliders,
            color: 'bg-purple-600',
            title: 'Schémas de Métadonnées Personnalisés',
            description: 'Enrichissez vos documents avec des champs sur-mesure typés (numéro de facture, montant, date d\'échéance, client).',
            badge: 'Indexation'
        },
        {
            icon: Lock,
            color: 'bg-blue-700',
            title: 'Contrôle d\'Accès par Rôles (RBAC) & Teams',
            description: 'Garantissez une étanchéité parfaite de vos informations avec le système Spatie Teams et les périmètres d\'accès par direction.',
            badge: 'Gouvernance'
        }
    ];

    return (
        <div className="min-h-screen bg-[#0B132B] text-slate-100 font-sans selection:bg-blue-600 selection:text-white">
            <Head title="Fonctionnalités — GEDAPP Plateforme B2B" />

            {/* Navigation Header */}
            <header className="border-b border-slate-800/80 bg-[#0B132B]/90 backdrop-blur-md sticky top-0 z-40">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-3 group">
                        <div className="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white shrink-0 group-hover:scale-105 transition-transform">
                            GED
                        </div>
                        <span className="font-black text-xl tracking-tight text-white">
                            GED<span className="text-blue-500">APP</span>
                        </span>
                    </Link>

                    <nav className="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-300">
                        <Link href="/" className="hover:text-white transition">Accueil</Link>
                        <Link href="/fonctionnalites" className="text-blue-400 font-bold">Fonctionnalités</Link>
                        <Link href="/tarifs" className="hover:text-white transition">Tarifs</Link>
                        <Link href="/enterprise" className="hover:text-white transition">Enterprise</Link>
                        <Link href="/contact" className="hover:text-white transition">Contact</Link>
                    </nav>

                    <div className="flex items-center gap-3">
                        <Link href="/login" className="text-xs sm:text-sm font-semibold text-slate-300 hover:text-white px-3 py-2 transition">
                            Connexion
                        </Link>
                        <Link
                            href="/inscription"
                            className="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-bold bg-blue-600 hover:bg-blue-500 text-white transition"
                        >
                            <span>Essai gratuit 14j</span>
                            <ArrowRight className="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </div>
            </header>

            {/* Hero Section */}
            <section className="py-20 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto text-center space-y-6">
                <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                    <Sparkles className="w-3.5 h-3.5" />
                    <span>Plateforme GED Cloud Tout-en-Un</span>
                </div>

                <h1 className="text-4xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight max-w-4xl mx-auto leading-tight">
                    Toutes les fonctionnalités pour maîtriser vos <span className="text-blue-400">documents d'entreprise</span>
                </h1>

                <p className="text-slate-400 text-base sm:text-lg max-w-2xl mx-auto">
                    Conçu pour les PME, grandes entreprises et administrations : de la capture OCR à l'archivage légal en passant par les workflows d'approbation.
                </p>

                <div className="pt-4 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <Link
                        href="/inscription"
                        className="w-full sm:w-auto px-8 py-3.5 rounded-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition flex items-center justify-center gap-2"
                    >
                        <span>Démarrer l'essai gratuit</span>
                        <ArrowRight className="w-4 h-4" />
                    </Link>
                    <Link
                        href="/tarifs"
                        className="w-full sm:w-auto px-8 py-3.5 rounded-full bg-slate-900 hover:bg-slate-800 text-slate-300 font-bold text-sm border border-slate-800 transition"
                    >
                        Voir la grille tarifaire
                    </Link>
                </div>
            </section>

            {/* Features Grid */}
            <section className="py-16 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    {features.map((feat, idx) => {
                        const Icon = feat.icon;
                        return (
                            <div
                                key={idx}
                                className="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-8 hover:border-slate-700 transition duration-300 group flex flex-col justify-between"
                            >
                                <div>
                                    <div className="flex items-center justify-between mb-6">
                                        <div className={`w-12 h-12 rounded-2xl ${feat.color} flex items-center justify-center text-white`}>
                                            <Icon className="w-6 h-6" />
                                        </div>
                                        <span className="text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-slate-800 text-slate-400 border border-slate-700">
                                            {feat.badge}
                                        </span>
                                    </div>
                                    <h3 className="text-xl font-bold text-white mb-3 group-hover:text-blue-400 transition">
                                        {feat.title}
                                    </h3>
                                    <p className="text-slate-400 text-sm leading-relaxed">
                                        {feat.description}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </section>

            {/* Security Callout */}
            <section className="py-16 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
                <div className="bg-slate-900 border border-blue-500/20 rounded-3xl p-8 sm:p-12 text-center space-y-6">
                    <div className="w-14 h-14 rounded-2xl bg-blue-600/20 text-blue-400 flex items-center justify-center mx-auto border border-blue-500/30">
                        <ShieldCheck className="w-8 h-8" />
                    </div>
                    <h2 className="text-2xl sm:text-3xl font-black text-white">
                        Sécurité & Isolation Multi-Tenant Inviolables
                    </h2>
                    <p className="text-slate-300 text-sm sm:text-base max-w-2xl mx-auto">
                        Chaque organisation dispose d'un périmètre hermétique grâce à Spatie Teams. Vos fichiers sont chiffrés et hébergés dans un stockage privé Cloudflare R2 / S3 avec des contrôles d'accès stricts.
                    </p>
                    <div className="pt-2">
                        <Link
                            href="/inscription"
                            className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-sm transition"
                        >
                            <span>Créer votre espace entreprise</span>
                            <ArrowRight className="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-slate-800/80 bg-[#0B132B] py-12 px-4 sm:px-6 lg:px-8 text-xs text-slate-500 text-center">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
                    <span>© {new Date().getFullYear()} GEDAPP SaaS. Tous droits réservés.</span>
                    <div className="flex gap-6">
                        <Link href="/fonctionnalites" className="hover:text-slate-300 transition">Fonctionnalités</Link>
                        <Link href="/tarifs" className="hover:text-slate-300 transition">Tarifs</Link>
                        <Link href="/enterprise" className="hover:text-slate-300 transition">Enterprise</Link>
                        <Link href="/contact" className="hover:text-slate-300 transition">Contact</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
