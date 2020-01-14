(function () {
    'use strict';

    admin.parts.main = function () {

        $(document).ready(function() {
            $(document).ready(function() {
                $('input:first').focus();
            });
    
            // Dismiss messages
            $('.message .close').on('click', function () {
                $(this).closest('.message').transition('fade');
            });
        
            window.resizeChosen = function() {
                $(".chosen-container").each(function() {
                    $(this).attr('style', 'width: 100%');
                });
            }
            
            window.prepare_sidebar = function() {
                var window_width = jQuery(window).width();
                if ( window_width < 769 ) {
                    $('.main_menu .active .dropdown_content').hide();
                    $('.main_menu li').removeClass('active');
            
                    if ( !$('body').hasClass('menu_contracted') ) {
                        $('body').addClass('menu_contracted');
                    }
                }
            }

            /** Main side menu */
            prepare_sidebar();

            resizeChosen();

            $('.main_menu > li.has_dropdown .nav_top_level').click(function(e) {
                e.preventDefault();

                var parent = $(this).parents('.has_dropdown');
                if ( $(parent).hasClass('active') ) {
                    $(parent).removeClass('active');
                    $(parent).find('.dropdown_content').stop().slideUp();
                }
                else {
                    if ( $('body').hasClass('menu_contracted') ) {
                        $('.main_menu li').removeClass('active');
                        $('.main_menu').find('.dropdown_content').stop().slideUp(100);
                    }
                    $(parent).addClass('active');
                    $(parent).find('.dropdown_content').stop().slideDown();
                }
            });

            $('.toggle_main_menu').click(function(e) {
                e.preventDefault();

                var window_width = jQuery(window).width();
                if ( window_width > 768 ) {
                    $('body').toggleClass('menu_contracted');
                    if ( $('body').hasClass('menu_contracted') ) {
                        Cookies.set("menu_contracted", 'true', { expires: 365 } );
                        $('.main_menu li').removeClass('active');
                        $('.main_menu').find('.dropdown_content').stop().hide();
                    }
                    else {
                        Cookies.set("menu_contracted", 'false', { expires: 365 } );
                        $('.current_nav').addClass('active');
                        $('.current_nav').find('.dropdown_content').stop().show();
                    }
                }
                else {
                    $('body').toggleClass('menu_hidden');
                    $('.main_menu li').removeClass('active');

                    if ( $('body').hasClass('menu_hidden') ) {
                        //Cookies.set("menu_hidden", 'true', { expires: 365 } );
                        $('.main_menu').find('.dropdown_content').stop().hide();
                    }
                    else {
                        //Cookies.set("menu_hidden", 'false', { expires: 365 } );
                    }
                }
            });

            /** Used on the public link modal on both manage files and the upload results */
            $(document).on('click', '.public_link_copy', function(e) {
                $(this).select();
                if ( document.execCommand("copy") ) {
                    var copied = '.copied';
                }
                else {
                    var copied = '.copied_not';
                }
                $(this).parents('.public_link_modal').find(copied).stop().fadeIn().delay(2000).fadeOut();
                $(this).mouseup(function() {
                    $(this).unbind("mouseup");
                    return false;
                });
            });


            /** Common for all tables */
            $("#select_all").click(function(){
                var status = $(this).prop("checked");
                /** Uncheck all first in case you used pagination */
                $("tr td input[type=checkbox].batch_checkbox").prop("checked",false);
                $("tr:visible td input[type=checkbox].batch_checkbox").prop("checked",status);
            });

            if ( $.isFunction($.fn.footable) ) {
                $('.footable').footable().find('> tbody > tr:not(.footable-row-detail):nth-child(even)').addClass('odd');
            }
            
            /** Pagination */
            $(".go_to_page").on("click", "button", function() {
                var _page = $('.go_to_page #page_number').data('link');
                var _page_no = parseInt($('.go_to_page #page_number').val());
                if (typeof _page_no == 'number'){
                    _page = _page.replace('_pgn_', _page_no);
                }
                window.location.href = _page;
            });

            /** Password generator */
            var hdl = new Jen(true);
            hdl.hardening(true);

            $('body').on('click', '.btn_generate_password', function(e) {
                var target = $(this).parents('.form-group').find('.password_toggle');
                var min_chars = $(this).data('min');
                var max_chars = $(this).data('max');
                $(target).val( hdl.password( min_chars, max_chars ) );
            });


            /** File editor */
            if ( $.isFunction($.fn.datepicker) ) {
                var todayDate = new Date().getDate();

                $('.date-container .date-field').datepicker({
                    format			: 'dd-mm-yyyy',
                    autoclose		: true,
                    todayHighlight	: true,
                    startDate: new Date(),
                    endDate: new Date(new Date().setDate(todayDate + expiration_days))
                });
            }

            $('body').on('click', '.add-all', function(){
                var selector = $(this).parent().prev().prev('select');
                $(selector).find('option').each(function(){
                    $(this).prop('selected', true);
                });
                $(selector).trigger('chosen:updated');
                return false;
            });

            $('body').on('click', '.remove-all', function(){
                var selector = $(this).parent().prev().prev('select');
                $(selector).find('option').each(function(){
                    $(this).prop('selected', false);
                });
                $(selector).trigger('chosen:updated');
                return false;
            });


            /** Misc */
            $('button').click(function() {
                $(this).blur();
            });

            /**
             * Modal: show a public file's URL
             */
            $('body').on('click', '.public_link', function(e) {
                $(document).psendmodal();
                var type	= $(this).data('type');
                var id		= $(this).data('id');
                var token	= $(this).data('token');

                if ( type == 'group' ) {
                    var link_base = json_strings.uri.public_group + '?';
                    var note_text = json_strings.translations.public_group_note;
                }
                else if ( type == 'file' ) {
                    var link_base = json_strings.uri.public_download + '?';
                    var note_text = json_strings.translations.public_file_note;
                }

                var content =  '<div class="public_link_modal">'+
                    '<strong>'+json_strings.translations.copy_click_select+'</strong>'+
                    '<div class="copied">'+json_strings.translations.copy_ok+'</div>'+
                    '<div class="copied_not">'+json_strings.translations.copy_error+'</div>'+
                    '<div class="form-group">'+
                    '<textarea class="input-large public_link_copy form-control" rows="4" readonly>' + link_base + 'id=' + id + '&token=' + token + '</textarea>'+
                    '</div>'+
                    '<span class="note">' + note_text + '</span>'+
                    '</div>';
                var title 	= json_strings.translations.public_url;
                $('.modal_title span').html(title);
                $('.modal_content').html(content);
            });

            /**
             * Modal: show a multiple public file's URL
             */
            $('body').on('click', '.public_links', function(e) {
                $(document).psendmodal();
                var link_base = json_strings.uri.public_download + '?';
                var note_text = json_strings.translations.public_file_note;
                var uploader = $(this).data('name');
                var modalText = '';
                $('#uploaded_files_tbl').find('.public_link').each(function(i, element){
                    modalText += link_base + 'id=' + $(element).data('id') + '&token=' + $(element).data('token') + '\n';
                });

                var content = '<div class="public_link_modal">' +
                                '<strong>'+json_strings.translations.copy_click_select+'</strong>' +
                                '<div class="copied">'+json_strings.translations.copy_ok+'</div>' +
                                '<div class="copied_not">'+json_strings.translations.copy_error+'</div>' +
                                '<div class="form-group">' +
                                    '<textarea class="input-large public_link_copy form-control" rows="4" id="links" readonly>' +
                                        modalText +
                                    '</textarea>' +
                                '</div>' +
                                '<h3 class="note">' + note_text + '</h3>'+
                                '<form name="email_links" id="email_links" class="form-horizontal" data-name="' + uploader + '">' +
                                    '<div class="form-group">' +
                                        '<label for="name" class="col-sm-4 control-label">'+json_strings.translations.send_links.email+
                                            '<i class="fa fa-question-circle-o fa-fw" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="' + json_strings.translations.send_links.email_multiple + '"></i>' +
                                        '</label>' +
                                        '<div class="col-sm-8">' +
                                            '<input type="text" name="email" id="email" class="form-control required" required />' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="form-group">' +
                                        '<label for="name" class="col-sm-4 control-label">'+json_strings.translations.send_links.note+'</label>' +
                                        '<div class="col-sm-8">' +
                                            '<textarea class="input-large public_link_note form-control" rows="4" name="note" id="note"></textarea>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="inside_form_buttons">' +
                                        '<button type="submit" class="btn btn-wide btn-primary">'+json_strings.translations.send_links.submit+'</button>' +
                                    '</div>' +
                                '</form>' +
                              '</div>';
                var title = json_strings.translations.public_url;
                $('.modal_title span').html(title);
                $('.modal_content').html(content);
                var validator = $("#email_links").validate({
                    rules: {
                        email: {
                            required: true
                        }
                    },
                    messages: {
                        email: {
                            required: json_strings.validation.no_email
                        }
                    },
                    errorPlacement: function(error, element) {
                        error.appendTo(element.closest('div'));
                    }
                });
                $("#email_links").on('submit', function(event) {
                    event.preventDefault();
                    var form = $(this);
                    if (form.valid()) {
                        $.ajax({
                            url: 'ajax/send_public_links.php',
                            cache: false,
                            data: {
                                email: $('#email').val(),
                                note: $('#note').val(),
                                links: $('#links').val(),
                                uploader: form.data('name')
                            },
                            success: function (response) {
                                if (response.emails_send) {
                                    remove_modal();
                                }
                            },
                        });
                    }
                });
            });
            
            // Edit file + upload form
            if ( $.isFunction($.fn.chosen) ) {
                $('.chosen-select').chosen({
                    no_results_text	: json_strings.translations.no_results,
                    width			: "100%",
                    search_contains	: true
                });
            }

            // CKEditor
            if ( typeof CKEDITOR !== "undefined" ) {
                CKEDITOR.replaceAll( '.ckeditor' );
                for (var i in CKEDITOR.instances) {
                    CKEDITOR.instances[i].on('change', function() { CKEDITOR.instances[i].updateElement() });
                }
            }

        });

        /**
         * Event: Scroll
         */
        jQuery(window).scroll(function(event) {
        });

        /**
         * Event: Resize
         */
        jQuery(window).resize(function($) {
            prepare_sidebar();

            resizeChosen();
        });
    };
})();