(function ($) {
    function init_uploader(ticket_point, upload_point, pkg_urls, $this) {
        var up_button = $('<button class="btn btn-default uploadButton" type="button" style="width: 100%;">Upload a file</button>');
        var up_bar = null;
        var up_input = null;
        var uploader;

        pkg_urls.after(up_button);

        function init() {
            uploader = new plupload.Uploader({
                browse_button: up_button[0],
                drop_element: up_button[0],
                url: upload_point,

                runtimes: 'html5,html4',
                multi_selection: false
            });
            uploader.init();
            uploader.bind('FilesAdded', function (up, files) {
                if(uploader.files.length > 1) {
                    uploader.splice(1);
                }

                if(up_bar) up_bar.parent().remove();

                var bar = $('<div class="progress"><div class="progress-bar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">Connecting...</div></div>');
                up_bar = bar.find('.progress-bar');
                up_button.after(bar);

                uploader.disableBrowse(true);
                $.get(ticket_point, function (ticket) {
                    uploader.setOption('url', upload_point + '/' + ticket);
                    uploader.start();
                    up_bar.addClass('active');
                }).error(function () {
                    up_bar.parent().remove();
                    uploader.disableBrowse(false);
                });
            });
            uploader.bind('UploadProgress', function (up, file) {
                up_bar.css('width', file.percent + '%').text(file.percent + '%').attr('aria-valuenow', file.percent);
            });
            uploader.bind('FileUploaded', function (up, file, resp) {
                var res;
                try {
                    res = JSON.parse(resp.response);
                } catch(e) {
                    res = null;
                }

                if(!res || res.error || !res.url) {
                    up_bar
                        .removeClass('active')
                        .css('width', '100%')
                        .addClass('progress-bar-danger')
                        .text(res && res.error ? res.message : 'Unknown error!');
                } else if(res.url) {
                    var input = $this.find('.pkg-file-url input.uploaded');
                    if(input.length < 1) {
                        $this.find('.pkg-file-url .addButton').click();

                        input = $this.find('.pkg-file-url input.form-control:last');
                        input.addClass('uploaded')
                    }

                    input.val(res.url);
                    input.parents('.cardspacer').find('.pkg-file-name').val(file.name);
                    up_bar.parent().remove();
                }

                uploader.removeFile(file);
                uploader.disableBrowse(false);
            });
            uploader.bind('Error', function (up, err) {
                if(window.console) console.log(err);
                if(err.file) {
                    up_bar
                        .removeClass('active')
                        .css('width', '100%')
                        .addClass('progress-bar-danger')
                        .text(err.message);

                    uploader.destroy();
                    init();
                }
            });
        }

        init();
    }

    window.init_converter = function (server, ticket, owner) {
        if(!window.TaskWatcher) {
            $('#progress-container').html(
                "<p>"+
                    "<strong>Error:</strong> I can't reach the conversion server! Either your browser is blocking the request"+
                    "(i.e. if you block third-party scripts) or there is some other reason."+
                "</p>"+
                "<p>"+
                    "This means that you can't watch the conversion process. Just switch to the &quot;Details&quot; tab and refresh until it's done."+
                "</p>"
            );
            return;
        }

        var cv = new TaskWatcher(server, ticket, owner);
        cv.bootstrap_ui($('#progress-container'));
        cv.on('done', function (success) {
            $('#finaliseButton').removeAttr('disabled');
        });
        cv.on('task_status', function (status) {
            if(status == 'missing') {
                location.href = './';
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

    window.init_build_form = function (ticket_point, upload_point) {
        var uploader = null;

        $('form').on('blur', '.pkg-file-url input', function (e) {
            var url = $(this).val();
            var name_field = $(this).parents('.well').find('input.pkg-file-name');

            console.log([url, name_field])

            if(name_field.val() == '') {
                name_field.val(url.split('/').pop());
            }
        })
        .on('change', '.pkg-env-type', function (e) {
            var $this = $(this);
            var val_field = $this.parents('.well').find('.pkg-env-value');
            var rep, choices = null;

            switch($this.val()) {
                case 'os':
                    choices = ['windows', 'linux', 'macos'];
                    break;

                case 'cpu_feature':
                    choices = [
                        'x86_64','x86_32',
                        'sse','sse2','avx','sse4_1','sse4_2','ssse3',
                        'acpi','aes','apic','cid','clflush','cmov','cx16','cx8','dca','de','ds_cpl','dtes64','dts','est','f16c','fma','fpu','fxsr','ht','hypervisor','ia64','mca','mce','mmx','monitor','movbe','msr','mtrr','osxsave','pae','pat','pbe','pcid','pclmulqdq','pdcm','pge','pn','pni','popcnt','pse','pse36','rdrnd','sep','smx','ss','tm','tm2','tsc','tscdeadline','vme','vmx','x2apic','xsave','xtpr'
                    ];
                    break;

                default:
                    rep = $('<input type="text" required>');
            }

            if(choices) {
                rep = $('<select>');
                $.each(choices, function (i, opt) {
                    rep.append($('<option>').text(opt).prop('value', opt));
                });
            }

            $.each(['id', 'class', 'name'], function (i, attr) {
                rep.attr(attr, val_field.attr(attr));
            });

            rep.val(val_field.val());
            val_field.replaceWith(rep);
        })
        .on('field-added', function (e) {
            var $this = $(e.target);
            var pkg_urls = $this.find('.pkg-file-url .addButton');

            $this.find('.pkg-env-type').trigger('change')

            if(pkg_urls.length > 0) {
                init_uploader(ticket_point, upload_point, pkg_urls, $this);
            }
        });

        $('.pkg-env-type').trigger('change');
    };
})(jQuery);