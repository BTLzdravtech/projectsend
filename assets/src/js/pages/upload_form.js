(function () {
  'use strict'

  admin.pages.uploadForm = function () {
    $(document).ready(
      function () {
        // Send a keep alive action every 1 minute
        setInterval(function () {
          var timestamp = new Date().getTime()
          $.ajax(
            {
              type: 'GET',
              cache: false,
              url: 'includes/ajax-keep-alive.php',
              data: 'timestamp=' + timestamp
            }
          )
        }, 1000 * 60)

        var uploader = $('#uploader').pluploadQueue()
        var fading

        $('form').submit(
          function (e) {
            if (uploader.files.length > 0) {
              uploader.bind('StateChanged', function () {
                if (uploader.files.length === (uploader.total.uploaded + uploader.total.failed)) {
                  $('form').unbind('submit')
                  $('#btn-submit').click()
                }
              })

              uploader.start()

              $('#btn-submit').hide()
              $('.message_uploading').fadeIn()

              uploader.bind('FileUploaded', function (up, file, info) {
                var obj = JSON.parse(info.response)
                var newFileField = '<input type="hidden" name="finished_files[]" value="' + obj.NewFileName + '" />'
                $('form').append(newFileField)
              })

              return false
            } else {
              var alertInfo = $('form').siblings('div.alert-info')
              var alertDanger = $('form').siblings('div.alert-danger')
              if (fading !== undefined) {
                clearTimeout(fading)
              }
              alertInfo.hide()
              alertDanger.hide()
              alertDanger.fadeIn('slow')
              fading = setTimeout(function () {
                alertInfo.hide()
                alertDanger.hide()
                alertInfo.fadeIn('slow')
              }, 3000)
            }

            return false
          }
        )

        window.onbeforeunload = function (e) {
          var event = e || window.event

          // if uploading
          if (uploader.state === 2) {
            // IE & Firefox
            if (event) {
              event.returnValue = json_strings.translations.upload_form.leave_confirm
            }

            // For Safari
            return json_strings.translations.upload_form.leave_confirm
          }
        }
      }
    )
  }
})()
