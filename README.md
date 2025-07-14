# University of Ibadan Digital File Management System (DFMS)

A web portal for students, administrators, and lecturers to manage academic documents.

## Tech Stack

- **Backend:** PHP
- **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
- **Database:** MySQL

## Server Requirements

For the application to run correctly, the server must meet the following requirements:

- PHP 7.4 or higher
- MySQL 5.7 or higher
- A web server such as Apache or Nginx

### Image Quality Validation (Optional but Recommended)

To enable the automated rejection of blurry or unreadable documents, the following must be installed and configured.

#### For Linux (Debian/Ubuntu)

1.  **Install Dependencies:**
    ```bash
    sudo apt-get update
    sudo apt-get install php-imagick tesseract-ocr
    ```
2.  **Restart Web Server:**
    ```bash
    sudo systemctl restart apache2 # or php-fpm
    ```

#### For Windows (XAMPP/WAMP)

**1. Configure `php.ini`:**

-   Open your `php.ini` file (e.g., in `C:\xampp\php\php.ini`).
-   Enable the `fileinfo` and `imagick` extensions by uncommenting them:
    ```ini
    extension=fileinfo
    extension=imagick
    ```

**2. Install ImageMagick:**

-   **Important:** You must install the correct version of ImageMagick that matches your PHP environment's architecture (x64 vs x86) and thread safety (TS vs NTS). Find this in the first few lines of a `phpinfo()` page.
-   Download the corresponding ImageMagick "dll" binary from the [PECL website](https://mlocati.github.io/pecl-imagemagick-windows/).
-   Extract the downloaded zip file.
-   Copy all the `.dll` files from the `bin` subdirectory of the extracted files and paste them into your PHP's `ext` directory (e.g., `C:\xampp\php\ext`).
-   Copy all the other `.dll` files (from the root of the extracted files) and paste them into Apache's `bin` directory (e.g., `C:\xampp\apachein`). This makes them available to Apache.

**3. Install Tesseract OCR:**

-   Download and run the Tesseract installer from the [official UB-Mannheim repository](https://github.com/UB-Mannheim/tesseract/wiki).
-   During installation, ensure you select the option to **"Add Tesseract to the system PATH."** This allows PHP to find the `tesseract.exe` command.

**4. Restart Apache:**
s
-   After completing these steps, you **must restart your Apache server** from the XAMPP/WAMP control panel for the changes to take effect.

---

## Getting Started

1. **Clone the repository:**
   ```bash
   git clone <repository_url>
   ```
2. **Database Setup:**
   - Import the `dfms.sql` file into your MySQL database.
   - Configure the database connection in `app/config/config.php`.
3. **Install Dependencies:**
   - Follow the server requirement steps above.
4. **Web Server Configuration:**
   - Point the document root of your web server to the `/public` directory.

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
└── README.md
```
