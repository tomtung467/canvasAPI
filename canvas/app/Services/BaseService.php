<?php
namespace App\Services;
use App\Traits\ApiResponseTrait;

abstract class BaseService
{
    use ApiResponseTrait;
    protected $repository;
    public function __construct($repository)
    {
        $this->repository = $repository;
    }
    public function getAll()
    {
        $data = $this->repository->all();
        return $this->successResponse($data, "Data retrieved successfully.");
    }
    public function getById($id)
    {
        $data = $this->repository->find($id);
        if (!$data) {
            return $this->errorResponse("Resource not found.", 404);
        }
        return $this->successResponse($data, "Data retrieved successfully.");
    }
    public function create(array $attributes)
    {
        $data = $this->repository->create($attributes);
        return $this->successResponse($data, "Resource created successfully.", 201);
    }
    public function update($id, array $attributes)
    {
        $updated = $this->repository->update($id, $attributes);
        if (!$updated) {
            return $this->errorResponse("Resource not found.", 404);
        }
        return $this->successResponse($updated, "Resource updated successfully.");

    }
    public function delete($id)
    {
        $deleted = $this->repository->delete($id);
        if (!$deleted) {
            return $this->errorResponse("Resource not found.", 404);
        }
        return $this->successResponse(null, "Resource deleted successfully.");
    }
}
