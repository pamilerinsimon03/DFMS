# University of Ibadan Digital File Management System (DFMS)

A web portal for students, administrators, and lecturers to manage academic documents.

## Tech Stack

* **Backend:** PHP
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
* **Database:** MySQL

## Server Requirements

For the application to run correctly, the server must meet the following requirements:

* PHP 8.0 or higher
* MySQL 5.7 or higher
* GD extension (bundled with PHP) for blur detection
* A web server such as Apache or Nginx

## Image Quality & OCR Validation

These features automate rejection of blurry or unreadable uploads. Install and configure the following:

### Linux (Debian/Ubuntu)

1. **Install Dependencies**:

   ```bash
   sudo apt-get update
   sudo apt-get install php-gd php-imagick tesseract-ocr ghostscript
   ```
2. **Restart Services**:

   ```bash
   sudo systemctl restart apache2  # or nginx + php-fpm
   ```

### Windows (XAMPP/WAMP)

1. **Enable PHP Extensions** in `php.ini`:

   ```ini
   extension=gd
   extension=imagick
   extension=fileinfo
   ```

2. **Install ImageMagick**:

   * Download matching ImageMagick **DLL** binary (x64/TS or NTS)
   * Extract ZIP, copy `php_imagick.dll` to `C:\xampp\php\ext`.
   * Copy supporting `CORE_RL_*.dll` to Apache’s `bin` folder (e.g. `C:\xampp\apache\bin`).

3. **Install Ghostscript**:

   * Download and install from [https://www.ghostscript.com/download/gsdnld.html](https://www.ghostscript.com/download/gsdnld.html)
   * Add `C:\Program Files\gs\gs<version>\bin` to **System PATH**.
   * Create a `gs.bat` in that folder with:

     ```bat
     @echo off
     "C:\Program Files\gs\gs<version>\bin\gswin64c.exe" %*
     ```

4. **Install Tesseract OCR**:

   * Download from [https://github.com/UB-Mannheim/tesseract/wiki](https://github.com/UB-Mannheim/tesseract/wiki)
   * Ensure **"Add to PATH"** is checked.

5. **Restart Apache/WAMP** from control panel.

---

## Getting Started

1. **Clone the repository:**

   ```bash
   git clone <repository_url>
   cd DFMS
   ```
2. **Database Setup:**

   * Import the `dfms.sql` file into MySQL database.
   * Configure the database connection in `app/config/config.php`.
3. **Install Dependencies:**

   * Follow the steps under **Image Quality & OCR Validation** if you need automated checks.
4. **Web Server Configuration:**

   * Point your web server’s document root to the `/public` directory.

## Project Structure

```
DFMS/
├── app/
│   ├── config/      # Database and app configuration
│   ├── controllers/ # Handles user requests and business logic
│   ├── models/      # Database interaction and data models
│   ├── views/       # PHP templates for the UI
│   └── src/         # CSS, JS, and image assets
├── public/
│   ├── index.php    # Front controller
│   └── uploads/     # Directory for user-uploaded files
├── dfms.sql         # Database schema
└── README.md        # This file
```

## Login Credentials

Default Password for all users: test101

**Note:** The system now supports automatic password generation based on student surnames. New students will have passwords in the format: `surname12345` (e.g., "John Doe" → password: "doe12345").

## Admin Features

### Student Management
- **Add Individual Students**: Navigate to "Add Student" in the admin panel to add students one by one
- **Bulk Import Students**: Use "Bulk Import" to upload a CSV file with multiple students
- **Update Passwords**: Use "Update Passwords" to change all existing student passwords to the new format

### CSV Import Format
The bulk import feature accepts CSV files with the following columns:
- `matric_number` (required)
- `full_name` (required) 
- `level` (required)
- `session` (required)
- `programme` (required)
- `department_id` (required)
- `program_type` (optional, default: Undergraduate)
- `mode` (optional, default: Regular)
- `email` (optional)

Download the CSV template from the bulk import page for the correct format.

Admin Logins:

Psychology Admin: ADMIN_PSYC
English Admin: ADMIN_ENG
Sociology Admin: ADMIN_SOC
Linguistics Admin: ADMIN_LING
Super Admin: SUPER_ADMIN
Student Logins:

Computer Science: U123001, U123002
Psychology: U123003, U123004
English: U123005, U123006
Sociology: U123007, U123008
Linguistics: U123009, U123010