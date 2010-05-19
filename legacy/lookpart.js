$(document).ready(function() {
	$("#lkpt").css('display', 'none');
	$("#part").change(function() {
		$.post("lookup", { part: $("#part").val(), company: $("#company").val() }, function(xml) {
			var newpartlist = '<table><tbody>';
			$("pub",xml).each(function() {
				newpartlist += '<tr><td>' + $(this).find("part").text() + "</td><td><cite>" + $(this).find("title").text() + "</cite></td></tr>";
			});
			newpartlist += '</tbody></table>';
			$("#partlist").html(newpartlist);
		});
	});
});
