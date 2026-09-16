import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Tabs from '../../Components/Tabs';
import Table from '../../Components/Table';
import Button from '../../Components/Button';
import Badge from '../../Components/Badge';
import Modal from '../../Components/Modal';
import Textarea from '../../Components/Textarea';
import EmptyState from '../../Components/EmptyState';
import { GitBranch, CheckCircle2, XCircle, AlertCircle, Eye, ArrowRight } from 'lucide-react';

export default function WorkflowsIndex({ workflows, instances = [] }) {
    const [activeTab, setActiveTab] = useState('instances');
    const [actionModalOpen, setActionModalOpen] = useState(false);
    const [selectedInstance, setSelectedInstance] = useState(null);
    const [actionType, setActionType] = useState('approve'); // approve | reject | correction

    const form = useForm({
        comment: '',
    });

    const handleActionSubmit = (e) => {
        e.preventDefault();
        if (!selectedInstance) return;

        form.post(`/workflow-instances/${selectedInstance.id}/${actionType}`, {
            onSuccess: () => {
                setActionModalOpen(false);
                setSelectedInstance(null);
                form.reset();
            },
        });
    };

    const openAction = (instance, type) => {
        setSelectedInstance(instance);
        setActionType(type);
        setActionModalOpen(true);
    };

    const statusBadge = (st) => {
        switch (st) {
            case 'approved': return <Badge variant="success">Approuvé</Badge>;
            case 'rejected': return <Badge variant="danger">Rejeté</Badge>;
            case 'correction_requested': return <Badge variant="warning">Correction demandée</Badge>;
            case 'in_progress': return <Badge variant="primary">En cours</Badge>;
            case 'cancelled': return <Badge variant="default">Annulé</Badge>;
            default: return <Badge variant="default">{st}</Badge>;
        }
    };

    const tabs = [
        { id: 'instances', label: 'Circuits en cours & Historique', icon: <GitBranch className="w-4 h-4" />, badge: instances.length },
        { id: 'definitions', label: 'Modèles de validation', icon: <CheckCircle2 className="w-4 h-4" />, badge: workflows?.total || workflows?.data?.length || 0 },
    ];

    return (
        <AuthenticatedLayout>
            <Head title="Workflows de validation" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Workflows de validation</h1>
                    <p className="text-xs text-slate-500 mt-1">Supervisez les approbations documentaires et faites avancer les circuits.</p>
                </div>

                <div className="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                    <Tabs tabs={tabs} activeTab={activeTab} onChange={setActiveTab} className="px-6" />

                    <div className="p-6">
                        {activeTab === 'instances' ? (
                            instances.length > 0 ? (
                                <Table headers={['Document', 'Workflow', 'Étape active', 'Initié par', 'Statut', 'Actions']}>
                                    {instances.map((inst) => (
                                        <tr key={inst.id} className="hover:bg-slate-50/70 transition">
                                            <td className="px-6 py-4">
                                                <Link
                                                    href={`/documents/${inst.document?.id}`}
                                                    className="font-semibold text-slate-900 hover:text-indigo-600 block text-sm truncate"
                                                >
                                                    {inst.document?.name}
                                                </Link>
                                            </td>
                                            <td className="px-6 py-4 text-xs font-medium text-slate-700">
                                                {inst.workflow?.name}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-600">
                                                {inst.current_step?.name || '—'}
                                            </td>
                                            <td className="px-6 py-4 text-xs text-slate-600">
                                                {inst.started_by?.name || 'Système'}
                                            </td>
                                            <td className="px-6 py-4">
                                                {statusBadge(inst.status)}
                                            </td>
                                            <td className="px-6 py-4 text-right text-xs">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    {inst.status === 'in_progress' && (
                                                        <>
                                                            <button
                                                                type="button"
                                                                onClick={() => openAction(inst, 'approve')}
                                                                className="p-1 text-emerald-600 hover:bg-emerald-50 rounded"
                                                                title="Approuver"
                                                            >
                                                                <CheckCircle2 className="w-4 h-4" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => openAction(inst, 'reject')}
                                                                className="p-1 text-rose-600 hover:bg-rose-50 rounded"
                                                                title="Rejeter"
                                                            >
                                                                <XCircle className="w-4 h-4" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => openAction(inst, 'correction')}
                                                                className="p-1 text-amber-600 hover:bg-amber-50 rounded"
                                                                title="Demander correction"
                                                            >
                                                                <AlertCircle className="w-4 h-4" />
                                                            </button>
                                                        </>
                                                    )}
                                                    <Link
                                                        href={`/documents/${inst.document?.id}`}
                                                        className="p-1 text-slate-400 hover:text-indigo-600"
                                                        title="Voir le document"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </Table>
                            ) : (
                                <EmptyState
                                    title="Aucun circuit de validation actif"
                                    description="Les workflows démarrés sur vos documents apparaîtront ici."
                                />
                            )
                        ) : (
                            <div className="space-y-4">
                                {(workflows?.data || workflows || []).length > 0 ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {(workflows?.data || workflows || []).map((wf) => (
                                            <div key={wf.id} className="p-5 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-3">
                                                <div className="flex items-center justify-between">
                                                    <h3 className="font-bold text-base text-slate-900">{wf.name}</h3>
                                                    <Badge variant={wf.is_active ? 'success' : 'default'}>
                                                        {wf.is_active ? 'Actif' : 'Inactif'}
                                                    </Badge>
                                                </div>
                                                {wf.description && <p className="text-xs text-slate-500">{wf.description}</p>}
                                                <div className="pt-2 border-t border-slate-100 text-xs text-slate-600">
                                                    <strong className="text-slate-800">Étapes définies :</strong>
                                                    <ol className="list-decimal list-inside mt-1 space-y-0.5 text-slate-500">
                                                        {wf.steps?.map((s, idx) => (
                                                            <li key={s.id || idx}>
                                                                {s.name} ({s.approver_type === 'user' ? s.approver_user?.name : s.approver_group?.name})
                                                            </li>
                                                        ))}
                                                    </ol>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <EmptyState
                                        title="Aucun modèle de workflow défini"
                                        description="Contactez un administrateur pour configurer des circuits de validation."
                                    />
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Action Decision Modal */}
            <Modal
                isOpen={actionModalOpen}
                onClose={() => setActionModalOpen(false)}
                title={`Action de validation : ${actionType === 'approve' ? 'Approbation' : actionType === 'reject' ? 'Rejet' : 'Correction'}`}
                maxWidth="max-w-md"
            >
                <form onSubmit={handleActionSubmit} className="space-y-4">
                    <Textarea
                        id="wf-action-comment"
                        label="Commentaire de décision"
                        required={actionType !== 'approve'}
                        placeholder="Veuillez indiquer vos remarques ou motifs..."
                        rows={3}
                        value={form.data.comment}
                        onChange={(e) => form.setData('comment', e.target.value)}
                        error={form.errors.comment}
                    />

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setActionModalOpen(false)}>Annuler</Button>
                        <Button
                            type="submit"
                            variant={actionType === 'approve' ? 'success' : actionType === 'reject' ? 'danger' : 'warning'}
                            loading={form.processing}
                        >
                            Confirmer la décision
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
