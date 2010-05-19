package Manx::DB;

use strict;

use DBI qw();

BEGIN {
	use Exporter ();
	our ($VERSION, @ISA, @EXPORT, @EXPORT_OK, %EXPORT_TAGS);

	$VERSION = 1.00;
	@ISA = qw(Exporter);
	@EXPORT    = qw(&connectdb);
	@EXPORT_OK = qw($admin_email);
}

our @EXPORT_OK;

our $admin_email;

our $DATASOURCE;
our $DATAUSER;
our $DATAPASS;

$admin_email = 'manx(frixxon.co.uk)';

$DATASOURCE = 'DBI:mysql:dbname';
$DATAUSER = 'dbuser';
$DATAPASS = 'dbpass';

sub connectdb() {
	return DBI->connect($DATASOURCE, $DATAUSER, $DATAPASS, { mysql_enable_utf8 => 1 });
}

1;
