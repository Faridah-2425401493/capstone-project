# Cloud-Based Hostel Management and Booking System

A CSBC 252 capstone-ready PHP + MySQL application.

## Features
- Student, hostel administrator and system administrator roles
- Registration, login, logout and role authorization
- Hostel CRUD
- Room CRUD and availability
- Gender-aware room filtering
- Booking creation, approval and rejection
- Amazon S3-compatible upload abstraction
- MySQL / Amazon RDS schema
- Environment-variable configuration
- File logging suitable for CloudWatch Agent

## Local setup
1. Create a MySQL database and import `database/schema.sql`.
2. Copy `.env.example` to `.env` and edit it.
3. Configure Apache/Nginx so `public/` is the document root, or use:
   `php -S localhost:8000 -t public`
4. Open `http://localhost:8000`.

## AWS deployment
- Launch Ubuntu EC2 and install Apache/Nginx + PHP 8.2 + required extensions.
- Put RDS endpoint and credentials in environment variables.
- Attach a least-privilege IAM role to EC2 for S3 uploads.
- Configure the Security Group to allow HTTP/HTTPS and restrict SSH.
- Configure CloudWatch Agent to ship `storage/logs/app.log`.
- If using uploaded files, set `STORAGE_DRIVER=s3` and provide bucket/region variables.

## Default admin
After importing the database, register normally, then promote a user:
`UPDATE users SET role='system_admin' WHERE email='your@email.com';`
