#!/bin/bash
echo -e "user = " $DATABASE_USERNAME "\npassword = " $DATABASE_PASSWORD "\ndbname = " $DATABASE_NAME "\nhost = " $DATABASE_HOST "\nport = " $DATABASE_PORT > /var/www/html/config/database.ini

#echo -e "user = " $OMEKA_USERNAME "\npassword = " $OMEKA_PASSWORD > /var/www/html/config/reindex.ini

apache2-foreground