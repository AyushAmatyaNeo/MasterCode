(function ($, app) {
    'use strict';
    $(document).ready(function () {
        $("select").select2();
        var fiscalYears = document.fiscalYears;
        var flatValues = document.flatValues;
        var positions = document.positions;
        // console.log(flatValues);
        var $fiscalYearId = $("#fiscalYearId");

        var $assignFlatValueBtn = $('#assignFlatValueBtn');

        var $grid = $('#flatValueDetailGrid');
        var $header = $('#flatValuesDetailHeader');
        var $table = $('#flatValueDetailTable');
        var $footer = $('#flatValueDetailFooter');
        var $flatId = $('#flatId');


        app.populateSelect($fiscalYearId, fiscalYears, "FISCAL_YEAR_ID", "FISCAL_YEAR_NAME", "Select Fiscal Year");
        app.populateSelect($flatId, flatValues, "FLAT_ID", "FLAT_EDESC", "Select Flat Value");

        var pullData = function (fiscalYearId, fn) {
            app.pullDataById(document.getPositionFlatLink, {fiscalYearId: fiscalYearId}).then(function (response) {
                fn(response.data);
            }, function (error) {

            });
        };
        $fiscalYearId.on('change', function () {
            var value = $(this).val();
            if (value == -1) {
                return;
            }
            pullData(value, function (data) {
                initTable(flatValues, positions, data);
            });

        });

        var findFlatValue = function (serverData, positionId, flatId) {
            var result = serverData.filter(function (item) {
                return item['POSITION_ID'] == positionId;
            });

            if (result.length > 0) {
                return result[0][flatId];
            } else {
                return null;
            }
        };

        var initTable = function (flatValues, positions, data) {
            $header.html('');
            $header.append($('<th>', {text: 'Level'}));
            $header.append($('<th>', {text: 'Name'}));
            $.each(flatValues, function (index, item) {
                $header.append($('<th>', {id: item['FLAT_ID'], text: item['FLAT_EDESC']}));
            });
            $header.append($('<th>', {text: ''}));

            $grid.html('');
            $.each(positions, function (index, item) {
                var $tr = $('<tr>');

                $tr.append($('<td>', {text: item['LEVEL_NO']}));
                $tr.append($('<td>', {text: item['POSITION_NAME']}))

                $.each(flatValues, function (k, v) {
                    var $td = $('<td>');
                    $td.append($('<input>', {type: 'number', row: item['POSITION_ID'], col: v['FLAT_ID'], value: findFlatValue(data, item['POSITION_ID'], v['FLAT_ID']), class: 'form-control'}));
                    $tr.append($td);
                });
                var $td = $('<td>');
                $td.append($('<input>', {type: 'number', row: item['POSITION_ID'], class: 'group form-control'}));
                $tr.append($td);

                $grid.append($tr);
            });

            $footer.html('');
            var $tr = $('<tr>');

            $tr.append($('<td>', {text: ''}));
            $tr.append($('<td>', {text: ''}))

            $.each(flatValues, function (k, v) {
                var $td = $('<td>');
                $td.append($('<input>', {type: 'number', col: v['FLAT_ID'], class: 'group form-control'}));
                $tr.append($td);
            });
            var $td = $('<td>');
            $td.append($('<input>', {type: 'number', class: 'group form-control'}));
            $tr.append($td);

            $footer.append($tr);
            $table.bootstrapTable({height: 400});
        };

        $table.on('change', '.group', function () {
            var $this = $(this);
            var value = $this.val();
            var row = $this.attr('row');
            if (typeof row !== typeof undefined && row !== false) {
                $('input[row=' + row + ']').val(value);
            }
            var col = $this.attr('col');
            if (typeof col !== typeof undefined && col !== false) {
                $('input[col=' + col + ']').val(value);
            }

            if ((typeof row === "undefined" || row === false) && (typeof col === "undefined" || col === false)) {
                $.each($this.parent().parent().children(), function (key, item) {
                    var $td = $(item);
                    var $input = $td.find('input');
                    if ($input.attr('col')) {
                        $input.val(value);
                        $input.trigger('change');
                    }
                });
            }


        });

        $assignFlatValueBtn.on('click', function () {
            var fiscalYearId = $fiscalYearId.val();
            var promiseList = [];
            $.each($grid.find('input'), function (key, item) {
                var $item = $(item);
                var rowValue = $item.attr('row');
                var colValue = $item.attr('col');
                var value = $item.val();
                if (typeof rowValue !== "undefined" && rowValue != null && rowValue != "" && typeof colValue !== "undefined" && colValue != null && colValue != "" && typeof value !== "undefined" && value != null && value != "") {
                    promiseList.push(app.serverRequest(document.setPositionFlatValueLink, {
                        fiscalYearId: fiscalYearId,
                        positionId: rowValue,
                        flatId: colValue,
                        assignedValue: value
                    }));
                }

            });

            Promise.all(promiseList).then(function (response) {
                app.showMessage("Flat Value assigned successfully!!!");
            }, function (error) {
            });



        });



    });
})(window.jQuery, window.app);
