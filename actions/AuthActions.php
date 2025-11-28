public function register()
{
    if ($this->isPost()) {
        $data = [
            'full_name' => trim($_POST['full_name']),
            'username'  => trim($_POST['username']),
            'email'     => trim($_POST['email']),
            'password'  => $_POST['password'],
            'phone'     => $_POST['phone'],
            'location'  => $_POST['location'],
            'user_role' => 'customer'
        ];

        $id = $this->userModel->createUser($data);

        $_SESSION['user_id'] = $id;
        $_SESSION['user_role'] = 'customer';
        $_SESSION['logged_in'] = true;

        return $this->redirect('/');
    }

    $this->render('login/register');
}
