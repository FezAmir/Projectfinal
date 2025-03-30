# EasyComp Database Setup Guide

To fix the database-related errors in your application, you need to properly set up your database tables with the correct structure.

## Option 1: Using the Setup Script

1. Open phpMyAdmin by navigating to http://localhost/phpmyadmin/
2. Create a new database called `easycomp` if it doesn't exist yet:
   - Click "New" in the left sidebar
   - Enter "easycomp" as the database name
   - Click "Create"

3. Select the `easycomp` database from the left sidebar
4. Click on the "SQL" tab
5. Copy the contents of the `setup_database.sql` file and paste it into the SQL query box
6. Click "Go" to execute the SQL script

## Option 2: Manual Commands via Terminal

1. Open your terminal and navigate to your XAMPP installation directory:
   ```
   cd /Applications/XAMPP/xamppfiles/bin
   ```

2. Log in to MySQL:
   ```
   ./mysql -u root
   ```

3. Create the database (if it doesn't exist):
   ```
   CREATE DATABASE IF NOT EXISTS easycomp;
   USE easycomp;
   ```

4. Execute the SQL script:
   ```
   source /Applications/XAMPP/xamppfiles/htdocs/WEBSITE/setup_database.sql
   ```

## What This Fixes

These database changes will fix the following errors:

1. **"Unknown column 'location' in 'field list'"** - The script uses `venue` instead of `location` for the competition venue field
2. **"Unknown column 'cp.registered_at' in 'field list'"** - The script uses `created_at` instead of `registered_at` for timestamp fields

## Performance Optimizations

The database setup includes several performance optimizations:

1. **Database Indices** - Key fields used in WHERE clauses and JOIN conditions have been indexed:
   - `organizer_id` on the competitions table
   - `category_id` on the competitions table 
   - `status` on both competitions and competition_participants tables
   - `date` fields (start_date, end_date) on the competitions table
   - `created_at` on the competition_participants table

2. **Analytics Optimization** - The analytics page has been optimized:
   - Queries have been improved with appropriate LIMIT clauses
   - INDEX hints have been added to certain queries
   - Charts now load asynchronously to prevent page freeze
   - A loading indicator shows progress while charts are being generated

These optimizations will significantly improve the loading times of the analytics dashboard and other pages that query the database.

## After Setup

After setting up the database, you should be able to:
1. Create competitions successfully
2. View analytics data without errors
3. Experience much faster page loading, especially on the analytics page

The application has been updated to use the correct field names that match the database structure.

## Default Admin Login

The setup script includes a default admin user:
- Username: admin
- Email: admin@easycomp.com  
- Password: admin123 (hashed in the database)

You can use these credentials to log in and start using the system. 