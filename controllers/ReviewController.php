<?php

require_once __DIR__ . '/../classes/Review.php';
require_once __DIR__ . '/../services/Validator.php';

class ReviewController
{
    protected $review;

    public function __construct()
    {
        $this->review = new Review();
    }

    /**
     * Submit a new review
     */
    public function submit(): void
    {
        if (!$this->isPost()) {
            $this->redirect('../index.php');
            return;
        }

        $this->requireAuth('../login/login.php');

        $bookId = $this->getInt('book_id', 'POST', 0);
        $rating = $this->getInt('rating', 'POST', 0);
        $comment = $this->getString('comment', 'POST');
        $userId = $this->getUserId();

        if ($bookId <= 0) {
            $this->flash('error', 'Invalid book.');
            $this->redirect('../index.php');
            return;
        }

        // Validate input
        $validator = Validator::make([
            'rating' => $rating,
            'comment' => $comment,
        ]);

        $validator
            ->field('rating', ['required', 'integer', 'min:1', 'max:5'], 'Rating');

        if ($validator->fails()) {
            $this->flash('error', 'Please provide a valid rating (1-5 stars).');
            $this->redirect('../actions/single_book.php?id=' . $bookId);
            return;
        }

        try {
            // Create the review
            $reviewId = $this->review->createReview([
                'book_id' => $bookId,
                'reviewer_id' => $userId,
                'rating' => $rating,
                'comment' => $comment,
            ]);

            if ($reviewId) {
                $this->flash('success', 'Thank you for your review!');
            } else {
                $this->flash('error', 'Could not submit review. Please try again.');
            }

        } catch (Exception $e) {
            $this->flash('error', 'An error occurred. Please try again.');
        }

        $this->redirect('../actions/single_book.php?id=' . $bookId);
    }

    // Helper methods

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function requireAuth(string $redirectTo = '../login/login.php'): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . $redirectTo);
            exit;
        }
    }

    private function getUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    private function getInt(string $key, string $method = 'GET', int $default = 0, ?int $min = null, ?int $max = null): int
    {
        $value = $method === 'POST' ? ($_POST[$key] ?? $default) : ($_GET[$key] ?? $default);
        $value = (int)$value;

        if ($min !== null && $value < $min) {
            $value = $min;
        }
        if ($max !== null && $value > $max) {
            $value = $max;
        }

        return $value;
    }

    private function getString(string $key, string $method = 'GET', string $default = ''): string
    {
        $value = $method === 'POST' ? ($_POST[$key] ?? $default) : ($_GET[$key] ?? $default);
        return trim((string)$value);
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash_' . $type] = $message;
    }

    private function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
