<?php
/**
 * User Controller
 * Handles user account and profile operations
 */

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Transaction.php';

class UserController
{
    private User $user;
    private Transaction $transaction;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->user = new User();
        $this->transaction = new Transaction();
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../view/' . $view . '.php';
    }

    private function getFlash(): array
    {
        $flash = ['success' => $_SESSION['flash_success'] ?? null, 'error' => $_SESSION['flash_error'] ?? null];
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
        return $flash;
    }

    /**
     * Display user account/dashboard
     */
    public function account(): void
    {
        $this->requireAuth('/actions/user_account.php');

        $userId = $this->getUserId();

        $user = $this->user->find($userId);
        $purchases = $this->user->getPurchases($userId);
        $sales = $this->user->getSales($userId);
        $stats = $this->user->getStatistics($userId);
        $transactions = $this->transaction->getUserTransactions($userId);

        $this->render('user_account', [
            'user' => $user,
            'purchases' => $purchases,
            'sales' => $sales,
            'stats' => $stats,
            'transactions' => $transactions,
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Display user profile edit form
     */
    public function editProfile(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $user = $this->user->find($userId);

        $this->render('edit_profile', [
            'user' => $user,
            'flash' => $this->getFlash(),
            'errors' => $_SESSION['form_errors'] ?? [],
        ]);

        unset($_SESSION['form_errors']);
    }

    /**
     * Update user profile
     */
    public function updateProfile(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/actions/user_account.php');
            return;
        }

        $this->requireAuth();

        $userId = $this->getUserId();

        $data = [
            'full_name' => $this->getString('full_name', 'POST'),
            'phone' => $this->getString('phone', 'POST'),
            'location' => $this->getString('location', 'POST'),
            'bio' => $this->getString('bio', 'POST'),
        ];

        // Validate
        require_once __DIR__ . '/../services/Validator.php';
        $validator = Validator::make($data);

        $validator
            ->field('full_name', ['max:100'], 'Full name')
            ->field('phone', ['phone'], 'Phone number')
            ->field('location', ['max:255'], 'Location')
            ->field('bio', ['max:1000'], 'Bio');

        if ($validator->fails()) {
            $_SESSION['form_errors'] = $validator->errors();
            $this->redirect('/actions/edit_profile.php');
            return;
        }

        // Update profile
        try {
            $this->user->updateProfile($userId, $data);
            $this->flash('success', 'Profile updated successfully.');
        } catch (Exception $e) {
            $this->flash('error', 'Could not update profile.');
        }

        $this->redirect('/actions/user_account.php');
    }

    /**
     * View a specific transaction
     */
    public function viewTransaction(): void
    {
        $this->requireAuth();

        $transactionId = $this->getInt('id', 'GET', 0, 1);
        $userId = $this->getUserId();

        if ($transactionId <= 0) {
            $this->redirect('/actions/user_account.php');
            return;
        }

        // Get transaction with IDOR protection
        $transaction = $this->transaction->getTransactionDetails($transactionId, $userId);

        if (!$transaction) {
            $this->renderError('Transaction not found or access denied.', 404);
            return;
        }

        $this->render('transaction_detail', [
            'transaction' => $transaction,
        ]);
    }
}
