<?php

namespace App\Http\Middleware;

use App\Services\AbacService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AbacMiddleware
{
    public function __construct(protected AbacService $abacService) {}

    /**
     * Handle incoming request dengan ABAC check.
     *
     * Cara pakai di route:
      *   ->middleware('abac:dashboard.view')
      *   ->middleware('abac:lab_qa.create,lab_qa.update')
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'code'    => 'UNAUTHENTICATED',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Akun Anda tidak aktif. Hubungi Administrator.',
                'code'    => 'ACCOUNT_INACTIVE',
            ], 403);
        }

        // Cek permission — user harus memiliki SALAH SATU dari permission yang diminta
        foreach ($permissions as $permission) {
            $context = $this->extractContext($request);

            if ($this->abacService->can($user, $permission, $context)) {
                return $next($request);
            }
        }

        return response()->json([
            'message'     => 'Anda tidak memiliki akses untuk tindakan ini.',
            'code'        => 'FORBIDDEN',
            'required'    => $permissions,
        ], 403);
    }

    /**
     * Ekstrak konteks resource dari request untuk evaluasi ABAC.
     */
    protected function extractContext(Request $request): array
    {
        return [
            'area_kebun' => $request->route('area_kebun') ?? $request->input('area_kebun'),
            'action'     => $request->method(),
            'ip'         => $request->ip(),
        ];
    }
}
