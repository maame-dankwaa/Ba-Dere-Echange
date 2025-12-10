Ba Dɛre Exchange is a comprehensive marketplace platform designed specifically for the Ghanaian academic community. It facilitates the exchange of books, academic papers, and educational resources through multiple transaction types including purchases, rentals, and exchanges.

The platform features:
- Multi-modal transactions (Buy, Rent, Exchange)
- Vendor application and verification system
- Featured listings with Paystack payment integration
- Advanced search and filtering
- Wishlist management
- Review and rating system
- Admin dashboard with analytics
- Contact message system
- Institutional account support

---

## Features

### For Buyers/Students
- **Advanced Search** - Find books by title, author, category, condition, price range
- **Wishlist Management** - Save favorite listings for later
- **Reviews & Ratings** - Rate and review purchased items
- **Multiple Transaction Types** - Buy, rent, or exchange books
- **Responsive Design** - Works on desktop, tablet, and mobile
- **Featured Listings** - See promoted high-quality listings

### For Vendors/Sellers
- **Easy Listing Creation** - Upload books with images, set pricing and availability
- **Featured Listings** - Promote listings for better visibility (2-week or 1-month packages)
- **Vendor Dashboard** - Track sales, manage inventory, view analytics
- **Payment Integration** - Secure payments through Paystack
- **Performance Metrics** - View sales statistics and customer ratings

### For Administrators
- **User Management** - Manage users, vendors, and institutions
- **Vendor Approval System** - Review and approve vendor applications
- **Transaction Monitoring** - View all transactions, payments, and commissions
- **Contact Message Management** - Handle customer inquiries
- **Analytics Dashboard** - Platform-wide statistics and insights
- **Category Management** - Create and manage listing categories

---

## Technology Stack

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+** - Relational database
- **PDO** - Database abstraction layer with prepared statements

### Frontend
- **HTML5** - Semantic markup
- **CSS3** - Modern styling with custom properties
- **JavaScript (ES6+)** - Client-side interactivity
- **SweetAlert2** - Beautiful, responsive alerts

### Third-Party Integrations
- **Paystack** - Payment processing for featured listings
- **XAMPP** - Local development environment (Apache + MySQL + PHP)

### Architecture Patterns
- **MVC Pattern** - Separation of concerns (Models, Views, Controllers)
- **Service Layer** - Business logic separation

---

### Development Environment
- **XAMPP** (recommended) or equivalent LAMP/MAMP stack
- **Git** (for version control)


## Installation

### 1. Clone the Repository

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/
git clone <repository-url> ba_dere_exchange
cd ba_dere_exchange
```

### 2. Set Up Database

#### Option A: Using phpMyAdmin
1. Open phpmyadmin
2. Create a new database: `ba_dere_exchange`
3. Import the schema: `database/schema.sql`
4. Import sample data (optional): `database/seed.sql`



### 3. Configure Database Connection

Edit `classes/Database.php` with your credentials:

```php
private $host = 'localhost';
private $dbname = 'ba_dere_exchange';
private $username = 'root';  // Your MySQL username
private $password = '';      // Your MySQL password
```

### 4. Set File Permissions



### 5. Configure Paystack

Edit `config/settings/paystack.php`:

```php
return [
    'public_key' => 'pk_test_YOUR_PUBLIC_KEY',
    'secret_key' => 'sk_test_YOUR_SECRET_KEY',
    'callback_url' => 'http://localhost/ba_dere_exchange/view/paystack_callback.php',
];
```



---

## Configuration

### Environment-Specific Settings

**Development:**
- Error reporting is enabled
- Database credentials in `classes/Database.php`
- Debug mode can be enabled in `config/app.php`

**Production:**
- Disable error display: `ini_set('display_errors', 0);`
- Use environment variables for sensitive data
- Enable HTTPS
- Update Paystack keys to live keys
- Set secure session cookies

### Email Configuration

Currently, the platform uses a placeholder email service. To enable real emails:

1. Configure SMTP settings in `services/EmailService.php`
2. Use services like SendGrid, Mailgun, or AWS SES
3. Update email templates in `emails/templates/`

### Upload Limits

Edit `php.ini` to adjust upload limits:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 5
```

---

## Database Schema

### Core Tables

#### Users
```sql
- user_id (PK)
- username, email, password_hash
- first_name, last_name, phone
- role (buyer, vendor, admin)
- institution_id (FK, nullable)
- created_at, updated_at
```

#### Books (Listings)
```sql
- book_id (PK)
- seller_id (FK -> users)
- category_id (FK -> categories)
- title, author, isbn_doi
- description, condition_type
- price, rental_price, rental_period_unit
- available_purchase, available_rent, available_exchange
- cover_image, status
- created_at, updated_at
```

#### Transactions
```sql
- transaction_id (PK)
- buyer_id (FK -> users)
- book_id (FK -> books)
- transaction_type (purchase, rental, exchange)
- total_amount, commission_amount
- payment_method, payment_status
- created_at, completed_at
```

#### Featured Listings
```sql
- featured_listing_id (PK)
- book_id (FK -> books)
- vendor_id (FK -> users)
- package_type (2_week, 1_month)
- amount_paid, payment_status
- start_date, end_date
- paystack_reference
- created_at
```

#### Reviews
```sql
- review_id (PK)
- book_id (FK -> books)
- buyer_id (FK -> users)
- rating (1-5)
- comment
- created_at
```

#### Contact Messages
```sql
- message_id (PK)
- user_id (FK -> users, nullable)
- name, email, subject, message
- status (new, read, responded, archived)
- admin_response, responded_by, responded_at
- created_at
```

### View Complete Schema
See `database/schema.sql` for the complete database structure.

---

## Project Structure

```
ba_dere_exchange/
├── actions/                 # Action handlers (form submissions, AJAX)
│   ├── browse_books.php
│   ├── list_book_store.php
│   ├── paystack_verify.php
│   └── wishlist.php
├── admin/                   # Admin panel pages
│   ├── admin_dashboard.php
│   ├── manage_users.php
│   ├── transactions.php
│   ├── contact_messages.php
│   └── manage_vendor_applications.php
├── classes/                 # Model classes (Data access layer)
│   ├── Database.php         # Singleton database connection
│   ├── User.php
│   ├── Book.php
│   ├── Transaction.php
│   ├── FeaturedListing.php
│   ├── Review.php
│   ├── Wishlist.php
│   ├── ContactMessage.php
│   ├── Category.php
│   └── VendorApplication.php
├── config/                  # Configuration files
│   └── settings/
│       └── paystack.php
├── controllers/             # Controller classes (Business logic)
│   ├── BookController.php
│   ├── CheckoutController.php
│   └── UserController.php
├── css/                     # Stylesheets
│   └── styles.css
├── database/                # Database schemas and seeds
│   ├── schema.sql
│   └── seed.sql
├── helpers/                 # Helper classes
│   └── AuthHelper.php       # Authentication utilities
├── includes/                # Reusable components
│   └── sweetalert.php
├── js/                      # JavaScript files
│   ├── app.js
│   ├── book_upload.js
│   └── sweetalert-helper.js
├── login/                   # Authentication pages
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── register_institution.php
├── logs/                    # Application logs
│   └── database-YYYY-MM-DD.log
├── services/                # Service layer classes
│   ├── Validator.php        # Input validation
│   ├── Logger.php           # Logging service
│   └── PaystackService.php  # Payment processing
├── uploads/                 # User-uploaded files
│   └── books/               # Book cover images
├── view/                    # View templates
│   ├── browse_books.php
│   ├── list_book.php
│   ├── single_book.php
│   ├── user_account.php
│   ├── vendor_dashboard.php
│   ├── feature_listing.php
│   └── contact_messages.php
├── index.php               # Homepage
├── README.md               # This file
├── ERRORS_AND_FIXES.md     # Error documentation
└── .htaccess               # Apache configuration
```

---

## User Roles

### 1. Buyer (Default Role)
**Capabilities:**
- Browse and search listings
- Add items to wishlist
- Purchase books
- Rent books
- Exchange books
- Leave reviews and ratings
- Submit contact messages
- Apply to become a vendor

**Restrictions:**
- Cannot create listings
- Cannot access vendor dashboard
- Cannot access admin panel

### 2. Vendor
**Capabilities:**
- All buyer capabilities, plus:
- Create, edit, and delete listings
- Purchase featured listing promotions
- Access vendor dashboard
- View sales analytics
- Manage inventory
- Respond to buyer inquiries

**Restrictions:**
- Cannot access admin panel
- Cannot approve other vendors

### 3. Admin
**Capabilities:**
- Full system access
- User management (create, edit, delete, role changes)
- Vendor application approval/rejection
- Transaction monitoring
- Contact message management
- Category management
- Platform analytics
- Content moderation

---

## Core Features

### 1. Listing Creation

**Supported Listing Types:**
- Physical Books
- Academic Papers/Documents
- Digital Resources

**Availability Options:**
- **Purchase:** One-time sale with fixed price
- **Rental:** Time-based borrowing (per day/week/month)
- **Exchange:** Swap books for a specified duration

**Listing Fields:**
- Title, Author, ISBN/DOI
- Publication Year, Page Count
- Category, Condition (Like New, Good, Acceptable, Poor)
- Description (min 10 characters)
- Cover Image (max 5MB, JPG/PNG/GIF/WebP)
- Pricing per availability type
- Location (City, Region)
- Available Quantity

**File: `view/list_book.php`**

---

### 2. Featured Listings

Vendors can promote their listings to appear at the top of browse results with a "Featured" badge.

**Packages:**
- **2 Weeks:** ₵50.00
- **1 Month:** ₵80.00

**Payment Flow:**
1. Vendor selects a listing to feature
2. Chooses package duration
3. Redirected to Paystack payment gateway
4. Payment verified via webhook
5. Listing automatically promoted with featured badge
6. Featured status expires after duration

**Files:**
- `view/feature_listing.php` - Package selection
- `actions/feature_listing_process.php` - Initiate payment
- `view/paystack_callback.php` - Payment callback
- `actions/paystack_verify.php` - Verify payment

---

### 3. Search & Filtering

**Search Options:**
- Full-text search across title, author, description
- Category filter
- Price range filter
- Condition filter (Like New, Good, Acceptable, Poor)
- Rating filter (minimum rating)
- Location filter
- Availability filters (Rentable, Exchangeable)

**Sort Options:**
- Price: Low to High
- Price: High to Low
- Rating: Highest First
- Newest First

**Featured listings always appear first**, followed by sorted results.

**Files:**
- `view/browse_books.php` - Filter UI
- `classes/Book.php` - `getBooksWithFilters()` method

---

### 4. Wishlist Management

**Features:**
- Add/remove items to wishlist
- View all wishlist items
- Direct purchase from wishlist
- Persistent across sessions

**Implementation:**
- Stored in `wishlist` table
- AJAX-based add/remove
- Real-time UI updates

**Files:**
- `actions/wishlist.php` - Wishlist page
- `classes/Wishlist.php` - Model

---

### 5. Review & Rating System

**Features:**
- 5-star rating system
- Written reviews
- Only verified buyers can review
- One review per purchase
- Average rating calculation
- Review moderation by admins

**Display:**
- Average rating on listing cards
- Full reviews on single book page
- Seller rating on profile

**Files:**
- `classes/Review.php` - Review model
- `view/single_book.php` - Display reviews

---

### 6. Admin Dashboard

**Statistics:**
- Total users, vendors, listings
- Total transactions and revenue
- Commission earnings
- New vendor applications
- Unread contact messages

**Management Sections:**
- User Management
- Vendor Applications
- Transaction History
- Contact Messages
- Categories

**Files:**
- `admin/admin_dashboard.php`
- `admin/manage_users.php`
- `admin/transactions.php`
- `admin/contact_messages.php`

---

### 7. Vendor Dashboard

**Statistics:**
- Total listings (active, pending, sold)
- Total sales and revenue
- Average rating
- Featured listings (active/expired)

**Sections:**
- My Listings (with edit/delete)
- Sales History
- Feature a Listing
- Performance Metrics

**Files:**
- `view/vendor_dashboard.php`

---

### 8. Contact System

**Features:**
- Guest and user submissions
- Subject categorization
- Status tracking (New, Read, Responded, Archived)
- Admin responses
- Email notifications (when configured)
- Archive/Unarchive functionality

**Status Workflow:**
1. **New:** Just submitted
2. **Read:** Admin viewed the message
3. **Responded:** Admin replied
4. **Archived:** Resolved/closed

**Files:**
- `view/contact.php` - Contact form
- `admin/contact_messages.php` - Admin view
- `admin/view_contact_message.php` - Single message view
- `admin/archive_contact_message.php` - Archive action
- `admin/unarchive_contact_message.php` - Unarchive action

---

## Payment Integration

### Paystack Setup

**1. Create Paystack Account**
- Visit https://paystack.com/
- Sign up and complete verification

**2. Get API Keys**
- Dashboard → Settings → API Keys & Webhooks
- Copy Public Key (pk_test_xxx or pk_live_xxx)
- Copy Secret Key (sk_test_xxx or sk_live_xxx)

**3. Configure Webhook**
- Set webhook URL: `https://yourdomain.com/actions/paystack_verify.php`
- Enable webhook for: `charge.success`

**4. Update Configuration**

Edit `config/settings/paystack.php`:
```php
return [
    'public_key' => 'pk_live_YOUR_PUBLIC_KEY',
    'secret_key' => 'sk_live_YOUR_SECRET_KEY',
    'callback_url' => 'https://yourdomain.com/view/paystack_callback.php',
];
```

### Payment Flow

1. **Initiate Payment** (`actions/feature_listing_process.php`)
   - Create transaction record
   - Generate unique reference
   - Redirect to Paystack

2. **User Pays** (Paystack hosted page)
   - Enter card details
   - Complete payment

3. **Callback** (`view/paystack_callback.php`)
   - User redirected back
   - Show success/failure message

4. **Verification** (`actions/paystack_verify.php`)
   - Webhook receives payment confirmation
   - Verify transaction with Paystack API
   - Update database
   - Activate featured listing

### Testing Payments

**Test Cards:**
```
Success: 4084084084084081
Decline: 5060666666666666666
```

**Test Mode:**
- Use `pk_test_` and `sk_test_` keys
- No real money charged
- Test all payment scenarios

---

## Security Features

### 1. Authentication
- Password hashing with `password_hash()` (bcrypt)
- Session-based authentication
- Role-based access control (RBAC)
- Session regeneration on login

### 2. Input Validation
- Server-side validation for all inputs
- Whitelist-based validation
- Type checking and sanitization
- Custom `Validator` service class

### 3. SQL Injection Prevention
- PDO prepared statements for all queries
- Named parameter binding
- No raw SQL from user input

### 4. XSS Prevention
- `htmlspecialchars()` on all output
- Content Security Policy headers (recommended)
- No `eval()` or dangerous functions

### 5. File Upload Security
- MIME type validation (not extension-based)
- File size limits (5MB for images)
- Dimension checks (max 2000x2000px)
- Random filename generation
- `.htaccess` in upload directories to prevent PHP execution
- Secure file permissions (644 for files, 755 for directories)

### 6. CSRF Protection
- Form tokens(recommended)
- Same-origin policy
- Referer checking

### 7. Session Security
- `session_regenerate_id()` on privilege changes
- HttpOnly cookies (recommended)
- Secure flag for HTTPS (production)
- Session timeout

### 8. Error Handling
- Custom error pages (404, 500)
- Error logging to files (not displayed to users in production)
- Sensitive data redaction in logs

---

## Development

### Coding Standards

**PHP:**
- PSR-12 coding standard
- Type declarations where possible
- Meaningful variable names
- Comments for complex logic
- DRY (Don't Repeat Yourself)

**JavaScript:**
- ES6+ features
- Const/let (no var)
- Arrow functions
- Async/await for asynchronous operations

**CSS:**
- BEM naming convention (recommended)
- CSS custom properties for theming
- Mobile-first responsive design
- Consistent spacing and indentation

### Adding a New Feature

**Example: Adding a "Book Exchange Requests" feature**

1. **Database Schema**
   ```sql
   CREATE TABLE exchange_requests (
       request_id INT AUTO_INCREMENT PRIMARY KEY,
       requester_id INT NOT NULL,
       book_id INT NOT NULL,
       offered_book_id INT NOT NULL,
       status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (requester_id) REFERENCES users(user_id),
       FOREIGN KEY (book_id) REFERENCES books(book_id),
       FOREIGN KEY (offered_book_id) REFERENCES books(book_id)
   );
   ```

2. **Create Model** (`classes/ExchangeRequest.php`)
   ```php
   class ExchangeRequest {
       public function create(array $data): int { }
       public function getById(int $id): ?array { }
       public function getUserRequests(int $userId): array { }
       public function updateStatus(int $id, string $status): bool { }
   }
   ```

3. **Create Controller** (`controllers/ExchangeController.php`)
   ```php
   class ExchangeController {
       public function createRequest(): void { }
       public function viewRequests(): void { }
       public function acceptRequest(): void { }
       public function rejectRequest(): void { }
   }
   ```

4. **Create Views** (`view/exchange_requests.php`)
   - List user's exchange requests
   - Form to create new request
   - Accept/reject buttons

5. **Create Actions** (`actions/exchange_request_submit.php`)
   - Handle form submission
   - Validate input
   - Call controller method

6. **Update Navigation** - Add link to exchange requests page

7. **Add Tests** - Test all functionality

---

## Troubleshooting

### Common Issues

#### 1. Database Connection Error

**Error:**
```
SQLSTATE[HY000] [1045] Access denied for user 'root'@'localhost'
```

**Solution:**
- Verify MySQL is running in XAMPP
- Check credentials in `classes/Database.php`
- Ensure database `ba_dere_exchange` exists

---

#### 2. File Upload Fails

**Error:**
```
Failed to save image. Please try again.
```

**Solution:**
```bash
# Create upload directory
mkdir -p uploads/books

# Set permissions (macOS/Linux)
chmod 777 uploads/books

# On Windows, right-click → Properties → Security → Edit → Add write permissions
```

---

#### 3. Featured Listing Not Activating

**Issue:** Payment successful but listing not featured

**Solution:**
1. Check Paystack webhook is configured correctly
2. Verify webhook URL is publicly accessible (use ngrok for local testing)
3. Check `logs/database-YYYY-MM-DD.log` for errors
4. Verify Paystack secret key in config

---

#### 4. Session Lost After Login

**Issue:** User logged out immediately after login

**Solution:**
- Check session cookie settings
- Ensure `session_start()` is called before any output
- Verify session directory has write permissions

---

#### 5. Images Not Displaying

**Issue:** Broken image icons on listings

**Solution:**
- Check file path in database matches actual file location
- Verify file permissions (644 for images)
- Check `.htaccess` is not blocking access
- Ensure GD extension is enabled in PHP

---

### Debug Mode

Enable detailed error reporting for development:

**In `index.php` (and other entry points):**
```php
// At the very top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

** Disable in production!**

---

### Logs

Application logs are stored in:
```
logs/database-YYYY-MM-DD.log
```

**Log Levels:**
- `INFO` - General information
- `WARNING` - Warning messages
- `ERROR` - Error messages
- `SECURITY` - Security events

**View logs:**
```bash
tail -f logs/database-$(date +%Y-%m-%d).log
```

---

## API Documentation

### PaystackService

**Location:** `services/PaystackService.php`

**Methods:**

```php
// Initialize payment
PaystackService::initializeTransaction(array $data): array

// Verify transaction
PaystackService::verifyTransaction(string $reference): array

// Parameters:
$data = [
    'email' => 'user@example.com',
    'amount' => 5000,  // Amount in pesewas (GHS 50.00)
    'reference' => 'unique_ref_123',
    'callback_url' => 'https://domain.com/callback',
    'metadata' => [
        'custom_fields' => [
            ['display_name' => 'Book ID', 'value' => '123']
        ]
    ]
];
```

---

## Performance Optimization

### Recommended Optimizations

1. **Database Indexing**
   - Already indexed: Primary keys, foreign keys
   - Consider adding: `books.title`, `books.status`, `users.email`

2. **Image Optimization**
   - Implement image resizing on upload
   - Use WebP format for modern browsers
   - Lazy loading for browse pages

3. **Caching**
   - Implement Redis/Memcached for session storage
   - Cache frequently accessed queries (categories, featured listings)
   - Browser caching with proper headers

4. **Query Optimization**
   - Use `LIMIT` on all listing queries (already implemented)
   - Add pagination to admin pages with many records
   - Use `JOIN` instead of multiple queries

5. **Asset Optimization**
   - Minify CSS and JavaScript
   - Combine multiple CSS/JS files
   - Use CDN for third-party libraries (already using for SweetAlert2)

---

## Contributing

We welcome contributions! Please follow these guidelines:

### How to Contribute

1. **Fork the Repository**
2. **Create a Feature Branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make Your Changes**
   - Follow coding standards
   - Add comments
   - Write tests if applicable
4. **Commit Your Changes**
   ```bash
   git commit -m "Add: Description of your feature"
   ```
5. **Push to Your Fork**
   ```bash
   git push origin feature/your-feature-name
   ```
6. **Submit a Pull Request**

### Commit Message Format

```
Type: Short description

Longer description if needed

Types: Add, Fix, Update, Remove, Refactor, Docs
```

**Examples:**
```
Add: Featured listing payment integration
Fix: SQL parameter binding error in search
Update: Improve vendor dashboard UI
Docs: Add API documentation for PaystackService
```

---



## Contact & Support

**Project Maintainer:** Ba Dɛre Exchange Team

---

## Acknowledgments

- **SweetAlert2** - Beautiful alert library
- **Paystack** - Payment processing for Ghana
- **XAMPP** - Local development environment
- **Contributors** - All who have contributed to this project

---

## Roadmap
