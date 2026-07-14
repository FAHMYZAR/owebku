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
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            $_SESSION['flash_error'] = 'Alamat email tidak valid.';
            redirect_to('register');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9_-]{2,31}$/', $username)) {
            $_SESSION['flash_error'] = 'Username harus 3-32 karakter dan hanya boleh berisi huruf kecil, angka, garis bawah, atau tanda hubung.';
            redirect_to('register');
        }
        if (strlen($password) < 10 || strlen($password) > 4096) {
            $_SESSION['flash_error'] = 'Password minimal 10 karakter.';
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
            'password' => $password,
            'full_name' => $username,
            'role' => 'user',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        session_regenerate_id(true);
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
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

        $username = strtolower(trim($_POST['username'] ?? ''));
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

        session_regenerate_id(true);
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
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
        require_auth();
        verify_csrf();

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        redirect_to('login');
    }
}
