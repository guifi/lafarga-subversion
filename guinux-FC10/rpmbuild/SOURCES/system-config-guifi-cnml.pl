#!/usr/bin/perl
#
# set the configuration CNML
#

$oldfile = "/usr/share/cnml/common/config.php.fedora";
$newfile = "/usr/share/cnml/common/config.php";
$old = "%CNMLId%";
$defserver=6833;

print "Enter the number of the Graph Service Id [$defserver]: ";
$new = <STDIN>;
chomp($new);
if ($new eq "") { $new=$defserver; }

print "Setting the Graph Server Id to $new\n";
open(OF, $oldfile) or die "Can't open $oldfile : $!";
open(NF, ">$newfile") or die "Can't open $newfile : $!";

# read in each line of the file
while ($line = <OF>) {
    $line =~ s/$old/$new/;
    print NF $line;
}

close(OF);
close(NF);
`chkconfig httpd on`;
`service httpd start`;
print "Successful: New Graph Server Id has been set to $new\n";
print "Check that the graph server is responding to the CNML calls by visiting http://localhost/cnml/index.php\n";
print "Done. Press <Enter> to finalize.";
$end = <STDIN>;
