$(document).ready(
    function()
    {
        $("#lkpt").css('display', 'none');
        $("#part").change(
        function()
        {
            $.post("PartLookup.php", { part: $("#part").val(), company: $("#company").val() },
                function(xml)
                {
                    var newPartList = '<table><tbody>';
                    $("pub",xml).each(
                        function()
                        {
                            newPartList += '<tr><td>' + $(this).find("part").text() + "</td>"
                                + "<td><cite>" + $(this).find("title").text() + "</cite></td></tr>";
                        });
                    newPartList += '</tbody></table>';
                    $("#partlist").html(newPartList);
                });
        });
    });
