#!/usr/bin/perl -w

use strict;
use DBI;
use URI;
use Manx::DB;
use Manx::Utilities;

my ($dbh, $smt, $sth, $rc, $rv);

$dbh = connectdb();
die $DBI::errstr if $DBI::err;

$sth = $dbh->prepare('select ph_id, ph_part, ph_match_part, ph_company, ph_sort_part, ph_alt_part, ph_match_alt_part from PUB' .
			' join PUBHISTORY on pub_history = ph_id');
warn $DBI::errstr if $DBI::err;
$sth->execute;
warn $DBI::errstr if $DBI::err;

my $numrows = 0;
my $neededchanges = 0;
my $changedrows = 0;
my $allowedchanges = 100;

while (my $r = $sth->fetchrow_hashref) {
	++$numrows;
	next if !defined($r->{ph_part});

	my $calc_mp = normalise_part_number($r->{ph_part});
	my $calc_sp = sort_part_number($r->{ph_company}, $r->{ph_part});
	if (!defined($r->{ph_match_part}) || $calc_mp ne $r->{ph_match_part}) {
		$dbh->do('update PUBHISTORY set ph_match_part=? where ph_id=?', undef, $calc_mp, $r->{ph_id});
		printf "Pub %5d  %s -> %s (was %s)\n", $r->{ph_id}, $r->{ph_part}, $calc_mp, (defined($r->{ph_match_part}) ? $r->{ph_match_part} : 'null');
	}

	if (!defined($r->{ph_sort_part}) || $calc_sp ne $r->{ph_sort_part}) {
		++$changedrows;
		$dbh->do('update PUBHISTORY set ph_sort_part=? where ph_id=?', undef, $calc_sp, $r->{ph_id});
		printf "SORT %5d  %-12s -> %-12s\n", $r->{ph_id}, $r->{ph_part}, $calc_sp;
	}

	next if !defined($r->{ph_alt_part});

	my $calc_map = normalise_part_number($r->{ph_alt_part});
	if (!defined($r->{ph_match_alt_part}) || $calc_map ne $r->{ph_match_alt_part}) {
		$dbh->do('update PUBHISTORY set ph_match_alt_part=? where ph_id=?', undef, $calc_map, $r->{ph_id});
		printf "Pub %5d  %s -> %s (was %s)\n", $r->{ph_id}, $r->{ph_alt_part}, $calc_map, (defined($r->{ph_match_alt_part}) ? $r->{ph_match_alt_part} : 'null');
	}

}
$sth->finish;

$dbh->disconnect() if $dbh;

print "Processed $numrows rows, changing $changedrows.\n";

exit;
