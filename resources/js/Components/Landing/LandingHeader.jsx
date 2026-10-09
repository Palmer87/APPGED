import React, { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { ArrowRight, Menu, X } from 'lucide-react';
import BrandLogo from './BrandLogo';

const sectionLinks = [
    { href: '#fonctionnalites', label: 'Fonctionnalités' },
    { href: '#solutions', label: 'Solutions' },
    { href: '#securite', label: 'Sécurité' },
    { href: '#tarifs', label: 'Tarifs' },
];

/**
 * Sticky, glassy landing header that compacts once the page scrolls.
 */
export default function LandingHeader({ user }) {
    const [isScrolled, setIsScrolled] = useState(false);
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    useEffect(() => {
        const handleScroll = () => setIsScrolled(window.scrollY > 24);
        handleScroll();
        window.addEventListener('scroll', handleScroll, { passive: true });
        return () => window.removeEventListener('scroll', handleScroll);
    }, []);

    useEffect(() => {
        if (!isMobileMenuOpen) {
            return undefined;
        }
        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                setIsMobileMenuOpen(false);
            }
        };
        const handleResize = () => {
            if (window.innerWidth >= 1024) {
                setIsMobileMenuOpen(false);
            }
        };
        window.addEventListener('keydown', handleKeyDown);
        window.addEventListener('resize', handleResize);
        return () => {
            window.removeEventListener('keydown', handleKeyDown);
            window.removeEventListener('resize', handleResize);
        };
    }, [isMobileMenuOpen]);

    const closeMobileMenu = () => setIsMobileMenuOpen(false);
    const isSolid = isScrolled || isMobileMenuOpen;

    return (
        <header
            className={`fixed inset-x-0 top-0 z-50 transition-[background-color,border-color] duration-300 ${
                isSolid
                    ? 'border-b border-white/10 bg-night/85 backdrop-blur-xl'
                    : 'border-b border-transparent bg-transparent'
            }`}
        >
            <a
                href="#contenu"
                className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-slate-900"
            >
                Aller au contenu
            </a>
            <div
                className={`mx-auto flex max-w-7xl items-center justify-between px-4 transition-[height] duration-300 sm:px-6 lg:px-8 ${
                    isScrolled ? 'h-16' : 'h-20'
                }`}
            >
                <BrandLogo tone="dark" compact={isScrolled} />

                <nav aria-label="Navigation principale" className="hidden lg:block">
                    <ul className="flex items-center gap-1 text-sm font-medium text-slate-300">
                        {sectionLinks.map((link) => (
                            <li key={link.href}>
                                <a
                                    href={link.href}
                                    className="rounded-full px-4 py-2 transition-colors hover:bg-white/5 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                                >
                                    {link.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>

                <div className="hidden items-center gap-2 lg:flex">
                    {user ? (
                        <Link
                            href="/dashboard"
                            className="group inline-flex items-center gap-2 rounded-full bg-white px-5 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                        >
                            Tableau de bord
                            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/login"
                                className="rounded-full px-4 py-2 text-sm font-semibold text-slate-200 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                            >
                                Connexion
                            </Link>
                            <Link
                                href="/inscription"
                                className="group inline-flex items-center gap-2 rounded-full bg-blue-600 hover:bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400"
                            >
                                Commencer gratuitement
                                <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5" aria-hidden="true" />
                            </Link>
                        </>
                    )}
                </div>

                <button
                    type="button"
                    onClick={() => setIsMobileMenuOpen((previous) => !previous)}
                    className="inline-flex h-10 w-10 items-center justify-center rounded-xl text-slate-200 transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-400 lg:hidden"
                    aria-expanded={isMobileMenuOpen}
                    aria-controls="menu-mobile"
                    aria-label={isMobileMenuOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
                >
                    {isMobileMenuOpen ? <X className="h-6 w-6" aria-hidden="true" /> : <Menu className="h-6 w-6" aria-hidden="true" />}
                </button>
            </div>

            <div
                id="menu-mobile"
                hidden={!isMobileMenuOpen}
                className="border-t border-white/10 bg-night/95 px-4 pb-6 pt-3 backdrop-blur-xl lg:hidden"
            >
                <nav aria-label="Navigation mobile">
                    <ul className="space-y-1">
                        {sectionLinks.map((link) => (
                            <li key={link.href}>
                                <a
                                    href={link.href}
                                    onClick={closeMobileMenu}
                                    className="block rounded-xl px-3 py-3 text-base font-medium text-slate-200 hover:bg-white/5 hover:text-white"
                                >
                                    {link.label}
                                </a>
                            </li>
                        ))}
                    </ul>
                </nav>
                <div className="mt-4 flex flex-col gap-2 border-t border-white/10 pt-4">
                    {user ? (
                        <Link href="/dashboard" className="rounded-xl bg-white py-3 text-center font-semibold text-slate-900">
                            Accéder au tableau de bord
                        </Link>
                    ) : (
                        <>
                            <Link href="/login" className="rounded-xl py-3 text-center font-semibold text-slate-200 ring-1 ring-white/15 hover:bg-white/5">
                                Connexion
                            </Link>
                            <Link href="/inscription" className="rounded-xl bg-blue-600 hover:bg-blue-700 py-3 text-center font-semibold text-white">
                                Commencer gratuitement
                            </Link>
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}
