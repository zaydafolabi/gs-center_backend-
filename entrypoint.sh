#!/bin/sh

# Database credentials - support custom DB_* vars with automatic fallback to Railway's MYSQL* vars
DB_HOST_VAL="${DB_HOST:-${MYSQLHOST:-localhost}}"
DB_PORT_VAL="${DB_PORT:-${MYSQLPORT:-3306}}"
DB_NAME_VAL="${DB_NAME:-${MYSQLDATABASE:-gs_db}}"
DB_USER_VAL="${DB_USER:-${MYSQLUSER:-root}}"
DB_PASS_VAL="${DB_PASSWORD:-${MYSQLPASSWORD:-}}"

# Export variables to Apache's envvars file so Apache and PHP can read them
echo "export DB_HOST=\"$DB_HOST_VAL\"" >> /etc/apache2/envvars
echo "export DB_PORT=\"$DB_PORT_VAL\"" >> /etc/apache2/envvars
echo "export DB_NAME=\"$DB_NAME_VAL\"" >> /etc/apache2/envvars
echo "export DB_USER=\"$DB_USER_VAL\"" >> /etc/apache2/envvars
echo "export DB_PASSWORD=\"$DB_PASS_VAL\"" >> /etc/apache2/envvars

# Disable conflicting MPMs at runtime
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2enmod mpm_prefork || true

# Start Apache
exec apache2-foreground
