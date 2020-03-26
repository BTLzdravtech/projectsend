// eslint-disable-next-line no-unused-vars
var dataExtraction = function (node) {
  if (node.childNodes.length > 1) {
    return node.childNodes[1].innerHTML;
  } else {
    return node.innerHTML;
  }
};

/**
 * CLOSE THE ZIP DOWNLOAD MODAL
 * Solution to close the modal. Suggested by remez, based on
 * https://stackoverflow.com/questions/29532788/how-to-display-a-loading-animation-while-file-is-generated-for-download
 */
// eslint-disable-next-line no-unused-vars
var downloadTimeout;
// eslint-disable-next-line no-unused-vars
var checkDownloadCookie = function () {
  if (Cookies.get('download_started') === 1) {
    Cookies.set('download_started', 'false', { expires: 100 });
    // eslint-disable-next-line no-undef
    removeModal();
  } else {
    downloadTimeout = setTimeout(checkDownloadCookie, 1000);
  }
};

// Close the log CSV download modal
// eslint-disable-next-line no-unused-vars
var logdownloadTimeout;
// eslint-disable-next-line no-unused-vars
var checkLogDownloadCookie = function () {
  if (Cookies.get('log_download_started') === 1) {
    Cookies.set('log_download_started', 'false', { expires: 100 });
    // eslint-disable-next-line no-undef
    removeModal();
  } else {
    logdownloadTimeout = setTimeout(checkLogDownloadCookie, 1000);
  }
};
