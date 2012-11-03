String.prototype.trim = function()
{
    return this.replace(/^\s+|\s+$/g, "");
};

$(function()
{
    var show = function(id)
    {
        $("#" + id).removeClass("hidden");
    };

    var hide = function(id)
    {
        $("#" + id).addClass("hidden");
    };

    var show_or_hide = function(id_field)
    {
        return $("#" + id_field).val() == -1 ? show : hide;
    };

    var build_option = function(val, text)
    {
        return '<option value="' + val + '">' + text + '</option>';
    };

    var build_option_list = function(id, new_text, transformer, json)
    {
        var options = [build_option(-1, new_text)];
        for (var i = 0; i < json.length; ++i)
        {
            options.push(transformer(json[i]));
        }
        $("#" + id).html(options.join(''));
    };

    var clear_or_set_error_label = function(error, id, message)
    {
        var label = $("label[for='" + id + "']");
        var error_id = id + "_error";
        var error_div = $("#" + error_id);
        if (error)
        {
            label.addClass("error");
            error_div.html(message);
            show(error_id);
            return false;
        }
        else
        {
            label.removeClass("error");
            error_div.text('');
            hide(error_id);
            return true;
        }
    };

    var validate_field_non_empty = function(input_id)
    {
        return clear_or_set_error_label(
            $("#" + input_id).val().trim().length == 0,
            input_id,
            'This value is required and cannot be empty.');
    };

    var validate_combo_box = function(combo_id, field_validator)
    {
        return ($("#" + combo_id).val() != -1) || field_validator();
    };

    var set_copy = function(data)
    {
        hide("copy_text");
        show("copy_link");
        $("#copy_url").val(data.url);
        if (data.mirror_url.length > 0)
        {
            show("copy_mirror_url_field");
            $("#copy_mirror_url").val(data.mirror_url);
            $("#copy_link").attr('href', data.mirror_url);
        }
        else
        {
            $("#copy_link").attr('href', data.url);
        }
        show("copy_site_field");
        $("#copy_site").val(data.site.site_id);
        show("copy_format_field");
        $("#copy_format").val(data.format);
        $("#copy_size").val(data.size);
    };
    var reset_copy = function()
    {
        show("copy_text");
        hide("copy_link");
        hide("copy_mirror_url_field");
        $("#copy_mirror_url").val('');
        hide("copy_site_field");
        $("#copy_site").val(-1);
        hide("copy_format_field");
        $("#copy_format").val('');
        $("#copy_size").val(0);
    };
    var copy_exists = function(json)
    {
        var copy_url = $("#copy_url");
        copy_url.data('exists', json.exists);
        copy_url.data('company', json.company);
        copy_url.data('pub_id', json.pub_id);
        copy_url.data('title', json.title);
    };
    var reset_exists = function()
    {
        var copy_url = $("#copy_url");
        copy_url.removeData('exists');
        copy_url.removeData('company');
        copy_url.removeData('pub_id');
        copy_url.removeData('title');
    }
    var validate_copy_exists = function()
    {
        var copy_url = $("#copy_url");
        return clear_or_set_error_label(copy_url.data('exists'), "copy_url",
            'Manx already knows about <a href="details.php/'
                + copy_url.data('company') + ',' + copy_url.data('pub_id') + '">'
                + copy_url.data('title') + '</a>.');
    };
    var validate_copy = function()
    {
        var size = $("#copy_size").val();
        return validate_copy_exists()
            && validate_field_non_empty('copy_url')
            && validate_combo_box('copy_site', validate_site)
            && validate_field_non_empty('copy_format')
            && Number(size) == size;
    };

    var set_bitsavers = function(data)
    {
        $("#bitsavers_directory").val(data.bitsavers_directory);
    };

    var reset_site = function()
    {
        $("#site_name").val('');
        $("#site_url").val('');
        $("#site_description").val('');
        $("#site_copy_base").val('');
        $("#site_low").val(false);
        $("#site_live").val(false);
    };
    var validate_site = function()
    {
        return validate_field_non_empty("site_name")
            && validate_field_non_empty("site_url")
            && validate_field_non_empty("site_description")
            && validate_field_non_empty("site_copy_base");
    };

    var set_company = function(data)
    {
        show("company_fields");
        $("#company_id").val(data.company);
        show_hide_company_fields();
    };
    var show_hide_company_fields = function()
    {
        var fn = show_or_hide("company_id");
        fn("company_name_field");
        fn("company_name_field");
        fn("company_short_name_field");
        fn("company_sort_name_field");
        fn("company_notes_field");
    };
    var reset_company = function()
    {
        hide("company_fields");
        $("#company_id").val(-1);
    };
    var validate_company = function()
    {
        return validate_field_non_empty("company_name")
            && validate_field_non_empty("company_short_name")
            && validate_field_non_empty("company_sort_name");
    };

    var set_publication = function(data)
    {
        var keywords = (data.part + ' ' + data.title).trim();
        show("publication_fields");
        $("#pub_history_ph_title").val(data.title);
        $("#pub_history_ph_pub_date").val(data.pub_date);
        $("#pub_history_ph_part").val(data.part);

        $("#pub_search_keywords").val(keywords);
        search_for_publications();

        $("#supersession_search_keywords").val(keywords);
        search_for_supersessions();
    };
    var reset_publication = function()
    {
        hide("publication_fields");
        $("#pub_history_ph_title").val('');
        $("#pub_history_ph_pub_date").val('');
        $("#pub_history_ph_part").val('');

        $("#pub_search_keywords").val('');

        $("#supersession_search_keywords").val('');
    };
    var validate_publication = function()
    {
        var validate_title_part_number = function()
            {
                var title = $("#pub_history_ph_title").val().toLowerCase().trim();
                var part = $("#pub_history_ph_part").val().toLowerCase().trim();
                return clear_or_set_error_label(
                    (part.length > 0) && (title.indexOf(part) != -1),
                    'pub_history_ph_title',
                    'The title cannot contain the part number.');
            };
        return validate_field_non_empty("pub_history_ph_title")
            && validate_title_part_number();
    };

    var set_pub_list = function(id, new_text, json)
    {
        build_option_list(id, new_text,
            function(item)
            {
                return build_option(item.pub_id,
                    item.ph_part + ' ' + item.ph_revision + ' ' + item.ph_title);
            },
            json);
    };

    var set_supersessions = function(json)
    {
        var set_supersession_pub = function(id)
        {
            set_pub_list(id, "(None)", json);
        };
        set_supersession_pub("supersession_old_pub");
        set_supersession_pub("supersession_new_pub");
    };
    var reset_supersessions = function()
    {
        var reset_option_list = function(id)
        {
            build_option_list(id, "(None)", null, []);
        };
        reset_option_list("supersession_old_pub");
        reset_option_list("supersession_new_pub");
    };
    var validate_supersession = function()
    {
        return true;
    };

    var set_publication_search_results = function(json)
    {
        set_pub_list("pub_pub_id", "(New Publication)", json);
    };
    var reset_publication_search_results = function()
    {
        build_option_list("pub_pub_id", "(New Publication)", null, []);
    };

    var wizard_service = function(data, callback)
    {
        $.post("url-wizard-service.php", data, callback, "json");
    };

    var pub_search = function(search_keywords, error_id, callback)
    {
        var company_id = $("#company_id").val();
        if (company_id != -1)
        {
            wizard_service(
                {
                    'error_id': error_id,
                    'method': "pub-search",
                    'company': $("#company_id").val(),
                    'keywords': search_keywords
                },
                callback);
        }
    };

    var ajax_error_handler = function(id)
    {
        return function(e, jqxhr, settings, exception)
        {
            if (settings.data.indexOf('error_id=' + id + '&') == 0)
            {
                show("pub_search_keywords_error");
                $(this).html(jqxdr.responseText);
            }
        };
    };

    var search_for_publications = function()
    {
        var error_id = 'pub_search_keywords_error';
        $("#" + error_id).ajaxError(ajax_error_handler(error_id));
        pub_search($("#pub_search_keywords").val(), error_id, set_publication_search_results);
    };

    var search_for_supersessions = function()
    {
        var error_id = 'supersession_search_keywords_error';
        $("#" + error_id).ajaxError(ajax_error_handler(error_id));
        pub_search($("#supersession_search_keywords").val(), error_id, set_supersessions);
    };

    var clear_errors = function()
    {
        $('div[id$="_error"]').addClass('hidden');
        $("label").removeClass('error');
    };

    var validate_data = function()
    {
        clear_errors();
        return validate_copy()
            && validate_combo_box('company_id', validate_company)
            && validate_combo_box('pub_pub_id', validate_publication)
            && validate_combo_box("supersession_old_pub", validate_supersession);
    };

    var reset_form = function()
    {
        reset_copy();
        reset_site();
        reset_company();
        reset_publication();
        hide("supersession_fields");
        reset_publication_search_results();
        reset_supersessions();
    };

    $("#copy_url_error").ajaxError(ajax_error_handler('copy_url_error'));
    $("#copy_url").change(
        function()
        {
            var url = $("#copy_url").val();
            if (url.length > 0)
            {
                wizard_service(
                    {
                        'error_id': 'copy_url_error',
                        'method': "url-lookup",
                        'url': url
                    },
                    function(json)
                    {
                        if (!json.valid)
                        {
                            clear_or_set_error_label(true, "copy_url", "No document at URL " + url);
                            reset_form();
                        }
                        else if (json.exists)
                        {
                            copy_exists(json);
                            validate_copy_exists();
                            reset_form();
                        }
                        else
                        {
                            reset_exists();
                            set_copy(json);
                            set_bitsavers(json);
                            show_or_hide("copy_site")("site_fields");
                            set_company(json);
                            set_publication(json);
                            show("supersession_fields");
                        }
                    });
            }
            else
            {
                reset_form();
            }
            clear_errors();
        });

    $("#copy_site").change(
        function()
        {
            show_or_hide("copy_site")("site_fields");
            clear_errors();
        });

    $("#company_id").change(function()
        {
            show_hide_company_fields();
            clear_errors();
        });

    $("#supersession_search_keywords").change(search_for_supersessions);
    $("#supersession_old_pub").change(function()
        {
            $("#supersession_new_pub").val(-1);
        });
    $("#supersession_new_pub").change(function()
        {
            $("#supersession_old_pub").val(-1);
        });

    $("#pub_search_keywords").change(search_for_publications);

    $("#pub_pub_id").change(
        function()
        {
            var fn = show_or_hide("pub_pub_id");
            fn("pub_history_ph_title_field");
            fn("pub_history_ph_revision_field");
            fn("pub_history_ph_pub_type_field");
            fn("pub_history_ph_pub_date_field");
            fn("pub_history_ph_abstract_field");
            fn("pub_history_ph_part_field");
            fn("pub_history_ph_alt_part_field");
            fn("pub_history_ph_keywords_field");
            fn("pub_history_ph_notes_field");
            fn("pub_history_ph_amend_pub_field");
            fn("pub_history_ph_amend_serial_field");
            clear_errors();
        });

    $("input[name='next']").click(
        function(event)
        {
            try
            {
                if (!validate_data())
                {
                    $('.form_container').after('<p class="error">There is an error!</p>');
                    event.preventDefault();
                }
                else if (!confirm("Add this information?"))
                {
                    event.preventDefault();
                }
            }
            catch (e)
            {
                $('.form_container').after('<p>There was an exception!  FUCK!</p>'
                    + '<dl><dt>' + e.name + '</dt><dd>' + e.message + '</dd></dl>');
                event.preventDefault();
            }
        });

    var help_shown = { };
    $('img[id$="_help_button"]').each(function()
        {
            var id = $(this).attr('id').replace('_button', '');
            help_shown[id] = false;
            $(this).click(function()
            {
                (help_shown[id] ? hide : show)(id);
                help_shown[id] = !help_shown[id];
            });
        });
});
