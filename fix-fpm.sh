#!/bin/bash
sed -i '/log_level/d' /etc/php/7.4/fpm/pool.d/www.conf
rm -f /etc/nginx/sites-enabled/wangari-domain.bak2
nginx -t && systemctl restart nginx
systemctl restart php7.4-fpm
echo PHP-FPM-STATUS
systemctl is-active php7.4-fpm
