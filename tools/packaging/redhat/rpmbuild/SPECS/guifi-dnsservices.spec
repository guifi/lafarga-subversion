Name: guifi-dnsservices
Version: 1.1
Release: 4%{?dist}
Summary: The CNML services to generate DNS configuration files

Group: Applications/Internet
License: GPLv3
URL: http://www.guifi.net/
Source0: %{name}-%{version}.%{release}.tar.gz

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

Requires: php php-gd bind-chroot
Requires(postun): /sbin/service 
Requires(postun): /sbin/chkconfig 
BuildArch: noarch

%description
guifi.net CNML services provides a fundation for a distributed network 
management.
This package provides the program to create BIND configuration files which
keeps in synch with the domains at the database.

%prep
%setup -q
echo "*/24 * * * *    named    cd /var/named/chroot/etc/ && /usr/bin/php /usr/share/dnsservices/dnsservices.php >> /var/named/chroot/var/log/dnsservices.log " >dnsservices.cron
echo "*/26 * * * *    root    service named reload" >>dnsservices.cron
echo " " > named.conf.int.private
echo " " > named.conf.ext.private
echo " " > named.options

%install
rm -rf %{buildroot}
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/
%{__mkdir} -p %{buildroot}/etc/dnsservices
%{__mkdir} -p %{buildroot}/usr/share/dnsservices
%{__mkdir} -p %{buildroot}/var/log/dnsservices
%{__install} -D -m 0755 *php %{buildroot}/usr/share/dnsservices #%{buildroot}%{_datadir}/
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/
%{__install} -D -m 0644 dnsservices.cron %{buildroot}/etc/cron.d/dnsservices
%{__mkdir} -p %{buildroot}/var/named/chroot/var/named
%{__install} -D -m 0755 named.conf.int.private %{buildroot}/var/named/chroot/var/named/named.conf.int.private
%{__install} -D -m 0755 named.conf.ext.private %{buildroot}/var/named/chroot/var/named/named.conf.ext.private
%{__install} -D -m 0755 named.options %{buildroot}/var/named/chroot/var/named/named.options


%clean
rm -rf %{buildroot}

%pre

%post
chmod -R 770 /var/named
%{__mkdir} -p /etc/dnsservices
%{__mkdir} -p /var/log/dnsservices
echo "To complete the installation, run at the shell as root:"
echo "/usr/share/dnsservices/dnsservices_conf.php"
echo "to configure your DNS service."

%postun
rm -fr /etc/dnsservices
rm -fr /var/log/dnsservices
/etc/init.d/named stop

%files
%config(noreplace) /etc/cron.d/dnsservices
%config(noreplace) /var/named/chroot/var/named/named.conf.int.private
%config(noreplace) /var/named/chroot/var/named/named.conf.int.private
%config(noreplace) /var/named/chroot/var/named/named.options
%attr(0777,root,root) %{_datadir}/*
%attr(0777,root,root) /var/log/dnsservices
%attr(0755,named,named) /var/named/chroot/var/named/named.conf.ext.private
%attr(0755,named,named) /var/named/chroot/var/named/named.conf.int.private
%attr(0755,named,named) /var/named/chroot/var/named/named.options
%changelog
* Wed Oct 20 2010 Miquel Martos <miquel.martos@guifi.net> - 1.1.4
- Reformated named.conf output.

* Thu Oct 14 2010 Miquel Martos <miquel.martos@guifi.net> - 1.1.3
- Fix on delegation creation.

* Wed Oct 13 2010 Miquel Martos <miquel.martos@guifi.net> - 1.1.2
- Updated dnsservices.php, Now, the master domain, no generate slave domains for delegated hosts.
- Added timeout on remote server alive tests.

* Thu Oct 12 2010 Miquel Martos <miquel.martos@guifi.net> - 1.1.1
- Fixed cron.d location.
- Updated dnsservices.php, now can use MX priority.

* Thu Apr 8 2010 Miquel Martos <miquel.martos@guifi.net> - 1.0.41
- Some RedHat bugs fixed.

* Thu Apr 8 2010 Miquel Martos <miquel.martos@guifi.net> - 1.0.40
- Fixed some bugs.

* Thu Oct 15 2009 Tomas Velazquez <tomas.velazquez@guifi.net> - 0.3.8
- Fixed some bugs.

* Wed Oct 14 2009 Tomas Velazquez <tomas.velazquez@guifi.net> - 0.3.7
- Fixed some bugs.

* Wed Oct 14 2009 Tomas Velazquez <tomas.velazquez@guifi.net> - 0.3.6
- Added range 192.168.0.0/16 to internal view zone

* Mon Oct 12 2009 Tomas Velazquez <tomas.velazquez@guifi.net> - 0.3.5
- Slaves and Forwarders support
- Added named.conf.private (external and internal)

* Thu Oct 1 2009 Tomas Velazquez <tomas.velazquez@guifi.net> - 0.3.1
- Initial package.

