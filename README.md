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

## Scheduling & Automation

The project now includes comprehensive scheduling support for automated data collection:

### Command-Line Scheduler

- **Automated Collection**: `scheduler.php` for cron job automation
- **Flexible Options**: Device-specific, force collection, dry-run mode
- **Smart Scheduling**: Avoids duplicate collection within time windows
- **Comprehensive Logging**: Detailed logs and statistics tracking
- **Error Handling**: Robust error management and reporting

### Scheduler Features

- **SNMP Connectivity Testing**: Pre-collection connectivity verification
- **Batch Processing**: Efficient multi-device data collection
- **Statistics Tracking**: Collection performance monitoring
- **Configurable Options**: Verbose output, device targeting, force modes

### Usage Examples

```bash
# Basic collection (recommended for cron)
php scheduler.php

# Verbose collection with output
php scheduler.php --verbose

# Force collection for all devices
php scheduler.php --force --verbose

# Collect specific device only
php scheduler.php --device=123

# Dry run (test without actual collection)
php scheduler.php --dry-run --verbose

# Show help
php scheduler.php --help
```

### Cron Job Setup

```bash
# Every 15 minutes (recommended)
*/15 * * * * cd /path/to/project && php scheduler.php >/dev/null 2>&1

# Every 5 minutes (high frequency)
*/5 * * * * cd /path/to/project && php scheduler.php --verbose >> /var/log/snmp-collection.log 2>&1

# Hourly with logging
0 * * * * cd /path/to/project && php scheduler.php --verbose >> /var/log/snmp-collection.log 2>&1
```

### Scheduler Monitoring

- **Web Interface**: `scheduler-logs.php` for viewing collection history
- **Performance Metrics**: Collection duration, success rates, error tracking
- **Daily Statistics**: Aggregated daily collection statistics
- **Trend Analysis**: Visual charts for collection performance
- **Database Logging**: All collection runs stored in `collection_logs` table

### Installation Steps

1. Import additional scheduler table: `mysql -u username -p database_name < db_scheduler.sql`
2. Test scheduler manually: `php scheduler.php --dry-run --verbose`
3. Set up cron job using examples from `cron-examples.txt`
4. Monitor via web interface at `scheduler-logs.php`

### Files

- `scheduler.php` - Main scheduler script
- `db_scheduler.sql` - Additional database table for logging
- `cron-examples.txt` - Cron job examples and setup instructions
- `scheduler-logs.php` - Web interface for monitoring scheduler
