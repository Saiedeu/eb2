📘 Project Documentation: Exchange-Beidge

🧾 Table of Contents

📌 Project Overview
✅ Features
📁 Directory Structure
⚙️ Installation Guide
🔐 Admin Panel Overview
🔌 API Endpoints
🛡️ Security Features
🔑 License System
🛠️ Customization Guide
❓ FAQ & Support
📌 Project Overview

Exchange-Beidge is a PHP-based currency exchange platform that enables users to exchange currencies online, track transactions, read blog articles, and submit reviews. It includes a license verification system, SEO tools, a CMS for static pages, and a secure admin dashboard for managing exchanges, users, content, and more.

✅ Features

🌐 Currency exchange system with dynamic rates
🔎 Transaction tracking via unique tracking IDs
🛠️ Admin dashboard with full control
📰 Blog system with post management
📄 CMS for About, Contact, FAQ, Terms, Privacy
💬 Popup and scrolling notices
🔍 SEO control (global and per-page)
🧾 License activation and protection
📱 Responsive frontend and admin interface
🧩 Modular API access for real-time data
📁 Directory Structure

A simplified structure of the core folders and files:

graphql
Copy
Edit
Exchange-Beidge/
├── index.php
├── about.php, contact.php, faq.php, etc.
├── admin/                 # Admin dashboard and tools
│   ├── includes/          # Admin-specific includes (header, SEO, etc.)
│   ├── blog/, rates/, currencies/, etc.
├── api/                   # API endpoints
├── install/               # Web-based installer
├── includes/              # Core PHP functions
├── templates/             # Shared frontend components
├── assets/                # CSS, JS, media uploads
├── config/                # Configuration files (db, license)
├── errors/                # Custom HTTP error pages
⚙️ Installation Guide

⚠️ The installation process is browser-based and works just like WordPress.
✅ A. System Requirements
Make sure your server supports:

PHP 7.2 or higher
MySQL 5.6+ / MariaDB
Required PHP extensions:
PDO, mbstring, curl, fileinfo, openssl, json
Writable directories:

/assets/uploads/
/config/
📤 B. Upload Files
Extract the full script archive to your computer.
Inside, locate the file License_Key.txt. It contains your 22-digit license key.
Upload all files to your server (e.g., public_html/ or a subdomain folder) using FTP or cPanel.
🌐 C. Run the Installer
In your browser, go to:
arduino
Copy
Edit
http://yourdomain.com/install
The installer will guide you through the following steps:
✔️ System Check (PHP version, extensions, writable paths)
🔑 Enter License Key (from License_Key.txt)
🛠️ Database Setup:
DB Host
DB Name
DB Username
DB Password
🌍 Site Configuration:
Site Name
Admin Username
Admin Email
Admin Password
Complete the installation.
🧹 D. Post-Installation Steps
Delete the /install/ folder manually for security:
bash
Copy
Edit
/install/
(Optional but recommended) Import the pre-made database:
File: database/database.sql
Import it via phpMyAdmin or CLI.
Delete the default user and create your own admin account in the dashboard.
🔐 Admin Panel Overview

Access the admin panel at:

arduino
Copy
Edit
http://yourdomain.com/admin/
Admin Modules:
Module	Description
Blog	Manage blog posts
Currencies	Add/edit/delete exchangeable currencies
Rates	Set and update exchange rates
Reserves	Set reserves for each currency
Pages	CMS for static content (About, FAQ, etc.)
Notices	Popup and scrolling notifications
Testimonials	Display user reviews/testimonials
SEO Settings	Page-level and global SEO metadata
Site Users	Manage admin credentials
Receipts	View and track exchange receipts
Floating Buttons	Add helpful UI buttons
Settings	Global system configuration

🔌 API Endpoints

Located in /api/, these endpoints allow integration and automation.

Endpoint	Method	Description
/api/exchange.php	POST	Submit an exchange request
/api/track.php	GET	Track transaction via ID
/api/get_rates.php	GET	Get current exchange rates
/api/get_post.php	GET	Fetch blog posts
/api/get_server_time.php	GET	Sync time between client/server
/api/generate_id.php	GET	Generate unique transaction ID

🛡️ Security Features

✅ License Verification (via /includes/license_protection.php)
✅ Middleware Security (security_middleware.php)
✅ Error Handling (/errors/)
✅ Maintenance Mode Support
✅ Session-based admin authentication
✅ File-based install lock (install.lock)
🔑 License System

The script includes a built-in licensing system to prevent unauthorized use.

License Key: Found in License_Key.txt (22 characters)
License Activation: Handled during installation
Protection Files:
/includes/license_check.php
/includes/license_protection.php
/config/license.php
Remote API: Validates the license key
If the license is invalid, access to the frontend and admin is restricted.

🛠️ Customization Guide

Want to change the look or functionality? Here’s where to go:

Customization Target	File or Directory
Frontend layout	/templates/header.php, footer.php
Exchange form	/templates/exchange-form.php
Admin layout	/admin/includes/
Blog logic	/includes/blog_functions.php
Add/remove currencies	/admin/currencies/
Change rates/reserves	/admin/rates/, /admin/reserves/
SEO Tags & Metadata	/admin/global-seo/, seo-functions.php
Transaction flow	/api/exchange.php, functions.php
License logic	/includes/license_check.php

❓ FAQ & Support

Q: I get a "License invalid" error.
A: Make sure:

You entered the correct license key (22 digits).
Your server allows outbound connections (for license API).
You haven’t already used the key on another domain (unless multi-use).
Q: How do I reset the admin password?
A: Use /admin/site-user/reset_password.php or update it directly in the database.

Q: Can I disable the blog or static pages?
A: Yes, simply remove or comment out routes like blog.php and hide them in the menu.

Q: How to back up my data?
A:

Export your MySQL database via phpMyAdmin or command line.
Zip your full project files and download them.











Got it! Here's a **Support & Contact** section you can add to your documentation to provide users with your support details clearly:

---

## 📞 Need Support?

If you need assistance with **Exchange-Beidge** or have any questions, feel free to reach out:

| Contact Detail | Information                                                |
| -------------- | ---------------------------------------------------------- |
| **Author**     | Saieed Rahman                                              |
| **Company**    | SidMan Solution                                            |
| **Website**    | [https://saieed-rahman.rg.gd](https://saieed-rahman.rg.gd) |
| **WhatsApp**   | +974-66489944                                              |
| **E-mail**     | [info.saidur\_bd@aol.com](mailto:info.saidur_bd@aol.com)   |
| **Facebook**   | [SrMassum](https://facebook.com/SrMassum)                  |




