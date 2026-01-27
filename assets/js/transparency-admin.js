jQuery(function ($) {

    function bindPdfUploader(button) {
        var targetId      = button.data("target");
        var $targetInput  = $("#" + targetId);
        var $fileNameSpan = button.siblings(".fcsd-transparency-file-name");
        var frame;

        button.on("click", function (e) {
            e.preventDefault();

            if (frame) {
                frame.open();
                return;
            }

            frame = wp.media({
                title: button.data("title") || "Selecciona un PDF",
                button: { text: button.data("button") || "Usar aquest PDF" },
                library: { type: "application/pdf" },
                multiple: false
            });

            frame.on("select", function () {
                var attachment = frame.state().get("selection").first().toJSON();
                $targetInput.val(attachment.id);
                $fileNameSpan.text(attachment.filename || attachment.title || "");
            });

            frame.open();
        });
    }

    $(".fcsd-upload-pdf").each(function () {
        bindPdfUploader($(this));
    });
});
