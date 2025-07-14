# Task Manager System

A comprehensive task management system built with PHP and MySQL, featuring user management, task assignment, and status tracking.

## Features

### Admin Features
- **User Management**: Add, view, and delete users
- **Task Management**: Create, assign, update, and delete tasks
- **Task Assignment**: Assign tasks to specific users with deadlines
- **Status Tracking**: Monitor task progress (Pending, In Progress, Completed)
- **Deadline Management**: Set and track task deadlines with overdue indicators

### User Features
- **Task Dashboard**: View assigned tasks
- **Status Updates**: Update task status from user dashboard
- **Task Details**: View full task descriptions and deadlines

### System Features
- **Authentication**: Secure login system with role-based access
- **Setup Process**: First-time setup to create admin account
- **Database Integration**: MySQL database with proper relationships
- **Responsive Design**: Clean, modern interface

## Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher (or MariaDB)  
- **Web Server**: Apache/Nginx
- **XAMPP**: Recommended for local development
- **Email Server**: SMTP server or MailHog for development

## Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd task_manager
```

### 2. Set up XAMPP
- Install XAMPP and start Apache and MySQL services
- Make sure Apache is running on port 80 and MySQL on port 3306/3307

### 3. Database Setup
1. Open phpMyAdmin (`http://localhost/phpmyadmin`)
2. Run the SQL commands from `database_setup.sql`:

```sql
CREATE DATABASE IF NOT EXISTS task_manager;
USE task_manager;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tasks table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    assigned_to INT NOT NULL,
    created_by INT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Completed') DEFAULT 'Pending',
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### 4. Environment Configuration
1. **Copy the environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env` with your settings:**
   ```env
   # Database Configuration
   DB_HOST=localhost
   DB_PORT=3307
   DB_NAME=task_manager
   DB_USERNAME=root
   DB_PASSWORD=your-mysql-password

   # Email Configuration (for notifications)
   SMTP_ENABLED=true
   GMAIL_USERNAME=your-email@gmail.com
   GMAIL_PASSWORD=your-app-password
   FROM_EMAIL=your-email@gmail.com
   FROM_NAME=Task Manager System

   # Application Configuration
   BASE_URL=http://localhost/task_manager/
   APP_ENV=development
   ```

3. **Test email configuration:**
   - For development: Use MailHog (see EMAIL_SETUP.md)
   - For production: Configure real SMTP server

### 5. Access the Application
1. Navigate to `http://localhost/task_manager/`
2. First visit will redirect to setup page
3. Create your first admin account
4. Login and start using the system

## Usage

### First Time Setup
1. Go to `http://localhost/task_manager/setup.php`
2. Create the first administrator account
3. Login with your new admin credentials

### Admin Tasks
1. **Manage Users**: Add new users, view all users, delete users
2. **Manage Tasks**: Create tasks, assign to users, set deadlines
3. **Monitor Progress**: Track task status and overdue items

### User Tasks
1. **View Tasks**: See all assigned tasks on dashboard
2. **Update Status**: Change task status as work progresses
3. **Track Deadlines**: Monitor upcoming and overdue deadlines

## File Structure

```
task_manager/
├── admin/
│   ├── tasks.php          # Admin task management
│   └── users.php          # Admin user management
├── config/
│   ├── config.php         # Application configuration
│   └── database.php       # Database connection class
├── user/                  # User dashboard (coming soon)
├── index.php              # Main dashboard
├── login.php              # Login page
├── logout.php             # Logout handler
├── setup.php              # First-time setup
└── database_setup.sql     # Database schema
```

## Database Schema

### Users Table
- `id`: Primary key
- `username`: Unique username
- `email`: User email address
- `password`: Hashed password
- `full_name`: User's full name
- `role`: 'admin' or 'user'
- `created_at`, `updated_at`: Timestamps

### Tasks Table
- `id`: Primary key
- `title`: Task title
- `description`: Task description
- `assigned_to`: Foreign key to users table
- `created_by`: Foreign key to users table (admin who created)
- `status`: 'Pending', 'In Progress', or 'Completed'
- `deadline`: Task deadline (optional)
- `created_at`, `updated_at`: Timestamps

## Upcoming Features

- [ ] Email notifications when tasks are assigned
- [ ] User dashboard for task viewing and updates
- [ ] Task editing functionality
- [ ] File attachments for tasks
- [ ] Task comments and communication
- [ ] Advanced reporting and analytics
- [ ] Vue.js frontend enhancements

## Development

This project follows object-oriented principles and uses:
- **PDO** for database interactions
- **Password hashing** for security
- **Session management** for authentication
- **Input validation** and **SQL injection protection**
- **Clean separation** of concerns

## Security Features

- Password hashing with PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- Input validation and sanitization

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is for educational purposes and learning PHP development.
