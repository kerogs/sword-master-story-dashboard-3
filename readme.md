# SMSDv3 - Sword Master Story Dashboard

The assistant for Sword Master Story players.

## Features

### Currently Available
- **Automated Coupon Management**: Fetches and stores coupons automatically in the database (runs at regular intervals)
- **User Accounts**: Create and login to synchronize data across devices
- **Profile Management**: Edit your profile and set your favorite character
- **Integrated Help**: Built-in help page
- **Coupon Tracking**: Track used coupons and easily view their status (available, unavailable, etc.)
- **Comprehensive Statistics**: Detailed statistics and analytics
- **Custom Error Pages**: Custom 403 and 404 error pages

### Upcoming Features
- **Discord Webhooks Compatibility** (coming soon)
- **Team Power Tracking** (coming soon)
- **API Service** via FastAPI (coming soon)

### Known Issues
- Occasional logout when editing your profile
- Minor error message appears when visiting user profiles while not logged in

## Installation

### Web Application Setup

1. **Environment Configuration**
   - Copy `.env.sample` to `.env`
   - Fill in the required environment variables in the `.env` file
   - It's not recommended to change the database prefix unless necessary

2. **Database Setup**
   - Connect to your MySQL database
   - Create a new database
   - Import the SQL file: `backend/sql/smdv3.sql`

3. **Launch**
   - That's it! You can now use the website
   - Recommended: Create an account to access all features

### API Installation
- Coming soon

## Developer Notes

- This application was not primarily designed for public deployment. While possible, public usage is at your own risk. Please note that its initial intended use is within private networks.
- I'm open to discussions and any assistance with the project

## Technical Details

- Built with PHP and MySQL
- Uses PDO for database operations
- Environment-based configuration
- Session-based authentication system

## Support
Contact me via discord : ``kerogs``