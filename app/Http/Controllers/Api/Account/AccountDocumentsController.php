<?php

namespace App\Http\Controllers\Api\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountDocumentsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $documents = $request->user()
            ->customerDocuments()
            ->where(function ($query): void {
                $query->whereNull('available_from')->orWhere('available_from', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->get()
            ->map(fn (CustomerDocument $document): array => [
                'id' => $document->id,
                'type' => $document->type,
                'title' => $document->title,
                'external_id' => $document->external_id,
                'available_from' => $document->available_from,
                'expires_at' => $document->expires_at,
                'download_url' => route('account.documents.download', $document),
            ]);

        return response()->json(['documents' => $documents]);
    }

    public function download(Request $request, CustomerDocument $document): StreamedResponse
    {
        abort_unless((int) $document->user_id === (int) $request->user()->id, 404);
        abort_unless($document->isAvailable(), 404);

        $disk = config("filesystems.disks.{$document->disk}")
            ? $document->disk
            : 'local';

        abort_unless(Storage::disk($disk)->exists($document->path), 404);

        return Storage::disk($disk)->download(
            $document->path,
            $document->title ?: basename($document->path),
        );
    }
}
