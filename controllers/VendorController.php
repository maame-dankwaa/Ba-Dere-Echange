<?php
// controllers/VendorController.php

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Vendor.php';

class VendorController
{
    private $user;
    private $book;
    private $vendor;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->user   = new User();
        $this->book   = new Book();
        $this->vendor = new Vendor();
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../view/' . $view . '.php';
    }

    private function redirect(string $path): void
    {
        header("Location: {$path}");
        exit;
    }

    // Vendor listing page
    public function index(): void
    {
        $vendors = $this->user->getUsersByRole('vendor');

        $this->render('vendor_list', [
            'vendors' => $vendors,
        ]);
    }

    // Single vendor + their books
    public function show(): void
    {
        $vendorId = (int)($_GET['id'] ?? 0);
        if ($vendorId <= 0) {
            $this->redirect('/index.php');
            return;
        }

        // Uses BaseModel::find() from User/Vendor
        $vendorData = $this->user->find($vendorId);

        $filters = ['seller_id' => $vendorId];
        $books   = $this->book->getBooksWithFilters($filters, 1, 50);

        $this->render('book_listing', [
            'vendor' => $vendorData,
            'books'  => $books,
        ]);
    }
}
