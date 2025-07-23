# Project Starter Template

Below is the recommended folder structure for the Personal & Family Budgeting Web App:

```
Budgeting-Web-App/
│
├── assets/               # CSS, JS (Chart.js), images
├── includes/             # PHP includes: DB connection, session, helpers
├── export/               # (Optional) For CSV downloads
├── procedures.sql        # MySQL stored procedures
├── index.php             # Landing page / login redirect
├── register.php          # User registration
├── login.php             # User login
├── logout.php            # Logout script
├── dashboard.php         # Main dashboard after login
├── add_transaction.php   # Add income/expense
├── transactions.php      # View/filter/export transactions
├── settings.php          # User settings (budget cap, currency, etc.)
└── README.md             # Project documentation
```

**Folder/File Descriptions:**
- `assets/`: Static files (CSS, JS, images, Chart.js for graphs)
- `includes/`: PHP files for database connection, session management, and helper functions
- `export/`: (Optional) For generated CSV files for data export
- `procedures.sql`: SQL file containing all MySQL stored procedures
- `index.php`: Entry point, handles login redirect or landing page
- `register.php`: User registration form and logic
- `login.php`: User login form and logic
- `logout.php`: Ends user session
- `dashboard.php`: Main user dashboard with totals, graphs, and quick actions
- `add_transaction.php`: Form and logic to add new income/expense
- `transactions.php`: List, filter, and export transactions
- `settings.php`: User settings (budget cap, currency, etc.)
- `README.md`: Project documentation and setup instructions
