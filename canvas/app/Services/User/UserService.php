<?php
namespace App\Services\User;

use App\Repositories\User\IUserRepository;
use App\Services\User\IUserService;
use App\Services\BaseService;

class UserService extends BaseService implements IUserService
{
    protected $userRepository;

    public function __construct(IUserRepository $userRepository)
    {
        parent::__construct($userRepository);
        $this->userRepository = $userRepository;
    }
}
