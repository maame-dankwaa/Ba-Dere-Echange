<?php
/**
 * AuthHelper - Role-Based Access Control Helper
 * Handles authentication and authorization checks
 */
class AuthHelper
{
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user role
     */
    public static function getUserRole(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_role'] ?? 'guest';
    }

    /**
     * Get current user ID
     */
    public static function getUserId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /**
     * Check if user is a guest (not logged in)
     */
    public static function isGuest(): bool
    {
        return !self::isLoggedIn();
    }

    /**
     * Check if user is a customer
     */
    public static function isCustomer(): bool
    {
        return self::isLoggedIn() && self::getUserRole() === 'customer';
    }

    /**
     * Check if user is a vendor
     */
    public static function isVendor(): bool
    {
        return self::isLoggedIn() && self::getUserRole() === 'vendor';
    }

    /**
     * Check if user is an admin
     */
    public static function isAdmin(): bool
    {
        return self::isLoggedIn() && self::getUserRole() === 'admin';
    }

    /**
     * Check if user has at least customer privileges
     */
    public static function isCustomerOrAbove(): bool
    {
        return self::isCustomer() || self::isVendor() || self::isAdmin();
    }

    /**
     * Check if user has at least vendor privileges
     */
    public static function isVendorOrAbove(): bool
    {
        return self::isVendor() || self::isAdmin();
    }

    /**
     * Require login - redirect to login page if not logged in
     */
    public static function requireLogin(string $redirectTo = '../login/login.php'): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirectTo);
            exit();
        }
    }

    /**
     * Require specific role - redirect if user doesn't have the role
     */
    public static function requireRole(string $role, string $redirectTo = '../index.php'): void
    {
        self::requireLogin();

        $userRole = self::getUserRole();

        // Define role hierarchy: admin > vendor > customer
        $roleHierarchy = [
            'customer' => 1,
            'vendor' => 2,
            'admin' => 3
        ];

        $requiredLevel = $roleHierarchy[$role] ?? 0;
        $userLevel = $roleHierarchy[$userRole] ?? 0;

        if ($userLevel < $requiredLevel) {
            header('Location: ' . $redirectTo);
            exit();
        }
    }

    /**
     * Require customer role or above
     */
    public static function requireCustomer(string $redirectTo = '../login/login.php'): void
    {
        self::requireRole('customer', $redirectTo);
    }

    /**
     * Require vendor role or above
     */
    public static function requireVendor(string $redirectTo = '../index.php'): void
    {
        self::requireRole('vendor', $redirectTo);
    }

    /**
     * Require admin role
     */
    public static function requireAdmin(string $redirectTo = '../index.php'): void
    {
        if (!self::isAdmin()) {
            header('Location: ' . $redirectTo);
            exit();
        }
    }

    /**
     * Check if user can purchase books
     */
    public static function canPurchase(): bool
    {
        return self::isCustomerOrAbove();
    }

    /**
     * Check if user can add to wishlist
     */
    public static function canWishlist(): bool
    {
        return self::isCustomerOrAbove();
    }

    /**
     * Check if user can create listings
     */
    public static function canCreateListing(): bool
    {
        return self::isVendorOrAbove();
    }

    /**
     * Check if user can manage listings
     */
    public static function canManageListings(): bool
    {
        return self::isVendorOrAbove();
    }

    /**
     * Check if user can access admin features
     */
    public static function canAccessAdmin(): bool
    {
        return self::isAdmin();
    }

    /**
     * Get user display name
     */
    public static function getUsername(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['username'] ?? 'Guest';
    }
}
