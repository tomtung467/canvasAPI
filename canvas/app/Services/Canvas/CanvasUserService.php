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
        $data = collect($user)->only([
            'id',
            'name',
            'email',
            'created_at',
            'last_login'
        ])->toArray();

        $this->logAction(
            'FETCH_USER',
            'user',
            $userId,
            null,
            'success'
        );

        return $data;
    }
    public function getCurrentUser()
    {
        $user = $this->request('get', '/api/v1/users/self');
        $data = collect($user)->only([
            'id',
            'name',
            'email',
            'created_at',
            'last_login'
        ])->toArray();

        $this->logAction(
            'FETCH_CURRENT_USER',
            'user',
            null,
            null,
            'success'
        );

        return $data;
    }
    public function getUserEnrollments(int $userId)
    {
        $enrollments = $this->request('get', "/api/v1/users/{$userId}/enrollments", [
            'per_page' => 100,
        ]);
        $data = collect($enrollments)->map(function ($item) {
            return [
                'course_id' => $item['course_id'],
                'enrollment_state' => $item['enrollment_state'],
                'role' => $item['role'],
                'created_at' => $item['created_at'],
                'last_activity_at' => $item['last_activity_at'],
            ];
        })->toArray();

        $this->logAction(
            'FETCH_USER_ENROLLMENTS',
            'user',
            $userId,
            ['count' => count($enrollments)],
            'success'
        );

        return $data;
    }
    public function GetUserProfile(int $userId)
    {
        $profile = $this->request('get', "/api/v1/users/{$userId}/profile");
        $data = collect($profile)->only([
            'id',
            'name',
            'avatar_url',
            'primary_email',
            'created_at',
            'last_login'
        ])->toArray();

        $this->logAction(
            'FETCH_USER_PROFILE',
            'user',
            $userId,
            null,
            'success'
        );

        return $data;
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
