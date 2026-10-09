import React from 'react';
import { Link } from '@inertiajs/react';
import BrandLogo, { BRAND_NAME } from './BrandLogo';

const columns = [
    {
        title: 'Produit',
        links: [
            { href: '/fonctionnalites', label: 'Fonctionnalités' },
            { href: '/tarifs', label: 'Tarifs' },
            { href: '/enterprise', label: 'Offre Entreprise' },
        ],
    },
    {
        title: 'Découvrir',
        links: [
            { href: '#solutions', label: 'Solutions', isAnchor: true },
            { href: '#securite', label: 'Sécurité', isAnchor: true },
            { href: '#demonstration', label: 'Démonstration', isAnchor: true },
        ],
    },
    {
        title: 'Compte',
        links: [
            { href: '/login', label: 'Connexion' },
            { href: '/inscription', label: 'Créer un compte' },
            { href: '/contact', label: 'Contact' },
        ],
    },
];

/**
 * Landing footer with only existing destinations.
 */
export default function LandingFooter() {
    return (
        <footer className="border-t border-white/10 bg-night text-sm text-slate-400">
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
                <div className="grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-5">
                        <BrandLogo tone="dark" />
                        <p className="mt-5 max-w-sm leading-relaxed">
                            La plateforme de gestion électronique de documents qui aide les entreprises et les organisations à centraliser, structurer et sécuriser leurs documents.
                        </p>
                    </div>
                    <div className="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-7">
                        {columns.map((column) => (
                            <nav key={column.title} aria-label={column.title}>
                                <p className="mb-4 text-sm font-semibold text-white">{column.title}</p>
                                <ul className="space-y-3">
                                    {column.links.map((link) => (
                                        <li key={link.href}>
                                            {link.isAnchor ? (
                                                <a href={link.href} className="transition hover:text-white">{link.label}</a>
                                            ) : (
                                                <Link href={link.href} className="transition hover:text-white">{link.label}</Link>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </nav>
                        ))}
                    </div>
                </div>
                <div className="mt-14 flex flex-col gap-2 border-t border-white/10 pt-8 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {new Date().getFullYear()} {BRAND_NAME}. Tous droits réservés.</p>
                    <p>Les aperçus de l’interface présentés sur cette page utilisent des données fictives.</p>
                </div>
            </div>
        </footer>
    );
}
