# Port Statistics Monitoring Script

A basic monitoring script written in PHP which utilizes SNMP to monitor port statistics from network devices listed in the SQL database.

I'd advice anyone that is going to use this to make sure that you fully understand how SNMP works. If you don't understand how it works then the 2 guides below might be able to help you out.

- <https://www.networkmanagementsoftware.com/snmp-tutorial/>
- <https://www.networkmanagementsoftware.com/snmp-tutorial-part-2-rounding-out-the-basics/>

## Quick Installation

```bash
git clone https://github.com/Jiebss/Port-Statistics-Monitoring-Script.git
```

- Make sure that you have SNMP extension enabled in your PHP config.

```bash
Import the db.sql file
```

```bash
Copy config.example.php to config.php and update the configuration
cp config.example.php config.php
```

```bash
Edit config.php and update the following:
- Database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- SNMP community string (SNMP_COMMUNITY)
- Other settings as needed
```

```bash
Upload the files to your (local)webserver
```

```bash
Run the script either by visiting the page or with curl.
```

## Configuration

The script now uses a configuration file (`config.php`) instead of hardcoded values. Key settings include:

- **Database Settings**: Host, database name, username, password
- **SNMP Settings**: Community string, timeout, retries
- **Security Settings**: IP restrictions, authentication
- **Logging Settings**: Error logging, debug mode
- **SNMP OIDs**: Customizable OID mappings

## Security Features

- Configuration file separation for sensitive data
- Optional IP-based access control
- Error logging with timestamps
- Debug mode for development

## Dashboard Features

The script now includes a modern web-based dashboard accessible via `dashboard.php`:

### Main Dashboard

- **Real-time Statistics**: Device count, port count, error tracking
- **Interactive Charts**: Device status distribution, error trends over time
- **Device Management**: View all devices with status indicators
- **Auto-refresh**: Automatic data updates every 5 minutes
- **Responsive Design**: Works on desktop, tablet, and mobile devices

### Device Details Page

- **Comprehensive Device View**: Individual device monitoring via `device-details.php?id=X`
- **Port-level Statistics**: Detailed port information and error tracking
- **Historical Data**: Time-series charts and statistics
- **Real-time Status**: Current port status and performance metrics

### API Endpoints

- **RESTful API**: JSON endpoints for AJAX requests via `api.php`
- **Multiple Actions**: Dashboard stats, device lists, error trends, system health
- **Real-time Data**: Fresh data for charts and tables

### Dashboard Files

- `dashboard.php` - Main dashboard interface
- `device-details.php` - Individual device monitoring
- `api.php` - REST API endpoints
- `dashboard.js` - JavaScript for interactivity and real-time updates

### Features

- 📊 **Interactive Charts** with Chart.js
- 🔄 **Auto-refresh** functionality
- 📱 **Responsive Design** with Bootstrap 5
- ⚡ **Real-time Updates** via AJAX
- 🎨 **Modern UI** with gradients and animations
- 📈 **Historical Trends** and analytics
