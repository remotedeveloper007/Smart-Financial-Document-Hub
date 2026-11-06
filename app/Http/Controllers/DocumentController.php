<?php

namespace App\Http\Controllers;

use App\Actions\Documents\UploadDocumentAction;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Services\Tenancy\TeamContext;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final class DocumentController extends Controller
{
    public function page(): Response
    {
        $this->authorize('create', Document::class);

        return Inertia::render('Documents/Upload');
    }

    public function index(TeamContext $teamContext): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $documents = Document::query()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'file_path', 'status', 'created_at']);

        return response()->json(['documents' => $documents]);
    }

    public function store(StoreDocumentRequest $request, UploadDocumentAction $uploadDocumentAction): JsonResponse
    {
        $this->authorize('create', Document::class);

        $document = $uploadDocumentAction->execute(
            $request->file('file'),
            $request->input('name'),
        );

        return response()->json($document, 201);
    }
}
