<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductMedia;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductMediaController extends Controller
{
    public function show(int $productId, int $mediaId): StreamedResponse
    {
        $product = Product::query()->findOrFail($productId);
        Gate::authorize('view', $product);
        $media = ProductMedia::query()
            ->where('product_id', $product->id)
            ->findOrFail($mediaId);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        return $this->stream($disk, $media->path, $media->original_name, $media->mime_type, 'private');
    }

    public function publicShow(int $productId, int $mediaId): StreamedResponse
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where('commercial_status', '!=', 'discontinued')
            ->findOrFail($productId);
        $media = ProductMedia::query()
            ->where('product_id', $product->id)
            ->findOrFail($mediaId);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($media->disk);
        abort_unless($disk->exists($media->path), 404);

        return $this->stream($disk, $media->path, $media->original_name, $media->mime_type, 'public');
    }

    private function stream(FilesystemAdapter $disk, string $path, string $name, string $mimeType, string $visibility): StreamedResponse
    {
        return $disk->response($path, $name, [
            'Content-Type' => $mimeType,
            'Cache-Control' => $visibility.', max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
