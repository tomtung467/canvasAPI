<?php
namespace App\Services\Canvas;
use Illuminate\Support\Facades\Http;
use App\Models\ActionLog;
use Illuminate\Support\Facades\Log;
abstract class CanvasBaseService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        // Chuẩn hóa base URL để tránh double slash
        $this->baseUrl = rtrim(config('services.canvas.url'), '/');
            // Lấy token ưu tiên từ user đang đăng nhập (cột `canvas_access_token`),
            // nếu không có thì fallback về token trong .env (config services.canvas.token)
            $this->token = optional(auth()->user())->canvas_access_token ?: config('services.canvas.token');
    }

    public function request($method, $endpoint, $params = [])
    {
        try {
            $response = Http::withToken($this->token)
                ->$method($this->baseUrl . $endpoint, $params);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Canvas connection error', ['endpoint' => $endpoint, 'message' => $e->getMessage()]);
            throw $e;
        }

        return $response->json();
    }

    /**
     * Quick check to validate that the configured base URL and token work
     * Returns array with 'ok' => bool and 'detail' => response or error
     */
    public function validateConnection()
    {
        try {
            $resp = Http::withToken($this->token)
                ->get(rtrim($this->baseUrl, '/') . '/api/v1/users/self/profile');
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Canvas validateConnection failed', ['message' => $e->getMessage()]);
            return ['ok' => false, 'error' => 'Connection failed', 'detail' => $e->getMessage()];
        }

        if ($resp->successful()) {
            return ['ok' => true, 'detail' => $resp->json()];
        }

        return ['ok' => false, 'error' => 'Invalid response', 'status' => $resp->status(), 'detail' => $resp->json()];
    }

    /**
     * Log action vào bảng action_logs
     */
    protected function logAction(
        string $actionType,
        string $resource,
        $targetId = null,
        $payload = null,
        string $status = 'success'
    ): void {
        ActionLog::create([
            'user_id' => auth()->id(),
            'action_type' => $actionType,
            'target_resource' => $resource,
            'target_id' => $targetId,
            'payload' => $payload,
            'status' => $status,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
