package Manx::Utilities;

use strict;

BEGIN {
	use Exporter ();
	our ($VERSION, @ISA, @EXPORT, @EXPORT_OK, %EXPORT_TAGS);

	$VERSION = 1.00;
	@ISA = qw(Exporter);
	@EXPORT = qw(&excelquote &normalise_part_number &sort_part_number &trim &html_encode
		&html_blob_encode &html_encode_nbsp &neat_quoted_list &neat_list_or);
}

sub excelquote {
	my $field = shift || $_;
	if ($field =~ /[,"]/) {
		$field =~ s/"/""/g;
		$field = qq{"$field"};
	}
	return $field;
}

sub normalise_part_number {
	my $pn = shift || $_;
	return undef if !defined($pn);
	$pn = uc($pn);
	$pn =~ tr/A-Z0-9//cd;
	$pn =~ tr/O/0/;
	return $pn;
}

# sort_part_number(company, part)
sub sort_part_number {
	my ($cp,$pn) = @_;
	return undef if !defined($pn);
	my $spn;
	$pn = uc($pn);
	# Calculate a default sorted part number, along the same lines as normalisation, without the 'O' -> '0' translation
	($spn = $pn) =~ tr/A-Z0-9//cd;
	if ($cp == 1) {
		# DEC
		$pn =~ s/-PRE\d*$/-000/;
		$pn =~ tr/A-Z0-9//cd;
		$pn =~ s/B(\d)$/0$1/ if $pn =~ /^ADC740.B.$/; # special case to get RT-11 Software Dispatch in order
		$spn = $pn;
	}
	if ($cp == 2) {
		# Texas Instruments
		if ($pn =~ /^\d{6}-\d{4}/) { $pn = '0' . $pn; }
		$pn =~ tr/A-Z0-9//cd;
		$spn = $pn;
	}
	if ($cp == 6) {
		# TeleVideo
		# B300013-001 was earliest form. Then dropped B and then added a '1'.
		# 970 Maintenance Manual has numbers 3002100 (transitional), and then 130021-00
		if ($pn =~ /^B/) { $pn = substr($pn,1); }
		if ($pn =~ /^3/) { $pn = '0' . $pn; }
		$pn =~ tr/A-Z0-9//cd;
		$spn = $pn;
	}
	if ($cp == 9) {
		# Visual
		# Order by numbers only
		$pn =~ tr/0-9//cd;
		$spn = $pn;
	}
	if ($cp == 13) {
		# Wyse
		# An extra digit was added in the middle in 1985, so pad earlier numbers
		if ($pn =~ /^\d\d-\d\d\d-/) { $pn = substr($pn,0,3) . '0' . substr($pn,3); }
		$pn =~ tr/A-Z0-9//cd;
		$spn = $pn;
	}
	if ($cp == 19) {
		# IBM
		if ($pn =~ /\d\d-\d\d\d\d$/) { $pn .= '-0'; }
		if ($pn =~ /^\d\d-\d\d\d\d-\d+$/) { $pn = 'A' . $pn; }
		if ($pn =~ /^[A-Z]\w\w\d-/) { $pn = substr($pn,1); }
		if ($pn =~ /^\w\w\d-\d\d\d\d-(\d+)$/) { $pn = substr($pn,0,9) . sprintf("%02d",$1); }
		$pn =~ tr/A-Z0-9//cd;
		$spn = $pn;
	}
	if ($cp == 49) {
		# Motorola
		if ($pn =~ /AN(\d+)(.*)/) { $pn = sprintf("AN%05d%s", $1, $2); }
		$pn =~ tr/A-Z0-9//cd;
		$spn = $pn;
	}
	if ($cp == 58) {
		# Interdata/Perkin-Elmer
		if ($pn =~ /^([A-Z]+)/) { $pn = substr($pn,length($1)); } # initial letters (distribution codes, like IBM's?) disregarded
		$pn =~ tr/A-Z0-9//cd;
		$spn = $pn;
	}
	if ($cp == 70) {
		# Teletype
		if ($pn =~ /^(\d+)(.*)/) { $pn = sprintf("%04d%s", $1, $2); }
		$pn =~ tr/A-Z0-9//cd;
		$spn = $pn;
	}
	if ($cp == 80) {
		# GRI
		if ($pn =~ /^(\d\d)-(\d\d)-(.*)$/) { $pn = $1 . sprintf("%03d",$2) . $3; }
		$pn =~ tr/A-Z0-9//cd;
		$spn = $pn;
	}
	return $spn;
}

sub trim {
	my @out = @_;
	for (@out) {
		s/^\s+//;
		s/\s+$//;
	}
	return wantarray ? @out : $out[0];
}

sub html_encode($) {
	my $str = shift;
	$str = '' if !defined($str);
	if (defined($str)) {
		$str =~ s/&/&amp;/g;
		$str =~ s/>/&gt;/g;
		$str =~ s/</&lt;/g;
		$str =~ s/"/&quot;/g;
	}
	return $str;
}

sub html_blob_encode($) {
	my $str = shift || '';
	if (defined($str)) {
		$str =~ s/&/&amp;/g;
		$str =~ s/>/&gt;/g;
		$str =~ s/</&lt;/g;
		$str =~ s/"/&quot;/g;
		$str =~ s/\015\012/<BR>/g;
	}
	return $str;
}

# As html_encode(), except also make spaces into non-breaking spaces
sub html_encode_nbsp($) {
	my $str = shift || '';
	if (defined($str)) {
		$str =~ s/ /\240/g;
		$str =~ s/&/&amp;/g;
		$str =~ s/>/&gt;/g;
		$str =~ s/</&lt;/g;
		$str =~ s/"/&quot;/g;
		$str =~ s/\240/&nbsp;/g;
	}
	return $str;
}

sub neat_quoted_list {
	return '"' . (@_ > 1 ? join('", "', @_[0 .. $#_-1]) . qq{" and "$_[-1]} : $_[0]) . '"';
}

sub neat_list_or {
	return (@_ > 1 ? join(', ', @_[0 .. $#_-1]) . ", or $_[-1]" : $_[0]);
}

1;
