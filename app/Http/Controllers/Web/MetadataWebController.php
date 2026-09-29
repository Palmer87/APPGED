<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MetadataDefinition;
use App\Services\MetadataDefinitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MetadataWebController extends Controller
{
    public function __construct(
        protected MetadataDefinitionService $metadataService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user->can('metadata.view')) {
            abort(403, 'Unauthorized to view metadata definitions.');
        }

        $definitions = MetadataDefinition::where('organization_id', $user->organization_id)
            ->withCount('values')
            ->orderBy('name')
            ->get();

        return Inertia::render('Metadata/Index', [
            'definitions' => $definitions,
            'allowedTypes' => MetadataDefinitionService::ALLOWED_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->can('metadata.create')) {
            abort(403, 'Unauthorized to create metadata definitions.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('metadata_definitions', 'key')->where('organization_id', $user->organization_id),
            ],
            'type' => ['required', 'string', 'in:'.implode(',', MetadataDefinitionService::ALLOWED_TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'key.regex' => 'La clé technique doit commencer par une lettre minuscule et contenir uniquement des lettres minuscules, chiffres et tirets bas (_).',
            'key.unique' => 'Cette clé technique existe déjà pour votre organisation.',
        ]);

        try {
            $this->metadataService->create($validated);
        } catch (HttpException $e) {
            return back()->withErrors(['key' => $e->getMessage()]);
        }

        return back()->with('success', 'Définition de métadonnée créée avec succès.');
    }

    public function update(Request $request, MetadataDefinition $metadata): RedirectResponse
    {
        $user = $request->user();
        if ($metadata->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized.');
        }

        if (! $user->can('metadata.update')) {
            abort(403, 'Unauthorized to update metadata definitions.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('metadata_definitions', 'key')
                    ->where('organization_id', $user->organization_id)
                    ->ignore($metadata->id),
            ],
            'type' => ['required', 'string', 'in:'.implode(',', MetadataDefinitionService::ALLOWED_TYPES)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'key.regex' => 'La clé technique doit commencer par une lettre minuscule et contenir uniquement des lettres minuscules, chiffres et tirets bas (_).',
            'key.unique' => 'Cette clé technique existe déjà pour votre organisation.',
        ]);

        try {
            $this->metadataService->update($metadata, $validated);
        } catch (HttpException $e) {
            return back()->withErrors(['key' => $e->getMessage()]);
        }

        return back()->with('success', 'Définition de métadonnée mise à jour avec succès.');
    }

    public function destroy(Request $request, MetadataDefinition $metadata): RedirectResponse
    {
        $user = $request->user();
        if ($metadata->organization_id !== $user->organization_id) {
            abort(403, 'Unauthorized.');
        }

        if (! $user->can('metadata.delete')) {
            abort(403, 'Unauthorized to delete metadata definitions.');
        }

        $this->metadataService->delete($metadata);

        return back()->with('success', 'Définition de métadonnée supprimée avec succès.');
    }
}
