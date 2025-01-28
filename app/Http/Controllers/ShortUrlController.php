<?php

namespace App\Http\Controllers;

use App\Exceptions\ShortUrlServiceException;
use App\Services\ShortUrlService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShortUrlController extends Controller
{
    /**
     * Retrieve the details of a short url.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function getCodeDetails(Request $request)
    {

        try {
            $validated = $request->validate([
                'code' => 'required|max:10'
            ]);

            $shortUrlService = new ShortUrlService();

            $details = $shortUrlService->getCodeDetails($validated['code']);

            return response()->json($details, 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (ShortUrlServiceException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['message' => 'An unexpected error occurred.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve the details of a short url.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function getRedirects(Request $request)
    {
        try {
            $validated = $request->validate([
                'limit' => 'integer|max:1000',
                'orderByClicks' => 'boolean'
            ]);

            $limit = $validated['limit'] ?? 100;
            $orderByClicks = $validated['orderByClicks'] ?? true;

            $shortUrlService = new ShortUrlService();

            $redirects = $shortUrlService->getRedirects($limit, $orderByClicks);

            return response()->json($redirects, 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (ShortUrlServiceException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            \Log::error($e);
            return response()->json(['message' => 'An unexpected error occurred.', 'error' => $e->getMessage()], 500);
        }
    }
}
