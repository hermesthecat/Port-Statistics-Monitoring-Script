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
Upload the index.php file on your (local)webserver
```

```bash
Change the credentials accordingly
```

```bash
Run the script either by visiting the page or with curl.
```
