Name: guifi-CNML-graphs
Version: 2.0
Release: 1%{?dist}
Summary: The CNML services to monitorize the network and become a graph server

Group: Applications/Internet
License: GPLv3
URL: http://www.guifi.net/
Source0: %{name}-%{version}.tar.gz
Source1: system-config-guifi-cnml.pl

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

Requires: traceroute httpd php php-gd mrtg rrdtool rrdtool-perl rrdtool-php
Requires(postun): /sbin/service 
Requires(postun): /sbin/chkconfig 
BuildArch: noarch

%description
guifi.net CNML services provides a fundation for a distributed network 
management.
This package provides the programs for a graph server service which
keeps in synch with the nodes at the database, and therefora, able to
monitorize the network status within a zone, stores historic data
and able to be queried for displaying that information. 

%prep
%setup -q
echo "*/5 * * * *	root	LANG=C; LC_ALL=C; cd %{_datadir}/cnml/graphs; php mrtgcsv2mrtgcfg.php >/dev/null 2>&1; %{_bindir}/mrtg ../data/mrtg.cfg --lock-file /var/lock/mrtg/guifi_l --confcache-file /var/lib/mrtg/guifi.ok > /dev/null 2>&1" >guifi-cnml.cron
cat <<EOF> guifi-cnml.conf
Alias /cnml /usr/share/cnml
Alias /snpservices /usr/share/cnml
<Directory /usr/share/cnml/>
   order deny,allow
   deny from all
   allow from all
</Directory>
EOF

%install
rm -rf %{buildroot}
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/
%{__mkdir} -p %{buildroot}/var/cnml/
%{__mkdir} -p %{buildroot}/var/cnml/logs/
%{__mkdir} -p %{buildroot}/var/cnml/images/
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/
%{__install} -d -m 0755 %{buildroot}/%{_datadir}/cnml/
%{__cp} -r * %{buildroot}/%{_datadir}/cnml/
%{__install} -D -m 0644 guifi-cnml.cron %{buildroot}/%{_sysconfdir}/cron.d/guifi-cnml
%{__install} -D -m 0644 guifi-cnml.conf %{buildroot}/%{_sysconfdir}/httpd/conf.d/guifi-cnml.conf
%{__install} -D -m 0644 %{SOURCE1} %{buildroot}/%{_datadir}/cnml/
#%{__install} -D -m 0755 proxypasswd.sh %{buildroot}/%{_sbindir}/proxypasswd.sh

%clean
rm -rf %{buildroot}

%pre

%post
#/sbin/chkconfig httpd on
#/sbin/service httpd restart
#echo "To complete the installation, run at the shell as root:"
#echo "%_datadir/cnml/system-config-guifi-cnml.pl"
#echo "to set your graph service id."

%postun
/sbin/service httpd reload
rm -f %{buildroot}/tmp/last_update.mrtg
rm -f %{buildroot}/tmp/last_mrtg

%files
%dir %{_datadir}/cnml
%{_datadir}/cnml/*
%config(noreplace) %{_sysconfdir}/cron.d/guifi-cnml
%config(noreplace) %{_sysconfdir}/httpd/conf.d/guifi-cnml.conf
%attr(0722,root,root) %{_datadir}/cnml/system-config-guifi-cnml.pl
%attr(0777,root,root) %{_datadir}/cnml/tmp
%attr(0777,root,root) /var/cnml/logs
%attr(0777,root,root) /var/cnml/images
%attr(0777,root,root) %{_datadir}/cnml/common/ping.sh


%changelog
* Sat Jan 10 2009 Ramon Roca <ramon.roca@guifi.net> - 0.1a
- Initial package.

