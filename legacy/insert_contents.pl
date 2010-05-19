#!/usr/racal1/apex_bin/perl/bin/perl -w

use Manx::DB;

my ($dbh, $smt, $sth, $rc, $rv, @rowary);

$dbh = connectdb();
die $DBI::errstr if $DBI::err;

$pub = 0;
$insert = 0;
$num_part_matches = 0;

while (<>) {
	chomp;
	if (/^#/) {
		if (/^#REVISION\s+(.*)$/) {
			$revision = $1;
		} elsif (/^#PART\s+(.+)$/) {
			# Try to find exact part number in PUB
			$part = $1;
			$norm_part = normalise_part_number($part);
			if (defined($revision)) {
				$sth = $dbh->prepare('select pub_id,ph_part from PUB join PUBHISTORY on pub_history=ph_id where ph_match_part=? and ph_revision=?');
				$sth->execute($norm_part, $revision);
			} else {
				$sth = $dbh->prepare('select pub_id,ph_part from PUB join PUBHISTORY on pub_history=ph_id where ph_match_part=?');
				$sth->execute($norm_part);
			}
			while (@rowary = $sth->fetchrow_array) {
				++$num_part_matches;
				$pub = $rowary[0];
				$insert = 1;
				print "\n$part = $rowary[1] -> $pub";
			}
			$sth->finish;
			if ($num_part_matches == 0) {
				$pub = 0;
				$insert = 0;
				print "\n$part -> NOT IN PUB";
			} elsif ($num_part_matches > 1) {
				$pub = 0;
				$insert = 0;
				print "\n$part -> AMBIGUOUS";
			}
			if ($pub) {
				# Now see if there are any contents already. This query always succeeds.
				@rowary = $dbh->selectrow_array('select pub_has_toc from PUB where pub_id=?', undef, $pub);
				if ($rowary[0] == 0) {
					$toc_count = 0;
				} else {
					print " Already got ToC";
					$insert = 0;
				}
				$sth->finish;
	
			}
		} elsif (/^#PART$/) {
			# If there isn't a part number, we'll try by title
			$pub = 0;
	
		} elsif (/^#TITLE\s(.+)/) {
			# If there isn't a part number, find title in PUB
			if (!$pub) {
				$title = $1;
				$sth = $dbh->prepare('select pub_id from PUB join PUBHISTORY on pub_history=ph_id where ph_title=?');
				$sth->execute($title);
				while (@rowary = $sth->fetchrow_array) {
					++$num_part_matches;
					$pub = $rowary[0];
					$insert = 1;
					print "\n$title -> $pub";
				}
				$sth->finish;
				if ($num_part_matches == 0) {
					$insert = 0;
					print "\n$title -> NOT IN PUB";
				} elsif ($num_part_matches > 1) {
					$insert = 0;
					print "\n$title -> AMBIGUOUS";
				}
				if ($pub) {
					# Now see if there are any contents already. This query always succeeds.
					@rowary = $dbh->selectrow_array('select pub_has_toc from PUB where pub_id=?', undef, $pub);
					if ($rowary[0] == 0) {
						$toc_count = 0;
					} else {
						print " Already got ToC";
						$insert = 0;
					}
					$sth->finish;
				}
			}
		}
		# Must be some other comment

	} elsif (/^\d/) {
		if ($insert) {
			@bits = split /\t/;
			$bits[2] ||= ''; # In case we have a label but no name
			# No longer pass @bits to TOC because there are now page numbers in some files (2006-04-28)
			$dbh->do('insert into TOC (pub,line,level,label,name) values (?,?,?,?,?)', undef, $pub, ++$toc_count, $bits[0], $bits[1], $bits[2]);
		}
	} else {
		print " ERROR: $_\n";
	}
}

if ($insert) {
	$dbh->do('update PUB set pub_has_toc=1 where pub_id=?', undef, $pub);
}

$dbh->disconnect() if $dbh;

print "\n";
exit;

sub normalise_part_number {
	my $pn = shift || $_;
	$pn = uc($pn);
	$pn =~ tr/A-Z0-9//cd;
	$pn =~ tr/O/0/;
	return $pn;
}
