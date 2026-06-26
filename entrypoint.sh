#!/bin/sh

# Disable conflicting MPMs at runtime
a2dismod mpm_event || true
a2dismod mpm_worker || true
a2enmod mpm_prefork || true

# Start Apache
exec apache2-foreground
