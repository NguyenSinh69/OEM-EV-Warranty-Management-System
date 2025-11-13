<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Customer;
use App\Models\Vehicle;

class CustomerController extends BaseController
{
    private Customer $customerModel;
    private Vehicle $vehicleModel;

    public function __construct()
    {
        $this->customerModel = new Customer();
        $this->vehicleModel = new Vehicle();
    }

    public function index(Request $request, Response $response): void
    {
        try {
            $page = (int)($request->getQuery('page') ?? 1);
            $perPage = (int)($request->getQuery('per_page') ?? 10);
            
            $result = $this->customerModel->paginate($page, $perPage);
            
            $response->paginated(
                $result['data'],
                $result['total'],
                $result['page'],
                $result['per_page']
            );
        } catch (\Exception $e) {
            $response->error('Failed to fetch customers: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $customer = $this->customerModel->getCustomerWithUser($id);
            
            if (!$customer) {
                $response->error('Customer not found', 404);
                return;
            }
            
            $response->success($customer);
        } catch (\Exception $e) {
            $response->error('Failed to fetch customer: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request, Response $response): void
    {
        try {
            $data = $this->sanitizeInput($request->getBody());
            
            // Validate required fields
            $required = ['company_name', 'address', 'city'];
            $errors = $this->validateRequired($data, $required);
            
            if (!empty($errors)) {
                $response->error('Validation failed', 422, $errors);
                return;
            }
            
            // Generate customer code if not provided
            if (!isset($data['customer_code'])) {
                $data['customer_code'] = $this->customerModel->generateCustomerCode();
            }
            
            $customerId = $this->customerModel->create($data);
            
            $this->logActivity('create', 'customer', (int)$customerId, [
                'customer_code' => $data['customer_code'],
                'company_name' => $data['company_name']
            ]);
            
            $response->success([
                'id' => $customerId,
                'customer_code' => $data['customer_code']
            ], 'Customer created successfully');
            
        } catch (\Exception $e) {
            $response->error('Failed to create customer: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, Response $response): void
    {
        try {
            $id = (int)$request->getParam('id');
            $data = $this->sanitizeInput($request->getBody());
            
            $customer = $this->customerModel->find($id);
            if (!$customer) {
                $response->error('Customer not found', 404);
                return;
            }
            
            $updated = $this->customerModel->update($id, $data);
            
            if ($updated) {
                $this->logActivity('update', 'customer', $id, $data);
                $response->success(['updated' => true], 'Customer updated successfully');
            } else {
                $response->error('Failed to update customer', 500);
            }
            
        } catch (\Exception $e) {
            $response->error('Failed to update customer: ' . $e->getMessage(), 500);
        }
    }

    public function getVehicles(Request $request, Response $response): void
    {
        try {
            $customerId = (int)$request->getParam('id');
            
            $customer = $this->customerModel->find($customerId);
            if (!$customer) {
                $response->error('Customer not found', 404);
                return;
            }
            
            $vehicles = $this->customerModel->getCustomerVehicles($customerId);
            
            $response->success($vehicles);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch customer vehicles: ' . $e->getMessage(), 500);
        }
    }

    public function getClaims(Request $request, Response $response): void
    {
        try {
            $customerId = (int)$request->getParam('id');
            
            $customer = $this->customerModel->find($customerId);
            if (!$customer) {
                $response->error('Customer not found', 404);
                return;
            }
            
            $claims = $this->customerModel->getCustomerClaims($customerId);
            
            $response->success($claims);
            
        } catch (\Exception $e) {
            $response->error('Failed to fetch customer claims: ' . $e->getMessage(), 500);
        }
    }
}