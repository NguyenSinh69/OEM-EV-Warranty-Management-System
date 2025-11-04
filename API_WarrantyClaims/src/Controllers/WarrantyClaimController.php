<?php
require_once __DIR__ . '/../Models/WarrantyClaim.php';

class WarrantyClaimController {
    private $model;

    public function __construct() {
        $this->model = new WarrantyClaim();
    }

    public function createClaim($data) {
        return $this->model->create($data);
    }

    public function getAllClaims() {
        return $this->model->getAll();
    }

    public function updateStatus($id, $status) {
        return $this->model->updateStatus($id, $status);
    }

    public function uploadAttachment($id, $file) {
        return $this->model->addAttachment($id, $file);
    }

    public function getCosts($id) {
        return $this->model->getCosts($id);
    }

    public function approveClaim($id) {
        return $this->model->approve($id);
    }
}
