import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Tabs from '../../Components/Tabs';
import Table from '../../Components/Table';
import Button from '../../Components/Button';
import Badge from '../../Components/Badge';
import Modal from '../../Components/Modal';
import Input from '../../Components/Input';
import Select from '../../Components/Select';
import Textarea from '../../Components/Textarea';
import EmptyState from '../../Components/EmptyState';
import {
    GitBranch,
    CheckCircle2,
    XCircle,
    AlertCircle,
    Eye,
    ArrowRight,
    Plus,
    Trash2,
    Layers,
    User,
    Users
} from 'lucide-react';

export default function WorkflowsIndex({
    workflows,
    instances = [],
    availableUsers = [],
    availableGroups = [],
    canCreate = true,
}) {
    const [activeTab, setActiveTab] = useState('instances');
    const [actionModalOpen, setActionModalOpen] = useState(false);
    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [selectedInstance, setSelectedInstance] = useState(null);
    const [actionType, setActionType] = useState('approve'); // approve | reject | correction

    const createForm = useForm({
        name: '',
        description: '',
        is_active: true,
        steps: [
            {
                name: 'Étape 1 : Validation',
                approver_type: 'user',
                approver_user_id: availableUsers[0]?.id ? String(availableUsers[0].id) : '',
                approver_group_id: '',
                position: 1,
            },
        ],
    });

    const addStep = () => {
        const nextPos = createForm.data.steps.length + 1;
        createForm.setData('steps', [
            ...createForm.data.steps,
            {
                name: `Étape ${nextPos} : Approbation`,
                approver_type: 'user',
                approver_user_id: availableUsers[0]?.id ? String(availableUsers[0].id) : '',
                approver_group_id: '',
                position: nextPos,
            },
        ]);
    };

    const removeStep = (index) => {
        if (createForm.data.steps.length <= 1) return;
        const filtered = createForm.data.steps.filter((_, i) => i !== index);
        createForm.setData('steps', filtered.map((s, idx) => ({ ...s, position: idx + 1 })));
    };

    const updateStep = (index, field, value) => {
        const updated = [...createForm.data.steps];
        updated[index] = { ...updated[index], [field]: value };
        if (field === 'approver_type') {
            if (value === 'user') {
                updated[index].approver_user_id = availableUsers[0]?.id ? String(availableUsers[0].id) : '';
                updated[index].approver_group_id = '';
            } else {
                updated[index].approver_group_id = availableGroups[0]?.id ? String(availableGroups[0].id) : '';
                updated[index].approver_user_id = '';
            }
        }
        createForm.setData('steps', updated);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        createForm.post('/workflows', {
            preserveScroll: true,
            onSuccess: () => {
                setCreateModalOpen(false);
                createForm.reset();
            },
        });
    };

    const handleDeleteWorkflow = (wfId) => {
        if (confirm('Voulez-vous vraiment supprimer ce modèle de workflow ?')) {
            router.delete(`/workflows/${wfId}`, {
                preserveScroll: true,
            });
        }
    };

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
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Workflows de validation</h1>
                        <p className="text-xs text-slate-500 mt-1">Supervisez les approbations documentaires et configurez vos circuits de validation.</p>
                    </div>
                    {canCreate && (
                        <Button variant="primary" onClick={() => setCreateModalOpen(true)}>
                            <Plus className="w-4 h-4" />
                            Nouveau workflow
                        </Button>
                    )}
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
                                                    {inst.status === 'in_progress' && inst.can_approve && (
                                                        <>
                                                            <button
                                                                type="button"
                                                                onClick={() => openAction(inst, 'approve')}
                                                                className="p-1 text-emerald-600 hover:bg-emerald-50 rounded"
                                                                title="Approuver cette étape"
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
                                                    {inst.status === 'in_progress' && !inst.can_approve && (
                                                        <span className="text-[11px] text-slate-400 italic">En cours</span>
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
                                <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                    <div>
                                        <h3 className="text-sm font-semibold text-slate-900">Modèles de circuit configurés</h3>
                                        <p className="text-xs text-slate-500">Ces modèles définissent les étapes et approbateurs pour la validation documentaire.</p>
                                    </div>
                                    {canCreate && (
                                        <Button size="sm" variant="primary" onClick={() => setCreateModalOpen(true)}>
                                            <Plus className="w-3.5 h-3.5" />
                                            Nouveau modèle
                                        </Button>
                                    )}
                                </div>
                                {(workflows?.data || workflows || []).length > 0 ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        {(workflows?.data || workflows || []).map((wf) => (
                                            <div key={wf.id} className="p-5 rounded-xl border border-slate-200 bg-white shadow-2xs space-y-3 flex flex-col justify-between">
                                                <div className="space-y-3">
                                                    <div className="flex items-center justify-between">
                                                        <h3 className="font-bold text-base text-slate-900">{wf.name}</h3>
                                                        <div className="flex items-center gap-2">
                                                            <Badge variant={wf.is_active ? 'success' : 'default'}>
                                                                {wf.is_active ? 'Actif' : 'Inactif'}
                                                            </Badge>
                                                            {canCreate && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleDeleteWorkflow(wf.id)}
                                                                    className="p-1 text-slate-400 hover:text-rose-600 transition"
                                                                    title="Supprimer ce workflow"
                                                                >
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </div>
                                                    {wf.description && <p className="text-xs text-slate-500">{wf.description}</p>}
                                                    <div className="pt-2 border-t border-slate-100 text-xs text-slate-600">
                                                        <strong className="text-slate-800">Étapes définies ({wf.steps?.length || 0}) :</strong>
                                                        <ol className="list-decimal list-inside mt-1.5 space-y-1 text-slate-600">
                                                            {wf.steps?.map((s, idx) => (
                                                                <li key={s.id || idx}>
                                                                    <span className="font-medium text-slate-800">{s.name}</span>
                                                                    <span className="text-slate-400 text-[11px] ml-1">
                                                                        ({s.approver_type === 'user'
                                                                            ? (s.approver_user ? `${s.approver_user.first_name || ''} ${s.approver_user.last_name || ''}`.trim() || s.approver_user.email : 'Collaborateur')
                                                                            : (s.approver_group?.name || 'Groupe')})
                                                                    </span>
                                                                </li>
                                                            ))}
                                                        </ol>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <EmptyState
                                        title="Aucun modèle de workflow défini"
                                        description="Créez votre premier modèle de validation pour automatiser l'approbation des documents."
                                        action={canCreate ? (
                                            <Button size="sm" variant="primary" onClick={() => setCreateModalOpen(true)}>
                                                <Plus className="w-4 h-4" />
                                                Créer un modèle
                                            </Button>
                                        ) : null}
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

            {/* Create Workflow Modal */}
            <Modal
                isOpen={createModalOpen}
                onClose={() => {
                    setCreateModalOpen(false);
                    createForm.clearErrors();
                }}
                title="Créer un modèle de workflow"
                maxWidth="max-w-2xl"
            >
                <form onSubmit={handleCreateSubmit} className="space-y-5">
                    <Input
                        id="wf-name"
                        label="Nom du workflow"
                        required
                        placeholder="Ex: Validation Contrat Fournisseur, Approbation Facture..."
                        value={createForm.data.name}
                        onChange={(e) => createForm.setData('name', e.target.value)}
                        error={createForm.errors.name}
                    />

                    <Textarea
                        id="wf-description"
                        label="Description (optionnelle)"
                        placeholder="Précisez le rôle de ce circuit et les règles applicables..."
                        rows={2}
                        value={createForm.data.description}
                        onChange={(e) => createForm.setData('description', e.target.value)}
                        error={createForm.errors.description}
                    />

                    {/* Steps Section */}
                    <div className="space-y-3 pt-2 border-t border-slate-100">
                        <div className="flex items-center justify-between">
                            <div>
                                <h4 className="text-sm font-bold text-slate-800">Étapes de validation</h4>
                                <p className="text-xs text-slate-500">Chaque étape doit être approuvée pour passer à la suivante.</p>
                            </div>
                            <Button type="button" variant="secondary" size="sm" onClick={addStep}>
                                <Plus className="w-3.5 h-3.5" />
                                Ajouter une étape
                            </Button>
                        </div>

                        <div className="space-y-3 max-h-60 overflow-y-auto pr-1">
                            {createForm.data.steps.map((step, idx) => (
                                <div key={idx} className="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 space-y-3 relative">
                                    <div className="flex items-center justify-between gap-2">
                                        <span className="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs font-bold flex items-center justify-center shrink-0">
                                            {idx + 1}
                                        </span>
                                        <div className="flex-1">
                                            <Input
                                                id={`step-name-${idx}`}
                                                required
                                                placeholder={`Nom de l'étape ${idx + 1} (ex: Vérification comptable)`}
                                                value={step.name}
                                                onChange={(e) => updateStep(idx, 'name', e.target.value)}
                                            />
                                        </div>
                                        {createForm.data.steps.length > 1 && (
                                            <button
                                                type="button"
                                                onClick={() => removeStep(idx)}
                                                className="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-white transition"
                                                title="Supprimer cette étape"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        )}
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-8">
                                        <Select
                                            id={`step-approver-type-${idx}`}
                                            label="Type d'approbateur"
                                            value={step.approver_type}
                                            onChange={(e) => updateStep(idx, 'approver_type', e.target.value)}
                                            options={[
                                                { value: 'user', label: 'Collaborateur individuel' },
                                                { value: 'group', label: 'Groupe / Direction' },
                                            ]}
                                        />

                                        {step.approver_type === 'user' ? (
                                            availableUsers.length > 0 ? (
                                                <Select
                                                    id={`step-approver-user-${idx}`}
                                                    label="Approbateur désigné"
                                                    required
                                                    value={step.approver_user_id}
                                                    onChange={(e) => updateStep(idx, 'approver_user_id', e.target.value)}
                                                    placeholder="-- Choisir un utilisateur --"
                                                    options={availableUsers.map((u) => ({
                                                        value: String(u.id),
                                                        label: `${u.first_name || ''} ${u.last_name || ''} (${u.email})`.trim(),
                                                    }))}
                                                />
                                            ) : (
                                                <Input
                                                    id={`step-approver-user-fb-${idx}`}
                                                    label="ID de l'utilisateur"
                                                    type="number"
                                                    required
                                                    value={step.approver_user_id}
                                                    onChange={(e) => updateStep(idx, 'approver_user_id', e.target.value)}
                                                />
                                            )
                                        ) : (
                                            availableGroups.length > 0 ? (
                                                <Select
                                                    id={`step-approver-group-${idx}`}
                                                    label="Groupe approbateur"
                                                    required
                                                    value={step.approver_group_id}
                                                    onChange={(e) => updateStep(idx, 'approver_group_id', e.target.value)}
                                                    placeholder="-- Choisir un groupe --"
                                                    options={availableGroups.map((g) => ({
                                                        value: String(g.id),
                                                        label: g.name,
                                                    }))}
                                                />
                                            ) : (
                                                <Input
                                                    id={`step-approver-group-fb-${idx}`}
                                                    label="ID du groupe"
                                                    type="number"
                                                    required
                                                    value={step.approver_group_id}
                                                    onChange={(e) => updateStep(idx, 'approver_group_id', e.target.value)}
                                                />
                                            )
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex justify-end gap-2 pt-4 border-t border-slate-100">
                        <Button variant="secondary" onClick={() => setCreateModalOpen(false)}>Annuler</Button>
                        <Button type="submit" variant="primary" loading={createForm.processing}>
                            Créer le modèle de workflow
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
