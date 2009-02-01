#!/bin/sh
PROXY_SERVICE_ID=ServiceId
wget http://guifi.net/guifi/export/$PROXY_SERVICE_ID/federated -qO /tmp/passwd
touch /usr/etc/passwd
NEW=`diff /usr/etc/passwd /tmp/passwd|wc -l`
OK=`grep Federated /tmp/passwd|wc -l`
if [ $OK != "0" ]; then
if [ $NEW != "0" ]; then
  cp /tmp/passwd /usr/etc/
 /etc/init.d/squid reload
  echo "Nou /usr/etc/passwd copiat"
 fi;
fi

