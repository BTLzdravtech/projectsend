(function () {
  'use strict'

  admin.parts.main = function () {
    $(document).ready(
      function () {
        $(document).ready(
          function () {
            $('input:first').focus()
          }
        )

        // Dismiss messages
        $('.message .close').on('click', function () {
          $(this).closest('.message').transition('fade')
        })

        window.resizeChosen = function () {
          $('.chosen-container').each(
            function () {
              $(this).attr('style', 'width: 100%')
            }
          )
        }

        window.prepare_sidebar = function () {
          var windowWidth = jQuery(window).width()
          if (windowWidth < 769) {
            $('.main_menu .active .dropdown_content').hide()
            $('.main_menu li').removeClass('active')

            if (!$('body').hasClass('menu_contracted')) {
              $('body').addClass('menu_contracted')
            }
          }
        }

        /**
         * Main side menu
         */
        // eslint-disable-next-line no-undef
        prepare_sidebar()

        // eslint-disable-next-line no-undef
        resizeChosen()

        $('.main_menu > li.has_dropdown .nav_top_level').on('click', function (e) {
          e.preventDefault()

          var parent = $(this).parents('.has_dropdown')
          if ($(parent).hasClass('active')) {
            $(parent).removeClass('active')
            $(parent).find('.dropdown_content').stop().slideUp()
          } else {
            if ($('body').hasClass('menu_contracted')) {
              $('.main_menu li').removeClass('active')
              $('.main_menu').find('.dropdown_content').stop().slideUp(100)
            }
            $(parent).addClass('active')
            $(parent).find('.dropdown_content').stop().slideDown()
          }
        })

        $('.toggle_main_menu').on('click', function (e) {
          e.preventDefault()

          var windowWidth = jQuery(window).width()
          if (windowWidth > 768) {
            $('body').toggleClass('menu_contracted')
            if ($('body').hasClass('menu_contracted')) {
              Cookies.set('menu_contracted', 'true', { expires: 365 })
              $('.main_menu li').removeClass('active')
              $('.main_menu').find('.dropdown_content').stop().hide()
            } else {
              Cookies.set('menu_contracted', 'false', { expires: 365 })
              $('.current_nav').addClass('active')
              $('.current_nav').find('.dropdown_content').stop().show()
            }
          } else {
            $('body').toggleClass('menu_hidden')
            $('.main_menu li').removeClass('active')

            if ($('body').hasClass('menu_hidden')) {
              // Cookies.set("menu_hidden", 'true', { expires: 365 } );
              $('.main_menu').find('.dropdown_content').stop().hide()
            } else {
              // Cookies.set("menu_hidden", 'false', { expires: 365 } );
            }
          }
        })

        /**
         * Used on the public link modal on both manage files and the upload results
         */
        $(document).on('click', '.public_link_copy', function (e) {
          $(this).select()
          var copied
          if (document.execCommand('copy')) {
            copied = '.copied'
          } else {
            copied = '.copied_not'
          }
          $(this).parents('.public_link_modal').find(copied).stop().fadeIn().delay(2000).fadeOut()
          $(this).mouseup(
            function () {
              $(this).unbind('mouseup')
              return false
            }
          )
        })

        /**
         * Common for all tables
         */
        $('#select_all').on('click', function () {
          var status = $(this).prop('checked')
          /**
           * Uncheck all first in case you used pagination
           */
          $('tr td input[type=checkbox].batch_checkbox').prop('checked', false)
          $('tr:visible td input[type=checkbox].batch_checkbox').prop('checked', status)
        })

        if ($.isFunction($.fn.footable)) {
          $('.footable').footable().find('> tbody > tr:not(.footable-row-detail):nth-child(even)').addClass('odd')
        }

        /**
         * Pagination
         */
        $('.go_to_page').on('click', 'button', function () {
          var _page = $('.go_to_page #page_number').data('link')
          var _pageNo = parseInt($('.go_to_page #page_number').val())
          if (typeof _pageNo === 'number') {
            _page = _page.replace('_pgn_', _pageNo)
          }
          window.location.href = _page
        })

        /**
         * Password generator
         */
        // eslint-disable-next-line no-undef
        var hdl = new Jen(true)
        hdl.hardening(true)

        $('body').on('click', '.btn_generate_password', function (e) {
          var target = $(this).parents('.form-group').find('.password_toggle')
          var minChars = $(this).data('min')
          var maxChars = $(this).data('max')
          $(target).val(hdl.password(minChars, maxChars))
        })

        /**
         * File editor
         */
        if ($.isFunction($.fn.datepicker)) {
          var todayDate = new Date().getDate()

          $('.date-container .date-field').datepicker(
            {
              format: 'dd-mm-yyyy',
              autoclose: true,
              todayHighlight: true,
              startDate: new Date(),
              // eslint-disable-next-line no-undef
              endDate: new Date(new Date().setDate(todayDate + expirationDays))
            }
          )
        }

        $('body').on('click', '.add-all', function () {
          var selector = $(this).parent().prev().prev('select')
          $(selector).find('option').each(
            function () {
              $(this).prop('selected', true)
            }
          )
          $(selector).trigger('chosen:updated')
          return false
        })

        $('body').on('click', '.remove-all', function () {
          var selector = $(this).parent().prev().prev('select')
          $(selector).find('option').each(
            function () {
              $(this).prop('selected', false)
            }
          )
          $(selector).trigger('chosen:updated')
          return false
        })

        /**
         * Misc
         */
        $('button').on('click', function () {
          $(this).blur()
        })

        /**
         * Modal: show a public file's URL
         */
        $('body').on('click', '.public_link', function (e) {
          $(document).psendmodal()
          var type = $(this).data('type')
          var id = $(this).data('id')
          var token = $(this).data('token')

          var linkBase
          var noteText
          if (type === 'group') {
            linkBase = json_strings.uri.public_group + '?'
            noteText = json_strings.translations.public_group_note
          } else if (type === 'file') {
            linkBase = json_strings.uri.public_download + '?'
            noteText = json_strings.translations.public_file_note
          }

          var content = '<div class="public_link_modal">' +
            '<strong>' + json_strings.translations.copy_click_select + '</strong>' +
            '<div class="copied">' + json_strings.translations.copy_ok + '</div>' +
            '<div class="copied_not">' + json_strings.translations.copy_error + '</div>' +
            '<div class="form-group">' +
            '<textarea class="input-large public_link_copy form-control" rows="4" readonly>' + linkBase + 'id=' + id + '&token=' + token + '</textarea>' +
            '</div>' +
            '<span class="note">' + noteText + '</span>' +
            '</div>'
          var title = json_strings.translations.public_url
          $('.modal_title span').html(title)
          $('.modal_content').html(content)
        })

        /**
         * Modal: show a multiple public file's URL
         */
        $('body').on('click', '.public_links', function (e) {
          $(document).psendmodal()
          var linkBase = json_strings.uri.public_download + '?'
          var noteText = json_strings.translations.public_file_note
          var uploader = $(this).data('name')
          var modalText = ''
          $('#uploaded_files_tbl').find('.public_link').each(
            function (i, element) {
              modalText += linkBase + 'id=' + $(element).data('id') + '&token=' + $(element).data('token') + '\n'
            }
          )

          var content = '<div class="public_link_modal">' +
            '<strong>' + json_strings.translations.copy_click_select + '</strong>' +
            '<div class="copied">' + json_strings.translations.copy_ok + '</div>' +
            '<div class="copied_not">' + json_strings.translations.copy_error + '</div>' +
            '<div class="form-group">' +
            '<textarea class="input-large public_link_copy form-control" rows="4" id="links" readonly>' +
            modalText +
            '</textarea>' +
            '</div>' +
            '<h3 class="note">' + noteText + '</h3>' +
            '<form name="email_links" id="email_links" class="form-horizontal" data-name="' + uploader + '">' +
            '<div class="form-group">' +
            '<label for="name" class="col-sm-4 control-label">' + json_strings.translations.send_links.email +
            '<i class="fa fa-question-circle-o fa-fw" aria-hidden="true" data-toggle="tooltip" data-placement="top" title="' + json_strings.translations.send_links.email_multiple + '"></i>' +
            '</label>' +
            '<div class="col-sm-8">' +
            '<input type="text" name="email" id="email" class="form-control required" required />' +
            '</div>' +
            '</div>' +
            '<div class="form-group">' +
            '<label for="name" class="col-sm-4 control-label">' + json_strings.translations.send_links.note + '</label>' +
            '<div class="col-sm-8">' +
            '<textarea class="input-large public_link_note form-control" rows="4" name="note" id="note"></textarea>' +
            '</div>' +
            '</div>' +
            '<div class="inside_form_buttons">' +
            '<button type="submit" class="btn btn-wide btn-primary">' + json_strings.translations.send_links.submit + '</button>' +
            '</div>' +
            '</form>' +
            '</div>'
          var title = json_strings.translations.public_url
          $('.modal_title span').html(title)
          $('.modal_content').html(content)
          $('#email_links').validate(
            {
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
              errorPlacement: function (error, element) {
                error.appendTo(element.closest('div'))
              }
            }
          )
          $('#email_links').on('submit', function (event) {
            event.preventDefault()
            var form = $(this)
            if (form.valid()) {
              $.ajax(
                {
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
                      // eslint-disable-next-line no-undef
                      removeModal()
                    }
                  }
                }
              )
            }
          })
        })

        // Edit file + upload form
        if ($.isFunction($.fn.chosen)) {
          $('.chosen-select').chosen(
            {
              no_results_text: json_strings.translations.no_results,
              width: '100%',
              search_contains: true
            }
          )
        }

        // CKEditor
        if (typeof CKEDITOR !== 'undefined') {
          CKEDITOR.replaceAll('.ckeditor')
          for (var i in CKEDITOR.instances) {
            (function (i) {
              CKEDITOR.instances[i].on('change', function () {
                CKEDITOR.instances[i].updateElement()
              })
            })(i)
          }
        }
      }
    )

    /**
     * Event: Scroll
     */
    jQuery(window).scroll(
      function (event) {
      }
    )

    /**
     * Event: Resize
     */
    jQuery(window).resize(
      function ($) {
        // eslint-disable-next-line no-undef
        prepare_sidebar()

        // eslint-disable-next-line no-undef
        resizeChosen()
      }
    )
  }
})()
