(function () {
    'use strict';

    admin.parts.bootboxInit = function () {

        $(document).ready(function(){
            bootbox.setDefaults({
                closeButton: false,
                swapButtonOrder: true
            });
        });
    };
})();