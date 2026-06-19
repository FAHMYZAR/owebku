<?php
namespace Modules\Auth;

use Core\Controller;

class AuthController extends Controller
{
    private User $users;

    /**
     * Dependency lewat constructor = jelas OOP encapsulation
     */
    public function __construct()
    {
        $this->users = new User();
    }

    /**
     * Halaman login
     */
    public function index(): void
    {
        if (is_authenticated()) {
            redirect_to('dashboard');
        }

        $this->render('auth.login', [
            'page_title' => 'Login',
            'layout_variant' => 'auth',
        ]);
    }

    /**
     * Halaman register
     */
    public function register(): void
    {
        if (is_authenticated()) {
            redirect_to('dashboard');
        }

        $this->render('auth.register', [
            'page_title' => 'Register',
            'layout_variant' => 'auth',
        ]);
    }

    /**
     * Proses register
     */
    public function doRegister(): void
    {
        verify_csrf();

        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $username === '' || $password === '') {
            $_SESSION['flash_error'] = 'Semua field wajib diisi.';
            redirect_to('register');
        }

        if ($this->users->findByUsername($username)) {
            $_SESSION['flash_error'] = 'Username sudah digunakan.';
            redirect_to('register');
        }

        if ($this->users->findByEmail($email)) {
            $_SESSION['flash_error'] = 'Email sudah terdaftar.';
            redirect_to('register');
        }

        $userId = $this->users->createUser([
            'email' => $email,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'full_name' => $username,
            'role' => 'user',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['auth_user'] = [
            'id_user' => (int) $userId,
            'username' => $username,
            'email' => $email,
            'role' => 'user',
        ];

        $_SESSION['flash_success'] = 'Akun berhasil dibuat.';
        redirect_to('dashboard');
    }

    /**
     * Proses login
     */
    public function login(): void
    {
        verify_csrf();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $_SESSION['flash_error'] = 'Username dan password wajib diisi.';
            redirect_to('login');
        }

        $user = $this->users->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['flash_error'] = 'Username atau password salah.';
            redirect_to('login');
        }

        if ((int) $user['is_active'] !== 1) {
            $_SESSION['flash_error'] = 'Akun tidak aktif.';
            redirect_to('login');
        }

        $_SESSION['auth_user'] = [
            'id_user' => (int) $user['id_user'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        $_SESSION['flash_success'] = 'Login berhasil.';
        redirect_to('dashboard');
    }

    /**
     * Halaman profile
     */
    public function profile(): void
    {
        require_auth();

        $this->render('auth.profile', [
            'page_title' => 'Profile',
            'layout_variant' => 'app',
        ]);
    }

    /**
     * Update password profile
     */
    public function updatePassword(): void
    {
        require_auth();
        verify_csrf();

        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if ($oldPassword === '' || $newPassword === '' || strlen($newPassword) < 6) {
            $_SESSION['flash_error'] = 'Password tidak valid.';
            redirect_to('profile');
        }

        $user = $this->users->find(auth_user()['id_user']);
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            $_SESSION['flash_error'] = 'Password lama salah.';
            redirect_to('profile');
        }

        $this->users->update(auth_user()['id_user'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['flash_success'] = 'Password berhasil diperbarui.';
        redirect_to('profile');
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        unset($_SESSION['auth_user']);
        $_SESSION['flash_success'] = 'Logout berhasil.';
        redirect_to('login');
    }
}
