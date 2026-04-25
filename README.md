# PANDA-TRUCK

Platform for DJs to manage music mixes, videos, banners, events, radio settings and download statistics. Includes the public site, admin dashboard, API endpoints and SQL structure for Panda Truck Reloaded.

## Requirements

- PHP 8.0+
- MariaDB/MySQL
- Apache with PHP enabled
- PDO MySQL extension

## Local Setup

1. Place the project inside your web server document root, for example `C:\xampp\htdocs\panda-truck-v2`.
2. Create a database named `panda_truck_v2`.
3. Import `panda_truck_v2.sql` or the latest production dump if you need current data.
4. Copy `includes/config.local.example.php` to `includes/config.local.php`.
5. Update the database credentials in `includes/config.local.php`.
6. Open `http://localhost/panda-truck-v2/`.

Production credentials should be configured through environment variables or `includes/config.local.php`; do not commit real passwords to the repository.

## Main Areas

- `index.php`, `mixes.php`, `albumes.php`, `superpacks.php`: public pages.
- `dashboard.php`: admin dashboard.
- `api/`: JSON endpoints and admin actions.
- `includes/`: shared configuration, authentication and helpers.
- `sql/` and `panda_truck_v2.sql`: database structure and seed data.
