<?php

namespace App\Services\Canvas;

class CanvasUserService extends CanvasBaseService
{
    /**
     * Lấy thông tin user theo ID
     */
    public function getUser(int $userId)
    {
        $user = $this->request('get', "/api/v1/users/{$userId}");

        $this->logAction(
            'FETCH_USER',
            'user',
            $userId,
            null,
            'success'
        );

        return $user;
    }
    public function getCurrentUser()
    {
        $user = $this->request('get', '/api/v1/users/self');

        $this->logAction(
            'FETCH_CURRENT_USER',
            'user',
            null,
            null,
            'success'
        );

        return $user;
    }
    public function getUserEnrollments(int $userId)
    {
        $enrollments = $this->request('get', "/api/v1/users/{$userId}/enrollments", [
            'per_page' => 100
        ]);

        $this->logAction(
            'FETCH_USER_ENROLLMENTS',
            'user',
            $userId,
            ['count' => count($enrollments)],
            'success'
        );

        return $enrollments;
    }
    /**
     * Tìm kiếm users theo tên
     */
    public function searchUsers(string $searchTerm)
    {
        $users = $this->request('get', '/api/v1/accounts/self/users', [
            'search_term' => $searchTerm
        ]);

        $this->logAction(
            'SEARCH_USERS',
            'user',
            null,
            ['search_term' => $searchTerm],
            'success'
        );

        return $users;
    }
}
