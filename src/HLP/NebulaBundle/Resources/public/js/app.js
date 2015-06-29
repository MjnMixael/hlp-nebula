(function ($) {

    window.init_converter = function (server, ticket, owner) {
        if(!window.TaskWatcher) {
            $('#progress-container').html("<p>
                <strong>Error:</strong> I can't reach the conversion server! Either your browser is blocking the request
                (i.e. if you block third-party scripts) or there's some other reason.
            </p>
            <p>
                This means that you can't watch the conversion process. Just switch to the &quot;Details&quot; tab and refresh until it's done.
            </p>");
            return;
        }

        var cv = new TaskWatcher(server, ticket, owner);
        cv.bootstrap_ui($('#progress-container'));
        cv.on('done', function (success) {
            $('#finaliseButton').removeAttr('disabled');
        });
        cv.on('task_status', function (status) {
            if(status == 'missing') {
                location.href = '../';
            }
        })
        cv.connect();
    };


    window.init_pkg_files = function () {
        var archives = {};

        $('.pkg-archive').each(function () {
            var $this = $(this);

            archives[$this.data('archive')] = $this.html();
        }).remove();

        $('.pkg-file').popover({
            placement: 'top',
            html: true,
            content: function () {
                return archives[$(this).data('archive')];
            }
        });
    };
})(jQuery);