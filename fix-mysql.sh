#!/bin/bash
# Add Wangari MySQL tuning
if ! grep -q '^innodb_buffer_pool_size = 256M' /etc/mysql/mariadb.conf.d/50-server.cnf; then
  cat >> /etc/mysql/mariadb.conf.d/50-server.cnf << 'MYSQLCONF'

[mysqld]
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
query_cache_type = 1
query_cache_size = 64M
max_connections = 150
thread_cache_size = 16
MYSQLCONF
fi
systemctl restart mariadb
echo "MySQL restarted"
systemctl is-active mariadb
