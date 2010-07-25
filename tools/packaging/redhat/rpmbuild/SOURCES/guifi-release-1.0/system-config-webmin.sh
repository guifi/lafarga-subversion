#!/bin/bash
#
# check/install webmin and launch
#

echo "Checking if webmin is already installed..."
#$webmin = `rpm -qa | grep webmin | wc -l`
if [ `rpm -qa | grep webmin | wc -l` -eq 0 ];
then
  echo "webmin is not installed, installing webmin..."
  rpm --import http://www.webmin.com/jcameron-key.asc
  yum -y install webmin
else 
  echo "webmin is already installed."
fi
cp /usr/share/guifi-release/config.webmin /etc/webmin/config
/sbin/service webmin restart
echo "Done."
