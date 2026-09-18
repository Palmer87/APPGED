import React, { useState } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout';
import Button from '../../Components/Button';
import Input from '../../Components/Input';
import Modal from '../../Components/Modal';
import Badge from '../../Components/Badge';
import {
    ArrowLeft,
    Sliders,
    Plus,
    Trash2,
    Check,
    AlertCircle,
    Building2,
    FileStack,
    Save,
    GripVertical
} from 'lucide-react';

export default function DocumentTypeMetadata({
    documentType = {},
    assignedDefinitions = [],
    availableDefinitions = [],
    can = {},
}) {
    // Current state of assigned definitions
    const [selectedDefs, setSelectedDefs] = useState(assignedDefinitions);
    const [attachModalOpen, setAttachModalOpen] = useState(false);
    const [newDefModalOpen, setNewDefModalOpen] = useState(false);

    // Form for saving assigned definitions
    const saveForm = useForm({
        definitions: [],
    });

    // Form for creating a new definition on the fly
    const createDefForm = useForm({
        name: '',
        key: '',
        type: 'string',
        description: '',
        is_required: false,
    });

    const isAttached = (defId) => selectedDefs.some((d) => d.id === defId);

    const toggleAttach = (def) => {
        if (isAttached(def.id)) {
            setSelectedDefs(selectedDefs.filter((d) => d.id !== def.id));
        } else {
            setSelectedDefs([
                ...selectedDefs,
                {
                    id: def.id,
                    name: def.name,
                    key: def.key,
                    type: def.type,
                    is_required: def.is_required,
                    order: selectedDefs.length + 1,
                },
            ]);
        }
    };

    const toggleRequired = (defId) => {
        setSelectedDefs(
            selectedDefs.map((d) => (d.id === defId ? { ...d, is_required: !d.is_required } : d))
        );
    };

    const updateOrder = (defId, newOrder) => {
        setSelectedDefs(
            selectedDefs.map((d) => (d.id === defId ? { ...d, order: parseInt(newOrder, 10) || 0 } : d))
        );
    };

    const removeDef = (defId) => {
        setSelectedDefs(selectedDefs.filter((d) => d.id !== defId));
    };

    const handleSave = () => {
        saveForm.setData(
            'definitions',
            selectedDefs.map((d, index) => ({
                id: d.id,
                is_required: d.is_required,
                order: d.order || index + 1,
            }))
        );

        router.post(
            `/document-types/${documentType.id}/metadata`,
            {
                definitions: selectedDefs.map((d, index) => ({
                    id: d.id,
                    is_required: d.is_required,
                    order: d.order || index + 1,
                })),
            },
            {
                preserveScroll: true,
            }
        );
    };

    const handleCreateDef = (e) => {
        e.preventDefault();
        createDefForm.post('/metadata', {
            onSuccess: () => {
                setNewDefModalOpen(false);
                createDefForm.reset();
                router.reload({ only: ['availableDefinitions'] });
            },
        });
    };

    const autoGenerateKey = (name) => {
        return name
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9_]/g, '_')
            .replace(/^_+|_+$/g, '')
            .replace(/_+/g, '_');
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Métadonnées — ${documentType.name}`} />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/document-types"
                            className="p-2 text-slate-400 hover:text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition"
                        >
                            <ArrowLeft className="w-4 h-4" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
                                    {documentType.name}
                                </h1>
                                <Badge variant="primary">Type documentaire</Badge>
                            </div>
                            <p className="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                                <Building2 className="w-3.5 h-3.5 text-slate-400" />
                                Direction : <span className="font-semibold text-slate-700">{documentType.department_name}</span>
                            </p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button variant="secondary" onClick={() => setAttachModalOpen(true)}>
                            <Plus className="w-4 h-4 mr-1" />
                            Associer des métadonnées
                        </Button>
                        <Button variant="primary" onClick={handleSave}>
                            <Save className="w-4 h-4 mr-1" />
                            Enregistrer la configuration
                        </Button>
                    </div>
                </div>

                {/* Info box */}
                <div className="p-4 rounded-2xl bg-blue-50/70 border border-blue-100 flex items-start gap-3">
                    <Sliders className="w-5 h-5 text-blue-600 shrink-0 mt-0.5" />
                    <div className="text-xs text-blue-900">
                        <p className="font-semibold mb-0.5">Configuration des métadonnées métier</p>
                        <p className="text-blue-700">
                            Chaque document ajouté dans ce type documentaire proposera automatiquement ce formulaire de saisie. Les champs obligatoires devront impérativement être complétés par l'utilisateur lors du classement.
                        </p>
                    </div>
                </div>

                {/* Table des métadonnées associées */}
                <div className="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
                    <div className="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h2 className="text-sm font-bold text-slate-800">
                            Champs de métadonnées configurés ({selectedDefs.length})
                        </h2>
                        <span className="text-xs text-slate-400">
                            Glissez ou ajustez l'ordre selon votre préférence
                        </span>
                    </div>

                    {selectedDefs.length > 0 ? (
                        <div className="divide-y divide-slate-100">
                            {selectedDefs.map((def, idx) => (
                                <div
                                    key={def.id}
                                    className="px-6 py-3.5 flex items-center justify-between hover:bg-slate-50/60 transition gap-4"
                                >
                                    <div className="flex items-center gap-3 min-w-0">
                                        <span className="text-slate-300 font-mono text-xs w-5 text-center">
                                            {idx + 1}
                                        </span>
                                        <div>
                                            <div className="font-semibold text-sm text-slate-900 flex items-center gap-2">
                                                {def.name}
                                                {def.is_required && (
                                                    <span className="text-rose-500 font-bold" title="Obligatoire">*</span>
                                                )}
                                            </div>
                                            <div className="text-xs font-mono text-slate-400">
                                                {def.key} • type: <span className="text-indigo-600 font-semibold">{def.type}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-4 shrink-0">
                                        {/* Toggle Obligatoire */}
                                        <label className="flex items-center gap-2 cursor-pointer select-none">
                                            <input
                                                type="checkbox"
                                                checked={def.is_required}
                                                onChange={() => toggleRequired(def.id)}
                                                className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span className="text-xs font-medium text-slate-700">
                                                Obligatoire
                                            </span>
                                        </label>

                                        {/* Ordre */}
                                        <div className="flex items-center gap-1.5">
                                            <span className="text-xs text-slate-400">Ordre :</span>
                                            <input
                                                type="number"
                                                min="1"
                                                value={def.order || idx + 1}
                                                onChange={(e) => updateOrder(def.id, e.target.value)}
                                                className="w-14 text-xs font-mono text-center rounded-lg border border-slate-200 py-1"
                                            />
                                        </div>

                                        {/* Supprimer */}
                                        <button
                                            onClick={() => removeDef(def.id)}
                                            className="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 transition"
                                            title="Retirer ce champ"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-12 text-center">
                            <Sliders className="w-8 h-8 text-slate-300 mx-auto mb-2" />
                            <p className="text-sm font-semibold text-slate-700">Aucune métadonnée configurée</p>
                            <p className="text-xs text-slate-400 mt-1 mb-4">
                                Ce type documentaire ne demande actuellement aucune métadonnée spécifique.
                            </p>
                            <Button variant="primary" onClick={() => setAttachModalOpen(true)}>
                                <Plus className="w-4 h-4 mr-1.5" />
                                Ajouter des métadonnées
                            </Button>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Sélection des métadonnées existantes */}
            <Modal
                show={attachModalOpen}
                onClose={() => setAttachModalOpen(false)}
                title="Associer des métadonnées"
            >
                <div className="space-y-4">
                    <div className="flex items-center justify-between pb-2 border-b border-slate-100">
                        <p className="text-xs text-slate-500">
                            Cochez les définitions applicables à ce type documentaire.
                        </p>
                        <button
                            onClick={() => {
                                setAttachModalOpen(false);
                                setNewDefModalOpen(true);
                            }}
                            className="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                        >
                            + Créer une nouvelle définition
                        </button>
                    </div>

                    <div className="max-h-72 overflow-y-auto divide-y divide-slate-100 pr-1">
                        {availableDefinitions.map((def) => {
                            const checked = isAttached(def.id);
                            return (
                                <label
                                    key={def.id}
                                    className={`flex items-center justify-between p-3 rounded-xl cursor-pointer transition ${
                                        checked ? 'bg-blue-50/50' : 'hover:bg-slate-50'
                                    }`}
                                >
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            checked={checked}
                                            onChange={() => toggleAttach(def)}
                                            className="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                        <div>
                                            <div className="text-xs font-bold text-slate-900">{def.name}</div>
                                            <div className="text-[11px] font-mono text-slate-400">
                                                {def.key} ({def.type})
                                            </div>
                                        </div>
                                    </div>
                                    <span className="text-[11px] px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium">
                                        {def.type}
                                    </span>
                                </label>
                            );
                        })}
                    </div>

                    <div className="flex justify-end pt-2">
                        <Button variant="primary" onClick={() => setAttachModalOpen(false)}>
                            Terminer la sélection
                        </Button>
                    </div>
                </div>
            </Modal>

            {/* Modal Création rapide d'une métadonnée */}
            <Modal
                show={newDefModalOpen}
                onClose={() => setNewDefModalOpen(false)}
                title="Créer une métadonnée"
            >
                <form onSubmit={handleCreateDef} className="space-y-4">
                    <Input
                        label="Libellé du champ"
                        value={createDefForm.data.name}
                        onChange={(e) => {
                            const name = e.target.value;
                            createDefForm.setData({
                                name,
                                key: autoGenerateKey(name),
                            });
                        }}
                        placeholder="Ex : Numéro client, Date d'échéance..."
                        error={createDefForm.errors.name}
                        required
                    />

                    <Input
                        label="Clé technique (snake_case)"
                        value={createDefForm.data.key}
                        onChange={(e) => createDefForm.setData('key', e.target.value)}
                        placeholder="numero_client"
                        error={createDefForm.errors.key}
                        required
                    />

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Type de données
                        </label>
                        <select
                            value={createDefForm.data.type}
                            onChange={(e) => createDefForm.setData('type', e.target.value)}
                            className="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 bg-white"
                        >
                            <option value="string">Texte court (string)</option>
                            <option value="text">Texte long (text)</option>
                            <option value="integer">Nombre entier (integer)</option>
                            <option value="decimal">Montant / Décimal (decimal)</option>
                            <option value="date">Date (YYYY-MM-DD)</option>
                            <option value="datetime">Date et heure (datetime)</option>
                            <option value="boolean">Booléen / Oui-Non (boolean)</option>
                        </select>
                    </div>

                    <div>
                        <label className="block text-xs font-semibold text-slate-700 mb-1">
                            Description ou consigne
                        </label>
                        <textarea
                            value={createDefForm.data.description}
                            onChange={(e) => createDefForm.setData('description', e.target.value)}
                            rows={2}
                            placeholder="Aide ou consigne affichée à l'utilisateur..."
                            className="w-full text-sm rounded-xl border border-slate-200 px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 transition"
                        />
                    </div>

                    <div className="flex justify-end gap-2 pt-3">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setNewDefModalOpen(false)}
                        >
                            Annuler
                        </Button>
                        <Button type="submit" variant="primary" loading={createDefForm.processing}>
                            Créer la métadonnée
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
