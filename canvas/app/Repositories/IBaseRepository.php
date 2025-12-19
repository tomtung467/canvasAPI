<?php

namespace App\Repositories;

interface IBaseRepository
{
    public function all();

    public function find($id);

    public function create(array $attributes);

    public function update($id, array $attributes);

    public function delete($id);
}
