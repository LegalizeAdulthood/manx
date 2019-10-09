String.prototype.trim = function()
{
    return this.replace(/^\s+|\s+$/g, "");
};

$(function()
{
    function show(id)
    {
        $("#" + id).removeClass("hidden");
    }

    function hide(id)
    {
        $("#" + id).addClass("hidden");
    }

    function first_item_selected(id_field)
    {
        return $("#" + id_field).val() == -1;
    }

    function show_or_hide(id_field)
    {
        return first_item_selected(id_field) ? show : hide;
    }

    function build_option(val, text)
    {
        return '<option value="' + val + '">' + text + '</option>';
    }

    function build_option_list(id, new_text, transformer, json)
    {
        var options = [build_option(-1, new_text)];
        for (var i = 0; i < json.length; ++i)
        {
            options.push(transformer(json[i]));
        }
        $("#" + id).html(options.join(''));
    }

    function clear_or_set_error_label(error, id, message)
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
    }

    function validate_field_non_empty(input_id)
    {
        return clear_or_set_error_label(
            $("#" + input_id).val().trim().length == 0,
            input_id,
            'This value is required and cannot be empty.');
    }

    function validate_field_non_empty_lower_case(input_id)
    {
        var value = $("#" + input_id).val().trim();
        return clear_or_set_error_label(
            (value.length === 0) || (value.match(/^[^A-Z]*$/) === null),
            input_id,
            'This value is required, cannot be empty and must be lower case.');
    }

    function validate_combo_box(combo_id, field_validator)
    {
        return ($("#" + combo_id).val() != -1) || field_validator();
    }

    function set_copy(data)
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
    }
    function reset_copy()
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
    }
    function copy_exists(json)
    {
        var copy_url = $("#copy_url");
        copy_url.data('exists', json.exists);
        copy_url.data('company', json.company);
        copy_url.data('pub_id', json.pub_id);
        copy_url.data('title', json.title);
    }
    function reset_exists()
    {
        var copy_url = $("#copy_url");
        copy_url.removeData('exists');
        copy_url.removeData('company');
        copy_url.removeData('pub_id');
        copy_url.removeData('title');
    }
    function validate_copy_exists()
    {
        var copy_url = $("#copy_url");
        return clear_or_set_error_label(copy_url.data('exists'), "copy_url",
            'Manx already knows about <a href="details.php/'
                + copy_url.data('company') + ',' + copy_url.data('pub_id') + '">'
                + copy_url.data('title') + '</a>.');
    }
    function validate_copy()
    {
        var size = $("#copy_size").val();
        return validate_copy_exists()
            && validate_field_non_empty('copy_url')
            && validate_combo_box('copy_site', validate_site)
            && validate_field_non_empty('copy_format')
            && Number(size) == size;
    }

    function set_site_company_directory(data)
    {
        $("#site_company_directory").val(data.site_company_directory);
    }

    function reset_site()
    {
        $("#site_name").val('');
        $("#site_url").val('');
        $("#site_description").val('');
        $("#site_copy_base").val('');
        $("#site_low").val(false);
        $("#site_live").val(false);
    }
    function validate_site()
    {
        return validate_field_non_empty("site_name")
            && validate_field_non_empty("site_url")
            && validate_field_non_empty("site_description")
            && validate_field_non_empty("site_copy_base");
    }

    function set_company(data)
    {
        var elem = $('#company_id');
        var initial_data = elem.data();
        show("company_fields");
        elem.val(data.company);
        if ('initial' in initial_data)
        {
            elem.val(initial_data['initial']);
        }
        show_hide_company_fields();
    }
    function show_hide_company_fields()
    {
        var toggle = show_or_hide("company_id");
        toggle("company_name_field");
        toggle("company_name_field");
        toggle("company_short_name_field");
        toggle("company_sort_name_field");
        toggle("company_notes_field");
    }
    function reset_company()
    {
        hide("company_fields");
        $("#company_id").val(-1);
    }
    function validate_company()
    {
        return validate_field_non_empty("company_name")
            && validate_field_non_empty("company_short_name")
            && validate_field_non_empty_lower_case("company_sort_name");
    }

    function set_publication_initial_field(key)
    {
        var elem = $('#pub_history_ph_' + key);
        var data = elem.data();
        if ('initial' in data)
        {
            elem.val(data['initial']);
        }
    }

    function set_publication_initial_data()
    {
        set_publication_initial_field('title');
        set_publication_initial_field('pub_date');
        set_publication_initial_field('part');
        set_publication_initial_field('abstract');
    }

    function set_publication(data)
    {
        var keywords = (data.part + ' ' + data.title).trim();
        show("publication_fields");
        $("#pub_history_ph_title").val(data.title);
        $("#pub_history_ph_pub_date").val(data.pub_date);
        $("#pub_history_ph_part").val(data.part);
        set_publication_initial_data();

        $("#pub_search_keywords").val(keywords);
        search_for_publications();

        $("#supersession_search_keywords").val(keywords);
        search_for_supersessions();
    }
    function reset_publication()
    {
        hide("publication_fields");
        $("#pub_history_ph_title").val('');
        $("#pub_history_ph_pub_date").val('');
        $("#pub_history_ph_part").val('');

        $("#pub_search_keywords").val('');

        $("#supersession_search_keywords").val('');
    }
    function validate_publication()
    {
        function validate_title_part_number()
        {
            var title = $("#pub_history_ph_title").val().toLowerCase().trim();
            var part = $("#pub_history_ph_part").val().toLowerCase().trim();
            return clear_or_set_error_label(
                (part.length > 0) && (title.indexOf(part) != -1),
                'pub_history_ph_title',
                'The title cannot contain the part number.');
        }
        return validate_field_non_empty("pub_history_ph_title")
            && validate_title_part_number();
    }

    function set_pub_list(id, new_text, json)
    {
        build_option_list(id, new_text,
            function(item)
            {
                return build_option(item.pub_id,
                    item.ph_part + ' ' + item.ph_revision + ' ' + item.ph_title);
            },
            json);
    }

    function set_supersessions(json)
    {
        function set_supersession_pub(id)
        {
            set_pub_list(id, "(None)", json);
        }
        set_supersession_pub("supersession_old_pub");
        set_supersession_pub("supersession_new_pub");
    }
    function reset_supersessions()
    {
        function reset_option_list(id)
        {
            build_option_list(id, "(None)", null, []);
        }
        reset_option_list("supersession_old_pub");
        reset_option_list("supersession_new_pub");
    }
    function validate_supersession()
    {
        return true;
    }

    function set_publication_search_results(json)
    {
        set_pub_list("pub_pub_id", "(New Publication)", json);
    }
    function reset_publication_search_results()
    {
        build_option_list("pub_pub_id", "(New Publication)", null, []);
    }

    function wizard_service(data, callback)
    {
        $.post("url-wizard-service.php", data, callback, "json");
    }

    function ajax_error_handler(error_id)
    {
        return function(e, response, settings, exception)
        {
            if (settings.data.indexOf('error_id=' + error_id + '&') == 0)
            {
                show(error_id);
                $(this).html(response.responseText);
            }
        };
    }

    function pub_search(id_base, callback)
    {
        var company_id = $("#company_id").val();
        var error_id = id_base + '_error';
        var working_id = id_base + '_working';
        if (company_id != -1)
        {
            $("#" + error_id).ajaxError(ajax_error_handler(error_id));
            show(working_id);
            wizard_service(
                {
                    'error_id': error_id,
                    'method': "pub-search",
                    'company': $("#company_id").val(),
                    'keywords': $("#" + id_base).val()
                },
                function(json)
                {
                    callback(json);
                    hide(working_id);
                });
        }
    }

    function search_for_publications()
    {
        pub_search('pub_search_keywords', set_publication_search_results);
    }

    function search_for_supersessions()
    {
        pub_search('supersession_search_keywords', set_supersessions);
    }

    function clear_errors()
    {
        $('div[id$="_error"]').addClass('hidden');
        $("label").removeClass('error');
    }

    function validate_data()
    {
        clear_errors();
        return validate_copy()
            && validate_combo_box('company_id', validate_company)
            && validate_combo_box('pub_pub_id', validate_publication)
            && validate_combo_box("supersession_old_pub", validate_supersession);
    }

    function reset_form()
    {
        reset_copy();
        reset_site();
        reset_company();
        reset_publication();
        hide("supersession_fields");
        reset_publication_search_results();
        reset_supersessions();
    }

    function show_hide_details_link(id_field)
    {
        var link_id = id_field + '_link';
        var label_id = id_field + '_label';
        if (first_item_selected(id_field))
        {
            $('#' + link_id).removeAttr('href');
            show(label_id);
            hide(link_id);
        }
        else
        {
            $('#' + link_id).attr('href',
                'details.php/' + $('#company_id').val() + ',' + $('#' + id_field).val());
            ;
            hide(label_id);
            show(link_id);
        }
    }

    function url_lookup()
    {
        var url = $("#copy_url").val();
        if (url.length > 0)
        {
            show("copy_url_working");
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
                        set_site_company_directory(json);
                        show_or_hide("copy_site")("site_fields");
                        set_company(json);
                        set_publication(json);
                        show("supersession_fields");
                    }
                    hide("copy_url_working");
                });
        }
        else
        {
            reset_form();
        }
        clear_errors();
    }

    $("#copy_url_error").ajaxError(ajax_error_handler('copy_url_error'));
    $("#copy_url").change(url_lookup);

    $("#copy_site").change(function()
        {
            show_or_hide("copy_site")("site_fields");
            clear_errors();
        });

    $("#company_id").change(function()
        {
            show_hide_company_fields();
            clear_errors();
            search_for_publications();
            search_for_supersessions();
        });

    $("#supersession_search_keywords").change(search_for_supersessions);
    $("#supersession_old_pub").change(function()
        {
            $("#supersession_new_pub").val(-1);
            show_hide_details_link('supersession_old_pub');
            show_hide_details_link('supersession_new_pub');
        });
    $("#supersession_new_pub").change(function()
        {
            $("#supersession_old_pub").val(-1);
            show_hide_details_link('supersession_old_pub');
            show_hide_details_link('supersession_new_pub');
        });

    $("#pub_search_keywords").change(search_for_publications);

    $("#pub_pub_id").change(function()
        {
            var id_field = 'pub_pub_id';
            var toggle = show_or_hide(id_field);
            toggle("pub_history_ph_title_field");
            toggle("pub_history_ph_revision_field");
            toggle("pub_history_ph_pub_type_field");
            toggle("pub_history_ph_pub_date_field");
            toggle("pub_history_ph_abstract_field");
            toggle("pub_history_ph_part_field");
            toggle("pub_history_ph_alt_part_field");
            toggle("pub_history_ph_keywords_field");
            toggle("pub_history_ph_notes_field");
            toggle("pub_history_ph_amend_pub_field");
            toggle("pub_history_ph_amend_serial_field");
            clear_errors();
            show_hide_details_link(id_field);
        });

    $("input[name='next']").click(function(event)
        {
            var next = $("input[name='next']");
            function cancel()
            {
                event.preventDefault();
                next.show();
            }
            try
            {
                next.hide();
                if (!validate_data())
                {
                    $('.form_container').after('<p class="error">There is an error!</p>');
                    cancel();
                }
                else if (!confirm("Add this information?"))
                {
                    cancel();
                }
            }
            catch (e)
            {
                $('.form_container').after('<p>There was an exception!</p>'
                    + '<dl><dt>' + e.name + '</dt><dd>' + e.message + '</dd></dl>');
                cancel();
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
