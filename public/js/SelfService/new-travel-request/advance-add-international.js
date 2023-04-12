(function ($, app) {
    
    $(document).ready(function () {
        all_data=document.currencyList;
        app.populateSelect($('.currency'), all_data, 'code', 'code', '-select-',null, 1, true);

    });
    
})(window.jQuery, window.app);

function addNoteDenom() {
    // var noteNumdc = $('#countnote').val();
    $('#currencyDetail tbody').append(`
    <tr>
        <td>
            <input type="number" name="fnote[]"  class="form-control fnote">
        </td>
        <td>
            <input type="number" name="fqty[]"  class="form-control fqty">
        </td>
        <td>
            <input class="dtlDelBtn btn btn-danger" type="button" value="Del -" style="padding:3px;"> 
        </td>
    </tr>
    `);
    // noteNumdc = parseInt(noteNumdc) + 1;
    // $('#countnote').val(noteNumdc);
}

$(document).on('change', '.fnote, .fqty', function () {
    calculateTotal();
});

function calculateTotal(){
    const fTotalNote = document.getElementsByClassName('fnote');
    const fTotalNoteArr = [...fTotalNote].map(input => input.value);
    const fTotalQty = document.getElementsByClassName('fqty');
    const fTotalQtyArr = [...fTotalQty].map(input => input.value);
    var fTotal = 0;
    for(var i = 0; i<fTotalNote.length; i++){
        fTotal += fTotalNoteArr[i] *  fTotalQtyArr[i];
    }
    var nprTotal = 0;
    nprTotal = fTotal * $('#conversionRate').val();
    $('#form-requestedAmount').val(nprTotal);
    $('#camount').val(nprTotal);
}

$('#currencyDetail').on('click', '.dtlDelBtn', function () {
    var selectedtr = $(this).parent().parent();
    selectedtr.remove();
    calculateTotal();
});

function conversionRatetyudc() {
    var fcurr = $('#famount').val();
    var conv = $('#conversionRate').val();
    var amount = fcurr * conv ;
    $('#form-requestedAmount').val(amount);
    $('#camount').val(amount);
}
