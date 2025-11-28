<?php


require_once __DIR__ . '/../config/Config.php';
require_once __DIR__ . '/../classes/Book.php';
require_once __DIR__ . '/../classes/Category.php';

class HomeController
{
    private $book;
    private $category;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->book     = new Book();
        $this->category = new Category();
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require __DIR__ . '/../view/' . $view . '.php';
    }

    public function index(): array
    {
        $featured   = $this->book->getFeaturedBooks(6);
        $trending   = $this->book->getTrendingBooks(7, 8);
        $recent     = $this->book->getRecentBooks(12);
        $categories = $this->category->getPopularCategories(8);
        $totalBooks = $this->book->getTotalActiveBooks();

        return [
            'featuredBooks' => $featured,
            'trendingBooks' => $trending,
            'recentBooks'   => $recent,
            'categories'    => $categories,
            'totalBooks'    => $totalBooks,
        ];
    }
}
