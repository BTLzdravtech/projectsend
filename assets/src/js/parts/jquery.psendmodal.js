/**
 * Very simple custom modal dialog
 */
$.fn.psendmodal = function () {
  var modalStructure = '<div class="modal_overlay"></div>' +
    '<div class="modal_psend">' +
    '<div class="modal_title">' +
    '<span>&nbsp;</span>' +
    '<a href="#" class="modal_close">&times;</a>' +
    '</div>' +
    '<div class="modal_content"></div>' +
    '</div>'

  $('body').append(modalStructure)
  showModal()

  function showModal () {
    $('.modal_overlay').stop(true, true).fadeIn()
    $('.modal_psend').stop(true, true).fadeIn()
  }

  window.removeModal = function () {
    $('.modal_overlay').stop(true, true).fadeOut(500, function () {
      $(this).remove()
    })
    $('.modal_psend').stop(true, true).fadeOut(500, function () {
      $(this).remove()
    })
    return false
  }

  $('.modal_close').on('click', function (e) {
    e.preventDefault()
    // eslint-disable-next-line no-undef
    removeModal()
  })

  $('.modal_overlay').on('click', function (e) {
    e.preventDefault()
    // eslint-disable-next-line no-undef
    removeModal()
  })

  $(document).keyup(
    function (e) {
      if (e.keyCode === 27) { // Esc
        // eslint-disable-next-line no-undef
        removeModal()
      }
    }
  )
}
