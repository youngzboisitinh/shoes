#!/bin/bash
set -e

mysql --protocol=socket \
  --default-character-set=utf8mb4 \
  -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /seed/01-shoestore.sql

mysql --protocol=socket \
  --default-character-set=utf8mb4 \
  -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /seed/02-fix-charset.sql