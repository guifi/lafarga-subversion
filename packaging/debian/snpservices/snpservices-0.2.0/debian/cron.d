#
# Regular cron jobs for the snpservices package
#

*/30 *  * * *   root    if [ -x /var/www/snpservices/graphs ]; then cd /var/www/snpservices/graphs ; fi; if [ -x /usr/bin/php ] && [ -r /var/www/snpservices/graphs/mrtgcsv2mrtgcfg.php ]; then env LANG=C /usr/bin/php mrtgcsv2mrtgcfg.php >> /var/log/snpservices/mrtgccfg.log 2>&1;fi
*/5 *   * * *   root    if [ ! -x /var/lock/mrtg ]; then mkdir /var/lock/mrtg ; fi; if [ -x /usr/bin/mrtg ] && [ -r /var/lib/snpservices/data/mrtg.cfg ]; then env LANG=C /usr/bin/mrtg /var/lib/snpservices/data/mrtg.cfg --lock-file /var/lock/mrtg/guifi_l >> /var/log/snpservices/mrtg.log 2>&1; fi
*/5 *   * * *   root  if [ -r /tmp/php-rrdtool/php5-rrdtool*.deb ]; then export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"; /usr/bin/dpkg -i /tmp/php-rrdtool/php5-rrdtool*.deb >> /tmp/miquel.log 2>&1; /etc/init.d/apache2 restart; /bin/rm -rf /tmp/php-rrdtool; fi;
