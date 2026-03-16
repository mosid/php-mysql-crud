<?php

require_once __DIR__ . '/autoload.php';

use App\Models\User;

// Enable error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Initialize user model
$user = new User();

// Handle request method
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';
$message = '';
$messageType = '';

// Process form submissions
if ($method === 'POST') {
    $action = $_POST['action'] ?? 'list';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        // Validation
        if (empty($name) || empty($email)) {
            $messageType = 'error';
            $message = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messageType = 'error';
            $message = 'Invalid email format.';
        } elseif ($user->emailExists($email)) {
            $messageType = 'error';
            $message = 'Email already exists.';
        } else {
            $id = $user->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ]);

            if ($id) {
                $messageType = 'success';
                $message = 'User created successfully!';
                $action = 'list';
            } else {
                $messageType = 'error';
                $message = 'Failed to create user.';
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($id <= 0 || empty($name) || empty($email)) {
            $messageType = 'error';
            $message = 'Invalid input data.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messageType = 'error';
            $message = 'Invalid email format.';
        } elseif ($user->emailExists($email, $id)) {
            $messageType = 'error';
            $message = 'Email already exists.';
        } else {
            if ($user->update($id, [
                'name' => $name,
                'email' => $email,
                'phone' => $phone
            ])) {
                $messageType = 'success';
                $message = 'User updated successfully!';
                $action = 'list';
            } else {
                $messageType = 'error';
                $message = 'Failed to update user.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            $messageType = 'error';
            $message = 'Invalid user ID.';
        } else {
            if ($user->delete($id)) {
                $messageType = 'success';
                $message = 'User deleted successfully!';
                $action = 'list';
            } else {
                $messageType = 'error';
                $message = 'Failed to delete user.';
            }
        }
    }
}

// Get data based on action
$data = [];
if ($action === 'list') {
    $page = (int)($_GET['page'] ?? 1);
    $itemsPerPage = 10;
    $offset = ($page - 1) * $itemsPerPage;

    $data['users'] = $user->getAll($itemsPerPage, $offset);
    $data['totalUsers'] = $user->getCount();
    $data['totalPages'] = ceil($data['totalUsers'] / $itemsPerPage);
    $data['currentPage'] = $page;
} elseif ($action === 'edit') {
    $id = (int)($_GET['id'] ?? 0);
    $data['user'] = $user->getById($id);
    if (!$data['user']) {
        $messageType = 'error';
        $message = 'User not found.';
        $action = 'list';
        $data['users'] = $user->getAll(10, 0);
        $data['totalUsers'] = $user->getCount();
        $data['totalPages'] = ceil($data['totalUsers'] / 10);
        $data['currentPage'] = 1;
    }
}

// Load view
include __DIR__ . '/views/layout.php';
