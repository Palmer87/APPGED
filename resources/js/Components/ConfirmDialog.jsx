import React from 'react';
import Modal from './Modal';
import Button from './Button';
import { AlertTriangle } from 'lucide-react';

export default function ConfirmDialog({
    isOpen = false,
    onClose,
    onConfirm,
    title = 'Confirmer l\'action',
    message = 'Êtes-vous sûr de vouloir effectuer cette action ? Cette action est irréversible.',
    confirmLabel = 'Confirmer',
    cancelLabel = 'Annuler',
    variant = 'danger',
    loading = false,
}) {
    return (
        <Modal isOpen={isOpen} onClose={onClose} maxWidth="max-w-md">
            <div className="flex items-start gap-4">
                <div className={`p-2.5 rounded-full shrink-0 ${variant === 'danger' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'}`}>
                    <AlertTriangle className="w-5 h-5" />
                </div>
                <div className="flex-1">
                    <h4 className="text-base font-semibold text-slate-900 mb-1">{title}</h4>
                    <p className="text-sm text-slate-500">{message}</p>
                </div>
            </div>
            <div className="mt-6 flex justify-end gap-3">
                <Button variant="secondary" size="md" onClick={onClose} disabled={loading}>
                    {cancelLabel}
                </Button>
                <Button variant={variant} size="md" onClick={onConfirm} loading={loading}>
                    {confirmLabel}
                </Button>
            </div>
        </Modal>
    );
}
