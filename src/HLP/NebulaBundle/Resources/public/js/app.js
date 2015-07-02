(function ($) {

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

    window.init_build_form = function () {
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
            $(this).find('.pkg-env-type').trigger('change');
        });

        $('.pkg-env-type').trigger('change');
    };
})(jQuery);