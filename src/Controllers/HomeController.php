<?php

require_once __DIR__ . '/../Models/Image.php';

class HomeController
{
    private Image $image;

    public function __construct()
    {
        $this->image = new Image();
    }

    /**
     * Show the homepage
     * - Logged in: Show user's own images
     * - Logged out: Show welcome/login prompt
     */
    public function index(): void
    {
        $userImages = [];

        if (isset($_SESSION['user_id'])) {
            $userImages = $this->image->getByUserId($_SESSION['user_id'], 100); // Get all user images
        }

        $data = [
            'userImages' => $userImages,
            'isLoggedIn' => isset($_SESSION['user_id']),
        ];

        require __DIR__ . '/../Views/home.php';
    }
}
