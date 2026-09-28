import React from 'react';
import Input from './Input';
import Textarea from './Textarea';
import Select from './Select';

export default function MetadataForm({
    definitions = [],
    values = {},
    onChange,
    errors = {},
    disabled = false,
    columns = 2,
    isSearchMode = false,
}) {
    if (!definitions || definitions.length === 0) {
        return (
            <div className="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-6 text-center text-xs text-slate-500">
                Aucune métadonnée spécifique n'est configurée pour ce type de document.
            </div>
        );
    }

    const handleChange = (key, val) => {
        if (onChange) {
            onChange(key, val);
        }
    };

    const getError = (key, id) => {
        return (
            errors[`metadata.${key}`] ||
            errors[`metadata.${id}`] ||
            errors[key] ||
            errors[id] ||
            null
        );
    };

    const sortedDefinitions = [...definitions].sort((a, b) => (a.order || 0) - (b.order || 0));

    const gridColsClass = columns === 1 ? 'grid-cols-1' : 'grid-cols-1 sm:grid-cols-2';

    return (
        <div className={`grid ${gridColsClass} gap-4`}>
            {sortedDefinitions.map((def) => {
                const key = def.key;
                const value = values[key] ?? values[def.id] ?? '';
                const isRequired = !isSearchMode && Boolean(def.is_required);
                const fieldError = getError(key, def.id);

                switch (def.type) {
                    case 'text':
                        return (
                            <div key={def.id || key} className={columns > 1 ? 'sm:col-span-2' : ''}>
                                <Textarea
                                    id={`metadata_${key}`}
                                    label={def.name}
                                    required={isRequired}
                                    value={value}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                    error={fieldError}
                                    disabled={disabled}
                                    placeholder={isSearchMode ? `Rechercher dans ${def.name.toLowerCase()}...` : (def.description || `Saisissez ${def.name.toLowerCase()}`)}
                                    rows={3}
                                />
                                {def.description && !fieldError && !isSearchMode && (
                                    <p className="mt-1 text-[11px] text-slate-400">{def.description}</p>
                                )}
                            </div>
                        );

                    case 'integer':
                        return (
                            <div key={def.id || key}>
                                <Input
                                    id={`metadata_${key}`}
                                    type="number"
                                    step="1"
                                    label={def.name}
                                    required={isRequired}
                                    value={value}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                    error={fieldError}
                                    disabled={disabled}
                                    placeholder={isSearchMode ? 'Ex: 2026' : (def.description || 'Ex: 123')}
                                />
                                {def.description && !fieldError && !isSearchMode && (
                                    <p className="mt-1 text-[11px] text-slate-400">{def.description}</p>
                                )}
                            </div>
                        );

                    case 'decimal':
                        return (
                            <div key={def.id || key}>
                                <Input
                                    id={`metadata_${key}`}
                                    type="number"
                                    step="0.01"
                                    label={def.name}
                                    required={isRequired}
                                    value={value}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                    error={fieldError}
                                    disabled={disabled}
                                    placeholder={isSearchMode ? 'Montant ou valeur' : (def.description || '0.00')}
                                />
                                {def.description && !fieldError && !isSearchMode && (
                                    <p className="mt-1 text-[11px] text-slate-400">{def.description}</p>
                                )}
                            </div>
                        );

                    case 'date':
                        return (
                            <div key={def.id || key}>
                                <Input
                                    id={`metadata_${key}`}
                                    type="date"
                                    label={def.name}
                                    required={isRequired}
                                    value={value}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                    error={fieldError}
                                    disabled={disabled}
                                />
                                {def.description && !fieldError && !isSearchMode && (
                                    <p className="mt-1 text-[11px] text-slate-400">{def.description}</p>
                                )}
                            </div>
                        );

                    case 'datetime':
                        return (
                            <div key={def.id || key}>
                                <Input
                                    id={`metadata_${key}`}
                                    type="datetime-local"
                                    label={def.name}
                                    required={isRequired}
                                    value={value}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                    error={fieldError}
                                    disabled={disabled}
                                />
                                {def.description && !fieldError && !isSearchMode && (
                                    <p className="mt-1 text-[11px] text-slate-400">{def.description}</p>
                                )}
                            </div>
                        );

                    case 'boolean':
                        return (
                            <div key={def.id || key}>
                                <label className="block text-sm font-medium text-slate-700 mb-1">
                                    {def.name} {isRequired && <span className="text-rose-500">*</span>}
                                </label>
                                <Select
                                    id={`metadata_${key}`}
                                    value={value === '' || value === null || value === undefined ? '' : String(value)}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        handleChange(key, v === '' ? '' : v === 'true' || v === '1');
                                    }}
                                    error={fieldError}
                                    disabled={disabled}
                                    placeholder={isSearchMode ? 'Tous' : 'Sélectionnez...'}
                                    options={[
                                        { value: 'true', label: 'Oui' },
                                        { value: 'false', label: 'Non' },
                                    ]}
                                />
                                {def.description && !fieldError && !isSearchMode && (
                                    <p className="mt-1 text-[11px] text-slate-400">{def.description}</p>
                                )}
                            </div>
                        );

                    case 'string':
                    default:
                        return (
                            <div key={def.id || key}>
                                <Input
                                    id={`metadata_${key}`}
                                    type="text"
                                    label={def.name}
                                    required={isRequired}
                                    value={value}
                                    onChange={(e) => handleChange(key, e.target.value)}
                                    error={fieldError}
                                    disabled={disabled}
                                    placeholder={isSearchMode ? `Filtrer par ${def.name.toLowerCase()}...` : (def.description || `Saisissez ${def.name.toLowerCase()}`)}
                                />
                                {def.description && !fieldError && !isSearchMode && (
                                    <p className="mt-1 text-[11px] text-slate-400">{def.description}</p>
                                )}
                            </div>
                        );
                }
            })}
        </div>
    );
}
