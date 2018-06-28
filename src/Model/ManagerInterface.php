<?php

namespace Model;

interface ManagerInterface
{
    public function getById($id);
    public function create($id, array $data);
    public function update($id, array $data);
    public function delete($id, array $data);
    public function validateOnCreate(array $data, $id);
    public function validateOnUpdate(array $data, $id);
    public function validateOnDelete(array $data, $id);
}