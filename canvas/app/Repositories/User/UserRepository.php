<?php
namespace App\Repositories\User;

use App\Repositories\BaseRepository;
use App\Models\User;
use App\Repositories\User\IUserRepository;
use App\Traits\ApiResponseTrait;

class UserRepository extends BaseRepository implements IUserRepository
{
    use ApiResponseTrait;
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
    public function getModel()
    {
        return User::class;
    }

    // public function findByEmail($email)
    // {

    //     $user = $this->model->where('email', $email)->first();
    //     if (!$user) {
    //         return $this->errorResponse("User not found.", 404);
    //     }
    //     return $user;
    // }
}
