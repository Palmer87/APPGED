<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MetadataDefinition;
use App\Services\MetadataDefinitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
            ->orderBy('order')
            ->get();

        return Inertia::render('Metadata/Index', [
            'definitions' => $definitions,
            'allowedTypes' => MetadataDefinitionService::ALLOWED_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
            'type' => ['required', 'string', 'in:'.implode(',', MetadataDefinitionService::ALLOWED_TYPES)],
            'description' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->metadataService->create($validated);

        return back()->with('success', 'Définition de métadonnée créée avec succès.');
    }

    public function update(Request $request, MetadataDefinition $metadata): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/'],
            'type' => ['required', 'string', 'in:'.implode(',', MetadataDefinitionService::ALLOWED_TYPES)],
            'description' => ['nullable', 'string'],
            'is_required' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $this->metadataService->update($metadata, $validated);

        return back()->with('success', 'Définition de métadonnée mise à jour avec succès.');
    }

    public function destroy(Request $request, MetadataDefinition $metadata): RedirectResponse
    {
        $this->metadataService->delete($metadata);

        return back()->with('success', 'Définition de métadonnée supprimée avec succès.');
    }
}
