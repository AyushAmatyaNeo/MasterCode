(function ($, app) {
    'use strict';
    $(document).ready(function () {
    
        $('select').select2();
        var summernotes = [];

        var cctemplate = ' <div class="row"> <div class="col-sm-6"> <div class="form-group"> <input name="ccEmail[]" type="text" class="form-control" placeholder="Email" value=""> </div></div><div class="col-sm-6"> <div class="form-group"> <input name="ccName[]" type="text" class="form-control" placeholder="Name" value=""> </div></div></div>';

        $('.summernote')
            .each(function () {
                var $this = $(this);
                var temp = decodeURIComponent($this.parent().find("input[name='body']").val());
                $this.summernote({height: 300,
                    minHeight: null,
                    maxHeight: null,
                    focus: true
                });
                $this.summernote('code', temp);
                summernotes.push($this);
        });
        for(var i = 0; i < 43; i++){
            summernotes.push(summernotes[0]);
        }
        // $('.btnAddCC').each(function () {
        //     var $this = $(this);
        //     $this.on('click', function () {
        //         $(this).parent().parent().parent().append(cctemplate);
    
        //     });
        // });
        
        $('.form').each(function () {
            var $this = $(this);
            $this.submit(function (e) {
                $this = $(this);
                var summernote = $this.find('.summernote');
                var message = $this.find("input[name='body']");
                $(message).val(summernote.summernote('code'));
                console.log(message.val());
                // form validation start
                if (message.val() == "" || message.val() == " ") {
                    var parentId = message.parent(".form-group");
                    var errorMsgSpan = parentId.find('span.errorMsg');
                    console.log(errorMsgSpan.length);
                    if (errorMsgSpan.length == 0) {
                        var errorMsgSpan = $('<span />', {
                            "class": 'errorMsg',
                            text: 'Body is required'
                        });
                        parentId.append(errorMsgSpan);
                        message.focus();
                    }
                    return false;
                }
                // form validation end
                return true;
            });
        });

        $('.variables').each(function () {
            var $this = $(this);
             $this.on('click', function () {
                var $self = $(this);
                var data = $self.attr('data');
                var index = $self.attr('index');
                summernotes[index].summernote('insertText', '[' + data + ']');
            });

        });
            
    });

    
})(window.jQuery, window.app);