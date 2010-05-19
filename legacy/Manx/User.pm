package Manx::User;

use strict;

BEGIN {
	use Exporter ();
	our ($VERSION, @ISA, @EXPORT, @EXPORT_OK, %EXPORT_TAGS);

	$VERSION = 1.00;
	@ISA = qw(Exporter);
	@EXPORT    = qw(&from_session);
	@EXPORT_OK = ();
}


sub from_session($$) {
	my ($dbh, $session) = @_;
	my $user;
	if ($session) {
		$user = $dbh->selectrow_hashref('select U.id,U.username,U.gn,U.sn from SESSION S join USER U on S.user=U.id where S.id=?', undef, $session);
		if (defined($user)) {
			$user->{logged_in} = 1;
			my $sth = $dbh->prepare(
				'select concat("pref_", pref_name) as pref_name,pref_type,ifnull(up_value,pref_default) as pref_value' .
				' from preference' .
				' left join user_pref on pref_id=up_pref and up_user=?');
			$sth->execute($user->{id});
			my $r;
			while ($r = $sth->fetchrow_hashref) {
				if ($r->{pref_type} eq 'boolean') {
					$user->{$r->{pref_name}} = $r->{pref_value} + 0;
				} else {
					$user->{$r->{pref_name}} = $r->{pref_value};
				}
			}
			$sth->finish;
			$sth = $dbh->prepare('select concat("can_", capability.name) as cap_name' .
				' from user_cap' .
				' join capability on user_cap.capability = capability.capability and user_cap.user=?');
			$sth->execute($user->{id});
			while ($r = $sth->fetchrow_hashref) {
				$user->{$r->{cap_name}} = 1;
			}
			$sth->finish;
		}
	}

	if (!defined($user)) {
		$user->{logged_in} = 0;
		$user->{username} = 'Guest';
	}

	return $user;
}

1;
