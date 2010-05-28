<?php
	require_once 'IFormatter.php';
	
	class HtmlFormatter implements IFormatter
	{
		public static function getInstance()
		{
			return new HtmlFormatter();
		}
		private function __construct()
		{
		}
		
		public static function neatQuotedList($words)
		{
			if (count($words) > 1)
			{
				return '"' . implode('", "', array_slice($words, 0, count($words) - 1))
					. '" and "' . $words[count($words) - 1] . '"';
			}
			else
			{
				return '"' . $words[0] . '"';
			}
		}
		
		public function renderResultsBar($ignoredWords, $searchWords, $start, $end, $total)
		{
			if (count($ignoredWords) > 0)
			{
				print '<p class="warning">Ignoring ' . HtmlFormatter::neatQuotedList($ignoredWords)
					. '.  All search words must be at least three letters long.</p>';
			}
			print '<div class="resbar">';
			if (count($searchWords) > 0)
			{
				print 'Searching for ' . HtmlFormatter::neatQuotedList($searchWords) . '.';
			}
			else
			{
				print 'Showing all documents.';
			}
			print ' Results <b>' . $start . ' - ' . $end . '</b> of <b>' . $total . '</b>.</div>';
		}
		
		public function renderPageSelectionBar($start, $total, $rowsPerPage)
		{
		/*
			--$start; # zero-based again
			$page_links = '<DIV CLASS="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;';
			# Remember to fully parenthesise options here, as '.' has higher priority than '?:'
			my $link_options =
				(($num_per_page != $DEFAULTNUMPERPAGE) ? ";num=$num_per_page" : '') .
				(defined(param('debug')) ? ';debug=1' : '') .
				(defined(param('disposition')) ? ';disposition=1' : '') .
				((defined(param('on')) && param('on')) ? ';on=on' : '') .
				';cp=' . param('cp');

			if ($start != 0) {
				$page_links .= qq{<A HREF="${SEARCHURL}?q=} . CGI::escape($param_q) . qq{;start=} .
					($start - $num_per_page) . $link_options . qq{"><B>Previous</B></A>&nbsp;&nbsp;};
			}
			my $first_page = int($start / (10 * $num_per_page)) * 10 + 1;
			my $last_page_num = int(($total_matches + $num_per_page - 1) / $num_per_page);
			my $last_page_start = ($last_page_num - 1) * $num_per_page;
			# First page number at the bottom is 1, 11, 21 ...
			my $curr_page_num = $first_page;
			my $curr_page_start = ($curr_page_num - 1) * $num_per_page;
			my $start_page_num = int($start / $num_per_page) + 1;

			my $num_indices = 0;
			while ($num_indices++ < 10) {
				if ($start == $curr_page_start) {
					$page_links .= qq{<B CLASS="currpage">$curr_page_num</B>&nbsp;&nbsp;};
				} else {
					$page_links .= qq{<A CLASS="navpage" HREF="${SEARCHURL}?q=} . (CGI::escape($param_q) || '') .
						qq{;start=$curr_page_start} . $link_options .
						qq{">$curr_page_num</A>&nbsp;&nbsp;};
				}
				++$curr_page_num;
				$curr_page_start += $num_per_page;
				last if $curr_page_start > $last_page_start;
			}
			if ($start != $last_page_start) {
				$page_links .= qq{<A HREF="${SEARCHURL}?q=} . (CGI::escape($param_q) || '') . qq{;start=} .
					($start + $num_per_page) . $link_options . qq{"><B>Next</B></A>};
			}
			$page_links .= "</DIV>\n";

			print $page_links;
		*/
			print '<div class="pagesel">Result page:&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<b class="currpage">1</b>&nbsp;&nbsp;';
			print '</div>';
		}
		
		public function renderResultsPage($rows, $start, $end)
		{
			throw new Exception("renderResultsPage: not implemented");
		}
	}
?>
