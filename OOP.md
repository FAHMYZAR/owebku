# Peta Belajar OOP Owebku

Dokumen ini berisi panduan alur dan letak penerapan 3 pilar utama OOP (Inheritance, Encapsulation, Abstraction) pada framework kustom PHP Owebku. 

Disusun untuk persiapan presentasi atau review konsep OOP.

---

## 1. Inheritance (Pewarisan)

**Konsep:** Class anak (child) mewarisi method dan properti dari class induk (parent) menggunakan keyword `extends`.

**Lokasi di Kode:**
- Induk: `src/core/Controller.php`
- Anak: `src/modules/projects/ProjectsController.php`

**Bukti Kode:**
```php
class ProjectsController extends Controller
{
    public function rename()
    {
        // $this->json() adalah method milik class Controller, 
        // tapi bisa dipanggil di sini karena mewarisi (extends).
        $this->json(['success' => false], 400); 
    }
}
```

**Penjelasan Singkat:**
"Saya memakai Inheritance agar method umum seperti `json()` dan `render()` tidak perlu ditulis ulang di setiap controller. Cukup buat di `Controller` utama, lalu controller fitur tinggal melakukan `extends Controller`."

---

## 2. Encapsulation (Pengkapsulan)

**Konsep:** Membatasi akses data/properti dari luar class menggunakan visibility `private` atau `protected`, agar data tidak bisa dimanipulasi sembarangan oleh class lain.

**Lokasi di Kode:**
- `src/modules/projects/ProjectsController.php`

**Bukti Kode:**
```php
class ProjectsController extends Controller
{
    private Project $projects; // <-- ENCAPSULATION

    public function __construct()
    {
        $this->projects = new Project();
    }
}
```

**Penjelasan Singkat:**
"Saya memakai `private` pada properti `$projects` untuk melindungi object model dari intervensi class PHP lain. Jika diubah menjadi `public`, ada risiko class lain secara tidak sengaja menimpa object tersebut yang dapat membuat aplikasi error/crash saat method controller dijalankan."

---

## 3. Abstraction (Abstraksi)

**Konsep:** Menyembunyikan detail kerumitan proses (seperti sintaks query SQL) dan hanya memperlihatkan method/interface yang mudah digunakan.

**Lokasi di Kode:**
- Dipanggil di: `src/modules/projects/ProjectsController.php`
- Dikerjakan di: `src/core/Model.php`

**Bukti Kode:**

*Di Controller (Simpel & Bersih):*
```php
$this->projects->update($id, ['project_name' => $name]);
```

*Di Model (Rumit & Tersembunyi):*
```php
public function update(int $id, array $data): bool
{
    // ... proses looping data untuk membuat SET ...
    $sql = "UPDATE {$this->table} SET {$fields} WHERE {$this->primaryKey} = ?";
    $stmt = $this->query($sql, $values);
    return $stmt->rowCount() > 0;
}
```

**Penjelasan Singkat:**
"Abstraction saya terapkan pada interaksi antara Controller dan Model. Controller cukup memanggil `$this->projects->update(...)` tanpa perlu tahu cara menyusun query SQL `UPDATE`. Detail teknis SQL-nya disembunyikan (di-abstraksi) ke dalam method milik class `Model`."

---

## 4. Pertanyaan Dosen yang Sering Muncul

### Q: "Kamu pakai Model PDO, lalu dari mana asal koneksi `getConnection()`?"
**A:** "Fungsi `getConnection()` berasal dari class `Database` (`src/core/Database.php`). Method tersebut bersifat static, jadi Model memanggilnya dengan `Database::getConnection()`. Method ini akan membaca file konfigurasi `src/config/database.php` (yang hanya me-return Array), merakitnya menjadi string DSN, lalu melakukan instance `new PDO()`."

### Q: "Kenapa method `json()` di Controller pakai `protected`, bukan `private` atau `public`?"
**A:** "Kalau `private`, class anak (`ProjectsController`) tidak akan bisa memakainya. Kalau `public`, method internal ini bisa dipanggil sembarangan dari luar (route/objek lain). Dengan `protected`, method ini eksklusif hanya bisa dipakai oleh class anak turunannya saja."

### Q: "Di mana kamu me-require/include file `Project.php`? Kok bisa langsung `new Project()`?"
**A:** "Saya menggunakan sistem **Autoloader** (`spl_autoload_register`) di `index.php`. Berbekal **Namespace** (seperti `Modules\Projects`), autoloader akan bekerja di balik layar mencari file `src/modules/projects/Project.php` dan me-require nya secara otomatis saat mendeteksi sintaks `new Project()`."

### Q: "Kenapa pilih PDO daripada mysqli?"
**A:** 
1. Mendukung *Prepared Statements* yang paling aman melawan SQL Injection (parameter dipisah dari string SQL menggunakan `?`).
2. Fleksibel, jika suatu saat ganti database (misal ke PostgreSQL), kodenya tidak perlu dirombak ulang.
3. Penanganan error (Exception) yang lebih baik dan rapi saat proses debugging.

---

## 5. Alur Data Lengkap (Contoh: Menampilkan Data Project)

1. **Browser** request URL `/projects/5`.
2. **`Router`** (`index.php`) menerjemahkan URL dan memanggil `ProjectsController->show()`.
3. **`ProjectsController`** mengecek keamanan (Auth & CSRF), lalu memanggil `$this->projects->find(5)` **(Encapsulation)**.
4. Method `find()` dijalankan. Method ini tidak ada di `Project.php`, melainkan diwarisi dari `Model.php` **(Inheritance)**.
5. Di dalam `find()`, query PDO disiapkan (SQL detail disembunyikan dari controller -> **Abstraction**).
6. Model meminta koneksi via `Database::getConnection()`.
7. Data MySQL dikembalikan menjadi array ke Controller.
8. Controller memanggil `$this->json(data)` yang secara otomatis menambahkan header, response code, menyisipkan `csrf_token` baru, lalu mereturn data ke Browser.
