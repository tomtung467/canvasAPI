<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CanvasApiService
{
    protected $baseUrl;
    protected $token;

    public function __construct()
    {
        $this->baseUrl = config('services.canvas.url');
        $this->token = config('services.canvas.token');
    }

    public function request($method, $endpoint, $params = [])
    {
        $response = Http::withToken($this->token)
            ->$method($this->baseUrl . $endpoint, $params);

        return $response->json();
    }

    // Lấy danh sách course
    public function getCourses()
    {
        return $this->request('get', '/courses');
    }

    // Lấy user theo ID
    public function getUser($id)
    {
        return $this->request('get', '/users/' . $id);
    }
}
